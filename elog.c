
#ifdef HAVE_CONFIG_H
#    include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "php_streams.h"
#include "php_main.h"
#include "zend_exceptions.h"
#include "ext/standard/info.h"
#include "ext/standard/file.h"
#include "ext/standard/php_mail.h"
//#include "ext/standard/php_var.h"

#include <spawn.h>
#include <fcntl.h>
#include <sys/wait.h>

#include "php_elog.h"
#include "elog_filter.h"

ZEND_DECLARE_MODULE_GLOBALS(elog)

ZEND_BEGIN_ARG_INFO_EX(arginfo_elog, 0, 0, 1)
    ZEND_ARG_INFO(0, message)
    ZEND_ARG_INFO(0, type)
    ZEND_ARG_INFO(0, destination)
    ZEND_ARG_INFO(0, options)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_elog_shutdown_execute, 0, 0, 1)
    ZEND_ARG_INFO(0, type)
    ZEND_ARG_INFO(0, destination)
    ZEND_ARG_INFO(0, options)
ZEND_END_ARG_INFO()

#define elog_err(_flag,...)                                   \
    if (ELOG_G(display_errors)) {                             \
        php_error_docref(NULL TSRMLS_CC, _flag, __VA_ARGS__); \
    }

typedef struct elog_spawn
{
    char *argv;
    char **args;
    int in[2];
    int out[2];
    int used_actions;
    posix_spawn_file_actions_t actions;
} elog_spawn_t;

typedef enum {
    ELOG_TYPE_STRING,
    ELOG_TYPE_JSON,
    ELOG_TYPE_HTTP
} elog_to_types;

static elog_to_types
elog_get_to_type(TSRMLS_D)
{
    char *to = ELOG_G(to);
    if (!to) {
        return ELOG_TYPE_STRING;
    } else if (strcmp(to, EL_TO_JSON) == 0) {
        return ELOG_TYPE_JSON;
    } else if (strcmp(to, EL_TO_HTTP) == 0) {
        return ELOG_TYPE_HTTP;
    }
    return ELOG_TYPE_STRING;
}

static void
elog_spawn_init(elog_spawn_t *self)
{
    self->argv = NULL;
    self->args = NULL;
    self->in[0] = -1;
    self->in[1] = -1;
    self->out[0] = -1;
    self->out[1] = -1;
    self->used_actions = 0;
}

static void
elog_spawn_destroy(elog_spawn_t *self TSRMLS_DC)
{
    if (self->args != NULL) {
        efree(self->args);
    }
    if (self->argv != NULL) {
        efree(self->argv);
    }

    if (self->used_actions) {
        posix_spawn_file_actions_destroy(&self->actions);
    }

    if (self->in[0] != -1) {
        close(self->in[0]);
    }
    if (self->in[1] != -1) {
        close(self->in[1]);
    }
    if (self->out[0] != -1) {
        close(self->out[0]);
    }
    if (self->out[1] != -1) {
        close(self->out[1]);
    }
}

static int
elog_spawn_args(elog_spawn_t *self, char *command, char *args TSRMLS_DC)
{
    int i = 1, n = 2;
    char *p, *str;
    size_t len;

    if (args != NULL) {
        len = strlen(args);
        self->argv = emalloc(sizeof(char) * (len+1));
        if (self->argv == NULL) {
            php_log_err("memory allocate argv" TSRMLS_CC);
            return FAILURE;
        }
        memcpy(self->argv, args, len);
        self->argv[len] = '\0';

        str = args;
        ++n;
        while ((p = strchr(str, ' ')) != NULL) {
            str = ++p;
            ++n;
        }
    }

    self->args = emalloc(sizeof(char *) * n);
    if (self->args == NULL) {
        php_log_err("memory allocate args" TSRMLS_CC);
        return FAILURE;
    }
    self->args[0] = command;

    if (n > 2 && self->argv != NULL) {
        p = strtok(self->argv, " ");
        self->args[i++] = p;
        while (p != NULL && i < n) {
            p = strtok(NULL, " ");
            self->args[i++] = p;
        }
    }
    self->args[n-1] = NULL;

    return SUCCESS;
}

static int
elog_spawn_actions(elog_spawn_t *self TSRMLS_DC)
{
    if (posix_spawn_file_actions_init(&self->actions) != 0) {
        php_log_err("POSIX spawn file action initilize" TSRMLS_CC);
        return FAILURE;
    }

    /* input */
    if (pipe(self->in) == -1) {
        php_log_err("create input pipe" TSRMLS_CC);
        return FAILURE;
    }
    if (posix_spawn_file_actions_addclose(&self->actions, self->in[1]) != 0 ||
        posix_spawn_file_actions_adddup2(&self->actions, self->in[0],
                                         fileno(stdin)) != 0) {
        php_log_err("POSIX spawn input action add." TSRMLS_CC);
        return FAILURE;
    }

    /* output */
    if (ELOG_G(command_output) && strlen(ELOG_G(command_output)) > 0) {
        posix_spawn_file_actions_addopen(&self->actions, fileno(stdout),
                                         ELOG_G(command_output),
                                         O_WRONLY | O_CREAT, 0600);
    } else {
        if (pipe(self->out) == -1) {
            php_log_err("create output pipe" TSRMLS_CC);
            return FAILURE;
        }
        if (posix_spawn_file_actions_addclose(&self->actions,
                                              self->out[0]) != 0 ||
            posix_spawn_file_actions_adddup2(&self->actions, self->out[1],
                                             fileno(stdout)) != 0) {
            php_log_err("POSIX spawn file action add" TSRMLS_CC);
            return FAILURE;
        }
    }

    return SUCCESS;
}

static int
elog_spawn_run(elog_spawn_t *self, char *msg, size_t len TSRMLS_DC)
{
    pid_t pid;
    int ret;

    if (posix_spawnp(&pid, self->args[0], &self->actions,
                     NULL, self->args, NULL) != 0) {
        php_log_err("POSIX spawn run" TSRMLS_CC);
        return FAILURE;
    }

    posix_spawn_file_actions_destroy(&self->actions);
    self->used_actions = 0;

    close(self->in[0]);
    self->in[0] = -1;
    if (self->out[1] != -1) {
        close(self->out[1]);
        self->out[1] = -1;
    }

    write(self->in[1], msg, len);

    close(self->in[1]);
    self->in[1] = -1;

    waitpid(pid, &ret, 0);

    if (self->out[0] != -1) {
        /*
        char buf[BUFSIZ];
        memset(buf, 0, BUFSIZ);
        read(self->out[0], buf, BUFSIZ);
        */
        close(self->out[0]);
        self->out[0] = -1;
    }

    return SUCCESS;
}

#define ELOG_HTTP_HEADER "Content-Type: application/x-www-form-urlencoded"

static int
elog_output_shutdown(int type, zval *message,
                     char *destination, char *options TSRMLS_DC)
{
    zval *msg;

    if (!ELOG_G(shutdown).enable) {
        return FAILURE;
    }

    if (type != ELOG_G(shutdown).type) {
        return FAILURE;
    }

    if (ELOG_G(shutdown).destination) {
        if (!destination ||
            strcmp(ELOG_G(shutdown).destination, destination) != 0) {
            return FAILURE;
        }
    } else if (destination) {
        return FAILURE;
    }

    if (ELOG_G(shutdown).options) {
        if (!options ||
            strcmp(ELOG_G(shutdown).options, options) != 0) {
            return FAILURE;
        }
    } else if (options) {
        return FAILURE;
    }

    MAKE_STD_ZVAL(msg);
    *msg = *message;
    zval_copy_ctor(msg);

    if (add_next_index_zval(ELOG_G(shutdown).messages, msg) != SUCCESS) {
        zval_ptr_dtor(&msg);
        return FAILURE;
    }

    return SUCCESS;
}

static inline void
elog_output_to_string_ex(zval *msg, zval *messages,
                         smart_str *str, elog_to_types type TSRMLS_DC)
{
    switch (type) {
        case ELOG_TYPE_JSON:
            php_elog_to_json(msg, messages, str TSRMLS_CC);
            break;
        case ELOG_TYPE_HTTP:
            php_elog_to_http(msg, messages, str TSRMLS_CC);
            break;
        default:
            php_elog_to_string(msg, messages, str TSRMLS_CC);
            break;
    }
}

static int
elog_output_to_string(zval *message, smart_str *str TSRMLS_DC)
{
    zval **msg;
    HashPosition pos;
    elog_to_types type = elog_get_to_type(TSRMLS_C);

    if (zend_hash_find(HASH_OF(message),
                       "msg", sizeof("msg"), (void **)&msg) != SUCCESS) {
        zend_hash_internal_pointer_reset_ex(HASH_OF(message), &pos);
        while (zend_hash_get_current_data_ex(HASH_OF(message),
                                             (void **)&msg, &pos) == SUCCESS) {
            elog_output_to_string_ex(NULL, *msg, str, type TSRMLS_CC);
            zend_hash_move_forward_ex(HASH_OF(message), &pos);
        }
    } else {
        elog_output_to_string_ex(*msg, message, str, type TSRMLS_CC);
    }
    smart_str_0(str);
    return SUCCESS;
}

static inline int
elog_output_to_mail_ex(zval *msg, zval *messages, elog_to_types type,
                       char *destination, char *options TSRMLS_DC)
{
    smart_str str = {0};
    int rc = SUCCESS;

    switch (type) {
        case ELOG_TYPE_JSON:
            php_elog_to_json(msg, messages, &str TSRMLS_CC);
            break;
        case ELOG_TYPE_HTTP:
            php_elog_to_http(msg, messages, &str TSRMLS_CC);
            break;
        default:
            php_elog_to_string(msg, messages, &str TSRMLS_CC);
            break;
    }
    smart_str_0(&str);
    if (!php_mail(destination, "PHP error_log message",
                  str.c, options, NULL TSRMLS_CC)) {
        rc = FAILURE;
    }
    smart_str_free(&str);

    return rc;
}

static int
elog_output_to_mail(zval *message, char *destination, char *options TSRMLS_DC)
{
    zval **msg;
    HashPosition pos;
    int rc = SUCCESS;
    elog_to_types type = elog_get_to_type(TSRMLS_C);

    if (zend_hash_find(HASH_OF(message),
                       "msg", sizeof("msg"), (void **)&msg) != SUCCESS) {
        zend_hash_internal_pointer_reset_ex(HASH_OF(message), &pos);
        while (zend_hash_get_current_data_ex(HASH_OF(message),
                                             (void **)&msg, &pos) == SUCCESS) {
            elog_output_to_mail_ex(NULL, *msg, type,
                                   destination, options TSRMLS_CC);
            zend_hash_move_forward_ex(HASH_OF(message), &pos);
        }
    } else {
        rc = elog_output_to_mail_ex(*msg, message, type,
                                    destination, options TSRMLS_CC);
    }
    return rc;
}

static inline int
elog_output_to_http_ex(zval *msg, zval *messages,
                       char *destination, char *options TSRMLS_DC)
{
    php_stream_context *context;
    zval method, content, header;
    php_stream *stream = NULL;
    smart_str str = {0};

    php_elog_to_http(msg, messages, &str TSRMLS_CC);
    smart_str_0(&str);

    context = php_stream_context_alloc(TSRMLS_C);
    if (!context) {
        smart_str_free(&str);
        return FAILURE;
    }

    ZVAL_STRINGL(&method, "POST", sizeof("POST")-1, 0);
    php_stream_context_set_option(context, "http", "method", &method);

    if (options) {
        ZVAL_STRINGL(&header, options, strlen(options), 0);
    } else {
        ZVAL_STRINGL(&header, ELOG_HTTP_HEADER, sizeof(ELOG_HTTP_HEADER)-1, 0);
    }
    php_stream_context_set_option(context, "http", "header", &header);

    ZVAL_STRINGL(&content, str.c, str.len, 0);

    php_stream_context_set_option(context, "http", "content", &content);

    stream = php_stream_open_wrapper_ex(destination, "r", 0, NULL, context);
    if (!stream) {
        smart_str_free(&str);
        return FAILURE;
    }
    php_stream_close(stream);

    smart_str_free(&str);

    return SUCCESS;
}

static int
elog_output_to_http(zval *message, char *destination, char *options TSRMLS_DC)
{
    zval **msg;
    HashPosition pos;
    int rc = SUCCESS;

    if (zend_hash_find(HASH_OF(message),
                       "msg", sizeof("msg"), (void **)&msg) != SUCCESS) {
        zend_hash_internal_pointer_reset_ex(HASH_OF(message), &pos);
        while (zend_hash_get_current_data_ex(HASH_OF(message),
                                             (void **)&msg, &pos) == SUCCESS) {
            elog_output_to_http_ex(NULL, *msg, destination, options TSRMLS_CC);
            zend_hash_move_forward_ex(HASH_OF(message), &pos);
        }
    } else {
        rc = elog_output_to_http_ex(*msg, message,
                                    destination, options TSRMLS_CC);
    }

    return rc;
}

static inline int
elog_output_to_socket_ex(zval *msg, zval *messages, elog_to_types type,
                         char *destination, char *options TSRMLS_DC)
{
    int err;
    char *errstr = NULL;
    struct timeval timeout = { FG(default_socket_timeout), 0 };
    php_stream *stream = NULL;
    smart_str str = {0};

    switch (type) {
        case ELOG_TYPE_JSON:
            php_elog_to_json(msg, messages, &str TSRMLS_CC);
            break;
        case ELOG_TYPE_HTTP:
            php_elog_to_http(msg, messages, &str TSRMLS_CC);
            break;
        default:
            php_elog_to_string(msg, messages, &str TSRMLS_CC);
            break;
    }
    smart_str_0(&str);

    stream = php_stream_xport_create(destination, strlen(destination),
                                     ENFORCE_SAFE_MODE | REPORT_ERRORS,
                                     STREAM_XPORT_CLIENT | STREAM_XPORT_CONNECT,
                                     NULL, &timeout, NULL, &errstr, &err);
    if (!stream) {
        if (errstr) {
            efree(errstr);
        }
        smart_str_free(&str);
        return FAILURE;
    }
    if (errstr) {
        efree(errstr);
    }

    /* nonblocking
    php_stream_set_option(stream, PHP_STREAM_OPTION_BLOCKING, 0, NULL);
    */

    php_stream_write(stream, str.c, str.len);

    php_stream_close(stream);

    smart_str_free(&str);

    return SUCCESS;
}

static int
elog_output_to_socket(zval *message, char *destination, char *options TSRMLS_DC)
{
    zval **msg;
    HashPosition pos;
    int rc = SUCCESS;
    elog_to_types type = elog_get_to_type(TSRMLS_C);

    if (zend_hash_find(HASH_OF(message),
                       "msg", sizeof("msg"), (void **)&msg) != SUCCESS) {
        zend_hash_internal_pointer_reset_ex(HASH_OF(message), &pos);
        while (zend_hash_get_current_data_ex(HASH_OF(message),
                                             (void **)&msg, &pos) == SUCCESS) {
            elog_output_to_socket_ex(NULL, *msg, type,
                                     destination, options TSRMLS_CC);
            zend_hash_move_forward_ex(HASH_OF(message), &pos);
        }
    } else {
        rc = elog_output_to_socket_ex(*msg, message, type,
                                      destination, options TSRMLS_CC);
    }

    return rc;
}

static inline void
elog_output_to_js_console_ex(zval *msg, zval *messages TSRMLS_DC)
{
    zval **data;
    smart_str m = {0};
    int n = 0;
    int display_errors = ELOG_G(display_errors);

    ELOG_G(display_errors) = 0;

    /* level */
    if (messages && zend_hash_find(HASH_OF(messages), "level", sizeof("level"),
                                   (void **)&data) == SUCCESS) {
        if (strncmp(Z_STRVAL_PP(data), "EMERGE", Z_STRLEN_PP(data)) == 0 ||
            strncmp(Z_STRVAL_PP(data), "ALERT", Z_STRLEN_PP(data)) == 0 ||
            strncmp(Z_STRVAL_PP(data), "CRIT", Z_STRLEN_PP(data)) == 0 ||
            strncmp(Z_STRVAL_PP(data), "ERR", Z_STRLEN_PP(data)) == 0) {
            php_printf("console.error(\"\",");
        } else if (strncmp(Z_STRVAL_PP(data),
                           "WARNING", Z_STRLEN_PP(data)) == 0) {
            php_printf("console.warn(\"\",");
        } else if (strncmp(Z_STRVAL_PP(data),
                           "DEBUG", Z_STRLEN_PP(data)) == 0) {
            php_printf("console.debug(\"\",");
        } else {
            php_printf("console.log(\"\",");
        }
        n++;
    } else {
        php_printf("console.log(\"\",");
    }

    /* message */
    if (msg) {
        php_elog_zval_to_json_string(msg, &m TSRMLS_CC);
    } else if (messages) {
        if (zend_hash_find(HASH_OF(messages),
                           "msg", sizeof("msg"), (void **)&data) == SUCCESS) {
            php_elog_zval_to_json_string(*data, &m TSRMLS_CC);
            n++;
        } else {
            smart_str_appendl(&m, "\"\"", 2);
        }
    } else {
        smart_str_appendl(&m, "\"\"", 2);
    }
    smart_str_0(&m);

    php_printf("%s);", m.c);

    if (messages) {
        if (zend_hash_num_elements(HASH_OF(messages)) > n) {
            smart_str s = {0};
            php_elog_to_json(NULL, messages, &s TSRMLS_CC);
            smart_str_0(&s);
            php_printf("console.dir(%s);", s.c);
            smart_str_free(&s);
        }
    }

    smart_str_free(&m);
    ELOG_G(display_errors) = display_errors;
}

static void
elog_output_to_js_console(zval *message TSRMLS_DC)
{
    zval **msg;
    HashPosition pos;

    php_printf("<script type=\"text/javascript\">"
               "if(!('console' in window)){window.console={};"
               "window.console.log=function(s1,s2){return s2;};}");

    if (zend_hash_find(HASH_OF(message),
                       "msg", sizeof("msg"), (void **)&msg) != SUCCESS) {
        zend_hash_internal_pointer_reset_ex(HASH_OF(message), &pos);
        while (zend_hash_get_current_data_ex(HASH_OF(message),
                                             (void **)&msg, &pos) == SUCCESS) {
            elog_output_to_js_console_ex(NULL, *msg TSRMLS_CC);
            zend_hash_move_forward_ex(HASH_OF(message), &pos);
        }
    } else {
        elog_output_to_js_console_ex(*msg, message TSRMLS_CC);

    }

    php_printf("</script>");
}

static inline void
elog_output_to_default_ex(zval *msg, zval *messages,
                          elog_to_types type TSRMLS_DC)
{
    smart_str str = {0};

    switch (type) {
        case ELOG_TYPE_JSON:
            php_elog_to_json(msg, messages, &str TSRMLS_CC);
            break;
        case ELOG_TYPE_HTTP:
            php_elog_to_http(msg, messages, &str TSRMLS_CC);
            break;
        default:
            php_elog_to_string(msg, messages, &str TSRMLS_CC);
            break;
    }
    if (str.c && str.c[str.len-1] == '\n') {
        str.len--;
    }
    smart_str_0(&str);

    php_log_err(str.c TSRMLS_CC);

    smart_str_free(&str);
}

static void
elog_output_to_default(zval *message TSRMLS_DC)
{
    zval **msg;
    HashPosition pos;
    elog_to_types type = elog_get_to_type(TSRMLS_C);

    if (zend_hash_find(HASH_OF(message),
                       "msg", sizeof("msg"), (void **)&msg) != SUCCESS) {
        zend_hash_internal_pointer_reset_ex(HASH_OF(message), &pos);
        while (zend_hash_get_current_data_ex(HASH_OF(message),
                                             (void **)&msg, &pos) == SUCCESS) {
            elog_output_to_default_ex(NULL, *msg, type TSRMLS_CC);
            zend_hash_move_forward_ex(HASH_OF(message), &pos);
        }
    } else {
        elog_output_to_default_ex(*msg, message, type TSRMLS_CC);

    }
}

static int
elog_output(int type, zval *message, char *destination, char *options TSRMLS_DC)
{
    smart_str str = {0};

    switch (type) {
        case -1:
            /* standard output */
            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }
            elog_output_to_string(message, &str TSRMLS_CC);
            php_printf("%s", str.c);
            smart_str_free(&str);
            return SUCCESS;
        case 1:
            /* origin: send an email */
            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }
            return elog_output_to_mail(message, destination, options TSRMLS_CC);
        case 2:
            /* origin: send to an address */
            php_error_docref(NULL TSRMLS_CC, E_WARNING,
                             "TCP/IP option not available!");
            return FAILURE;
        case 3: {
            /* origin: save to a file */
            php_stream *stream = NULL;
            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }
            stream = php_stream_open_wrapper(destination, "a",
                                             IGNORE_URL_WIN | REPORT_ERRORS,
                                             NULL);
            if (!stream) {
                return FAILURE;
            }
            elog_output_to_string(message, &str TSRMLS_CC);
            php_stream_write(stream, str.c, str.len);
            smart_str_free(&str);
            php_stream_close(stream);
            return SUCCESS;
        }
        case 4:
            /* origin: send to SAPI */
            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }
            if (sapi_module.log_message) {
                elog_output_to_string(message, &str TSRMLS_CC);
                sapi_module.log_message(str.c TSRMLS_CC);
                smart_str_free(&str);
                return SUCCESS;
            }
            return FAILURE;
        case 10: {
            /* spawn: send to command */
            elog_spawn_t espawn;

            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }

            if (!destination || strlen(destination) == 0) {
                elog_err(E_WARNING, "Command cannot be empty");
                return FAILURE;
            }

            elog_spawn_init(&espawn);

            if (elog_spawn_args(&espawn, destination,
                                options TSRMLS_CC) != SUCCESS) {
                elog_spawn_destroy(&espawn TSRMLS_CC);
                return FAILURE;
            }

            if (elog_spawn_actions(&espawn TSRMLS_CC) != SUCCESS) {
                elog_spawn_destroy(&espawn TSRMLS_CC);
                return FAILURE;
            }

            elog_output_to_string(message, &str TSRMLS_CC);

            if (elog_spawn_run(&espawn, str.c, str.len TSRMLS_CC) != SUCCESS) {
                elog_spawn_destroy(&espawn TSRMLS_CC);
                smart_str_free(&str);
                return FAILURE;
            }

            elog_spawn_destroy(&espawn TSRMLS_CC);

            smart_str_free(&str);
            return SUCCESS;
        }
        case 11:
            /* send to transport://target */
            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }

            if (!destination || strlen(destination) == 0) {
                elog_err(E_WARNING, "Address cannot be empty");
                return FAILURE;
            }

            if (strncasecmp(destination, "http://", 7) == 0 ||
                strncasecmp(destination, "https://", 8) == 0) {
                return elog_output_to_http(message, destination,
                                           options TSRMLS_CC);
            } else {
                return elog_output_to_socket(message, destination,
                                             options TSRMLS_CC);
            }
            break;
        case 12:
            /* output to javascript.console */
            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }
            elog_output_to_js_console(message TSRMLS_CC);
            return SUCCESS;
        default:
            if (elog_output_shutdown(type, message, destination,
                                     options TSRMLS_CC) == SUCCESS) {
                return SUCCESS;
            }
            elog_output_to_default(message TSRMLS_CC);
            return SUCCESS;
    }
    return FAILURE;
}

static inline zval *
elog_filter_function_table(zval *msg, zval *level TSRMLS_DC)
{
    zval *return_value;
    zval *retval, **args[2];
    HashPosition pos;
    php_elog_filter_data_t *data;

    return_value = msg;

    zend_hash_internal_pointer_reset_ex(ELOG_G(filter).enabled, &pos);
    while (zend_hash_get_current_data_ex(ELOG_G(filter).enabled,
                                         (void **)&data, &pos) == SUCCESS) {
        int builtin = 0;
        zval **arg = NULL;

        if (php_elog_filter_is_builtin(data->function) == SUCCESS) {
            builtin = 1;
            arg = &msg;
        } else if (zend_hash_find(HASH_OF(msg), "msg", sizeof("msg"),
                                  (void **)&arg) != SUCCESS) {
            arg = NULL;
        }

        args[0] = arg;
        args[1] = &level;

        if (arg &&
            call_user_function_ex(EG(function_table), NULL,
                                  data->function, &retval, 2, args, 0, NULL
                                  TSRMLS_CC) == SUCCESS && retval) {
            if (builtin) {
                return_value = retval;
                zval_ptr_dtor(&msg);
                msg = return_value;
            } else {
                add_assoc_zval_ex(msg, "msg", sizeof("msg"), retval);
            }
        } else {
            char *str_key = NULL;
            uint str_key_len;
            ulong num_key;
            zend_hash_get_current_key_ex(ELOG_G(filter).enabled,
                                         &str_key, &str_key_len,
                                         &num_key, 0, &pos);
            elog_err(E_WARNING, "Called function \"%s\"", str_key);
        }

        zend_hash_move_forward_ex(ELOG_G(filter).enabled, &pos);
    }

    return return_value;
}

static inline zval *
elog_filter_function_exec(zval *msg, zval *level, char *extra TSRMLS_DC)
{
    long len;
    char *functions, *token, *name;
    zval *return_value;
    zval *retval, **args[2];
    php_elog_filter_data_t *data;

    return_value = msg;

    if (extra) {
        spprintf(&functions, 0, "%s,%s", extra, ELOG_G(exec_filter));
    } else {
        spprintf(&functions, 0, "%s", ELOG_G(exec_filter));
    }
    token = functions;

    while (1) {
        name = strtok(token, ",");
        if (name == NULL) {
            break;
        }
        while (*name == ' ') {
            name++;
        }
        len = strlen(name) - 1;
        while (*(name+len) == ' ' && len > 0) {
            *(name+len) = '\0';
            len--;
        }

        if (!zend_hash_exists(ELOG_G(filter).enabled, name, strlen(name)+1)) {
            if (zend_hash_find(ELOG_G(filter).registers, name, strlen(name)+1,
                               (void **)&data) == SUCCESS) {
                int builtin = 0;
                zval **arg = NULL;

                if (php_elog_filter_is_builtin(data->function) == SUCCESS) {
                    builtin = 1;
                    arg = &msg;
                } else if (zend_hash_find(HASH_OF(msg), "msg", sizeof("msg"),
                                          (void **)&arg) != SUCCESS) {
                    arg = NULL;
                }

                args[0] = arg;
                args[1] = &level;

                if (arg &&
                    call_user_function_ex(EG(function_table), NULL,
                                          data->function, &retval,
                                          2, args, 0, NULL
                                          TSRMLS_CC) == SUCCESS && retval) {
                    if (builtin) {
                        return_value = retval;
                        zval_ptr_dtor(&msg);
                        msg = return_value;
                    } else {
                        add_assoc_zval_ex(msg, "msg", sizeof("msg"), retval);
                    }
                } else {
                    elog_err(E_WARNING, "Called function \"%s\"", name);
                }
            } else if (zend_hash_exists(EG(function_table),
                                        name, strlen(name)+1)) {
                int builtin = 0;
                zval *function, **arg = NULL;

                MAKE_STD_ZVAL(function);
                ZVAL_STRINGL(function, name, strlen(name), 1);

                if (php_elog_filter_is_builtin(function) == SUCCESS) {
                    builtin = 1;
                    arg = &msg;
                } else if (zend_hash_find(HASH_OF(msg), "msg", sizeof("msg"),
                                          (void **)&arg) != SUCCESS) {
                    arg = NULL;
                }

                args[0] = arg;
                args[1] = &level;

                if (arg &&
                    call_user_function_ex(EG(function_table), NULL,
                                          function, &retval, 2, args, 0, NULL
                                          TSRMLS_CC) == SUCCESS && retval) {
                    if (builtin) {
                        return_value = retval;
                        zval_ptr_dtor(&msg);
                        msg = return_value;
                    } else {
                        add_assoc_zval_ex(msg, "msg", sizeof("msg"), retval);
                    }
                } else {
                    elog_err(E_WARNING, "Called function \"%s\"", name);
                }
                zval_ptr_dtor(&function);
            }
        }

        token = NULL;
    }
    efree(functions);

    return return_value;
}

static inline void
elog_output_multi(zval *types, zval *message, zval *level TSRMLS_DC)
{
    zval **data;
    HashPosition pos;
    HashTable *myht = HASH_OF(types);
    zend_bool is_error = 1;

    zend_hash_internal_pointer_reset_ex(myht, &pos);
    while (zend_hash_get_current_data_ex(myht, (void **)&data,
                                         &pos) == SUCCESS) {
        zval *msg;
        int i, num, type = 0;
        char *destination = NULL, *options = NULL, *filters = NULL;
        char *to = NULL, *origin_to = NULL;

        if (Z_TYPE_PP(data) != IS_ARRAY) {
            zend_hash_move_forward_ex(myht, &pos);
            continue;
        }

        num = zend_hash_num_elements(HASH_OF(*data));
        if (num < 2) {
            zend_hash_move_forward_ex(myht, &pos);
            continue;
        }

        for (i = 0; i < num; i++) {
            zval **val;
            if (zend_hash_index_find(HASH_OF(*data), i,
                                     (void **)&val) == SUCCESS) {
                switch (i) {
                    case 0:
                        if (Z_TYPE_PP(val) != IS_LONG) {
                            convert_to_long(*val);
                        }
                        type = Z_LVAL_PP(val);
                        break;
                    case 1:
                        if (Z_TYPE_PP(val) == IS_STRING &&
                            Z_STRLEN_PP(val) > 0) {
                            destination = Z_STRVAL_PP(val);
                        }
                        break;
                    case 2:
                        if (Z_TYPE_PP(val) == IS_STRING &&
                            Z_STRLEN_PP(val) > 0) {
                            options = Z_STRVAL_PP(val);
                        }
                        break;
                    case 3:
                        if (Z_TYPE_PP(val) == IS_STRING &&
                            Z_STRLEN_PP(val) > 0) {
                            filters = Z_STRVAL_PP(val);
                        }
                        break;
                    case 4:
                        if (Z_TYPE_PP(val) == IS_STRING &&
                            Z_STRLEN_PP(val) > 0) {
                            to = Z_STRVAL_PP(val);
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        MAKE_STD_ZVAL(msg);
        *msg = *message;
        zval_copy_ctor(msg);

        if (filters ||
            (ELOG_G(exec_filter) && strlen(ELOG_G(exec_filter)) > 0)) {
            msg = elog_filter_function_exec(msg, level, filters TSRMLS_CC);
        }

        if (to) {
            origin_to = ELOG_G(to);
            ELOG_G(to) = to;
        }

        elog_output(type, msg, destination, options TSRMLS_CC);
        is_error = 0;

        zval_ptr_dtor(&msg);

        if (origin_to) {
            ELOG_G(to) = origin_to;
        }

        zend_hash_move_forward_ex(myht, &pos);
    }

    if (is_error) {
        elog_err(E_WARNING, "Invalid arguments");
    }
}

ZEND_FUNCTION(elog_shutdown_execute)
{
    char *destination = NULL, *options = NULL;
    int destination_len = 0, options_len = 0;
    long type = 0;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|lps",
                              &type, &destination, &destination_len,
                              &options, &options_len) == FAILURE) {
        RETURN_FALSE;
    }

    if (ELOG_G(shutdown).enable) {
        elog_err(E_WARNING, "Already shutdown function");
        RETURN_FALSE;
    }

    if (type == 0) {
        type = ELOG_G(type);
    }
    if (destination == NULL) {
        destination = ELOG_G(destination);
        if (destination) {
            destination_len = strlen(destination);
        }
    }
    if (options == NULL) {
        options = ELOG_G(options);
        if (options) {
            options_len = strlen(options);
        }
    }

    ELOG_G(shutdown).enable = 1;
    ELOG_G(shutdown).type = type;
    if (destination_len > 0) {
        ELOG_G(shutdown).destination = estrndup(destination, destination_len);
    }
    if (options_len > 0) {
        ELOG_G(shutdown).options = estrndup(options, options_len);
    }

    RETURN_TRUE;
}

#define ELOG_FUNCTION(_name, _level)                                      \
ZEND_FUNCTION(elog ## _name)                                              \
{                                                                         \
    char *destination = NULL, *options = NULL;                            \
    int destination_len = 0, options_len = 0;                             \
    int type = 0, argc = ZEND_NUM_ARGS();                                 \
    zval *arg_type = NULL, *level, *message;                              \
    zval *buf, *msg;                                                      \
    zend_bool is_multi = 0;                                               \
    if (ELOG_G(level) < _level) {                                         \
        RETURN_TRUE;                                                      \
    }                                                                     \
    if (zend_parse_parameters(argc TSRMLS_CC, "z|zps",                    \
                              &message, &arg_type,                        \
                              &destination, &destination_len,             \
                              &options, &options_len) == FAILURE) {       \
        RETURN_FALSE;                                                     \
    }                                                                     \
    RETVAL_FALSE;                                                         \
    MAKE_STD_ZVAL(msg);                                                   \
    *msg = *message;                                                      \
    zval_copy_ctor(msg);                                                  \
    Z_SET_REFCOUNT_P(msg, 1);                                             \
    Z_UNSET_ISREF_P(msg);                                                 \
    MAKE_STD_ZVAL(buf);                                                   \
    array_init(buf);                                                      \
    if (add_assoc_zval_ex(buf, "msg", sizeof("msg"), msg) != SUCCESS) {   \
        zval_ptr_dtor(&msg);                                              \
        zval_ptr_dtor(&buf);                                              \
        return;                                                           \
    }                                                                     \
    MAKE_STD_ZVAL(level);                                                 \
    ZVAL_LONG(level, _level);                                             \
    if (ELOG_G(filter).enabled &&                                         \
        zend_hash_num_elements(ELOG_G(filter).enabled) > 0) {             \
        buf = elog_filter_function_table(buf, level TSRMLS_CC);           \
    }                                                                     \
    if (argc > 1) {                                                       \
        if (Z_TYPE_P(arg_type) == IS_ARRAY) {                             \
            is_multi = 1;                                                 \
        } else {                                                          \
            convert_to_long(arg_type);                                    \
            type = Z_LVAL_P(arg_type);                                    \
        }                                                                 \
    }                                                                     \
    if (!arg_type) {                                                      \
        type = ELOG_G(type);                                              \
    }                                                                     \
    if (is_multi == 0) {                                                  \
        if (ELOG_G(exec_filter) && strlen(ELOG_G(exec_filter)) > 0) {     \
            buf = elog_filter_function_exec(buf, level, NULL TSRMLS_CC);  \
        }                                                                 \
        if (destination_len == 0 &&                                       \
            ELOG_G(destination) && strlen(ELOG_G(destination)) > 0) {     \
            destination = ELOG_G(destination);                            \
        }                                                                 \
        if (options_len == 0 &&                                           \
            ELOG_G(options) && strlen(ELOG_G(options)) > 0) {             \
            options = ELOG_G(options);                                    \
        }                                                                 \
        if (elog_output(type, buf, destination,                           \
                        options TSRMLS_CC) != FAILURE) {                  \
            RETVAL_TRUE;                                                  \
        }                                                                 \
    } else {                                                              \
        elog_output_multi(arg_type, buf, level TSRMLS_CC);                \
    }                                                                     \
    zval_ptr_dtor(&level);                                                \
    zval_ptr_dtor(&buf);                                                  \
}

ELOG_FUNCTION(,EL_LEVEL_NONE)
ELOG_FUNCTION(_emerg, EL_LEVEL_EMERG)
ELOG_FUNCTION(_alert, EL_LEVEL_ALERT)
ELOG_FUNCTION(_crit, EL_LEVEL_CRIT)
ELOG_FUNCTION(_err, EL_LEVEL_ERR)
ELOG_FUNCTION(_warning, EL_LEVEL_WARNING)
ELOG_FUNCTION(_notice, EL_LEVEL_NOTICE)
ELOG_FUNCTION(_info, EL_LEVEL_INFO)
ELOG_FUNCTION(_debug, EL_LEVEL_DEBUG)

ZEND_INI_MH(OnUpdateLevel)
{
    if (!new_value || new_value_length == 0) {
        ELOG_G(level) = EL_LEVEL_ALL;
    } else if (strncasecmp(new_value, "emerg", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_EMERG;
    } else if (strncasecmp(new_value, "alert", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_ALERT;
    } else if (strncasecmp(new_value, "crit", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_CRIT;
    } else if (strncasecmp(new_value, "err", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_ERR;
    } else if (strncasecmp(new_value, "warning", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_WARNING;
    } else if (strncasecmp(new_value, "notice", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_NOTICE;
    } else if (strncasecmp(new_value, "info", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_INFO;
    } else if (strncasecmp(new_value, "debug", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_DEBUG;
    } else if (strncasecmp(new_value, "none", new_value_length) == 0) {
        ELOG_G(level) = EL_LEVEL_NONE;
    } else if (is_numeric_string(new_value, new_value_length,
                                 NULL, NULL, 0) == IS_LONG) {
        ELOG_G(level) = atoi(new_value);
    } else {
        ELOG_G(level) = EL_LEVEL_ALL;
    }
    return SUCCESS;
}

ZEND_INI_BEGIN()
    /* INI_ALL */
    STD_ZEND_INI_ENTRY("elog.default_type", "0",
                       ZEND_INI_ALL, OnUpdateLong, type,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.default_destination", (char *)NULL,
                       ZEND_INI_ALL, OnUpdateString, destination,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.default_options", (char *)NULL,
                       ZEND_INI_ALL, OnUpdateString, options,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.command_output", (char *)NULL,
                       ZEND_INI_ALL, OnUpdateString, command_output,
                       zend_elog_globals, elog_globals)
    ZEND_INI_ENTRY("elog.level", NULL, ZEND_INI_ALL, OnUpdateLevel)
    STD_ZEND_INI_ENTRY("elog.filter_execute", (char *)NULL,
                       ZEND_INI_ALL, OnUpdateString, exec_filter,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_BOOLEAN("elog.filter_json_unicode_escape", "On",
                         ZEND_INI_ALL, OnUpdateBool, json_unicode_escape,
                         zend_elog_globals, elog_globals)
    STD_ZEND_INI_BOOLEAN("elog.filter_json_assoc", "Off",
                         ZEND_INI_ALL, OnUpdateBool, json_assoc,
                         zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_http_separator", (char *)NULL,
                       ZEND_INI_ALL, OnUpdateString, http_separator,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_http_encode", "0",
                       ZEND_INI_ALL, OnUpdateLong, http_encode,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_timestamp_format", (char *)NULL,
                       ZEND_INI_ALL, OnUpdateString, timestamp_format,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_label_message", EL_LABEL_MESSAGE,
                       ZEND_INI_ALL, OnUpdateString, label_message,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_label_file", EL_LABEL_FILE,
                       ZEND_INI_ALL, OnUpdateString, label_file,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_label_line", EL_LABEL_LINE,
                       ZEND_INI_ALL, OnUpdateString, label_line,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_label_timestamp", EL_LABEL_TIMESTAMP,
                       ZEND_INI_ALL, OnUpdateString, label_timestamp,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_label_level", EL_LABEL_LEVEL,
                       ZEND_INI_ALL, OnUpdateString, label_level,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_label_request", EL_LABEL_REQUEST,
                       ZEND_INI_ALL, OnUpdateString, label_request,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.filter_label_trace", EL_LABEL_TRACE,
                       ZEND_INI_ALL, OnUpdateString, label_trace,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_ENTRY("elog.to", EL_TO_STRING,
                       ZEND_INI_ALL, OnUpdateString, to,
                       zend_elog_globals, elog_globals)
    STD_ZEND_INI_BOOLEAN("elog.display_errors", "On",
                         ZEND_INI_ALL, OnUpdateBool, display_errors,
                         zend_elog_globals, elog_globals)
    /* INI_SYSTEM */
    STD_ZEND_INI_BOOLEAN("elog.override_error_log", "Off",
                         ZEND_INI_SYSTEM, OnUpdateBool, error_log,
                         zend_elog_globals, elog_globals)
    STD_ZEND_INI_BOOLEAN("elog.override_error_handler", "Off",
                         ZEND_INI_SYSTEM, OnUpdateBool, error_handler,
                         zend_elog_globals, elog_globals)
    STD_ZEND_INI_BOOLEAN("elog.called_origin_error_handler", "On",
                         ZEND_INI_SYSTEM, OnUpdateBool, error_handler_origin,
                         zend_elog_globals, elog_globals)
    STD_ZEND_INI_BOOLEAN("elog.throw_exception_hook", "Off",
                         ZEND_INI_SYSTEM, OnUpdateBool, exception_hook,
                         zend_elog_globals, elog_globals)
ZEND_INI_END()

zend_function *origin_error_log = NULL;
void (*origin_error_cb)(int type, const char *error_filename,
                        const uint error_lineno, const char *format,
                        va_list args);

static zend_function *
elog_override_function(char *origin, char *override TSRMLS_DC)
{
    size_t origin_len = strlen(origin);
    size_t override_len = strlen(override);
    zend_function *origin_fe, *override_fe;

    if (zend_hash_find(CG(function_table), override, override_len+1,
                       (void **)&override_fe) == FAILURE) {
        zend_error(E_WARNING, "Function symbol not found: \"%s\"", override);
        return NULL;
    }

    if (zend_hash_find(CG(function_table), origin, origin_len+1,
                       (void **)&origin_fe) == FAILURE) {
        zend_error(E_WARNING, "Function symbol not found: \"%s\"", override);
        return NULL;
    }

    if (zend_hash_update(CG(function_table), origin, origin_len+1,
                         (void *)override_fe, sizeof(zend_function),
                         NULL) == FAILURE) {
        zend_error(E_WARNING, "Error override reference to function name %s()",
                   origin);
        return NULL;
    }

    function_add_ref(override_fe);

    return origin_fe;
}

static inline void
elog_message_output(int error_level, zval *message TSRMLS_DC)
{
    zval *buf, *msg, *level;
    int type = 0;
    char *destination = NULL, *options = NULL;

    MAKE_STD_ZVAL(msg);
    *msg = *message;
    zval_copy_ctor(msg);

    MAKE_STD_ZVAL(buf);
    array_init(buf);
    if (add_assoc_zval_ex(buf, "msg", sizeof("msg"), msg) != SUCCESS) {
        zval_ptr_dtor(&msg);
        zval_ptr_dtor(&buf);
        return;
    }

    MAKE_STD_ZVAL(level);
    ZVAL_LONG(level, error_level);

    /* enabled filter */
    if (ELOG_G(filter).enabled &&
        zend_hash_num_elements(ELOG_G(filter).enabled) > 0) {
        buf = elog_filter_function_table(buf, level TSRMLS_CC);
    }

    /* type */
    type = ELOG_G(type);

    /* exec filter */
    if (ELOG_G(exec_filter) && strlen(ELOG_G(exec_filter)) > 0) {
        buf = elog_filter_function_exec(buf, level, NULL TSRMLS_CC);
    }

    /* destination */
    if (ELOG_G(destination) && strlen(ELOG_G(destination)) > 0) {
        destination = ELOG_G(destination);
    }

    /* options */
    if (ELOG_G(options) && strlen(ELOG_G(options)) > 0) {
        options = ELOG_G(options);
    }

    /* output */
    elog_output(type, buf, destination, options TSRMLS_CC);

    /* cleanup */
    zval_ptr_dtor(&level);
    zval_ptr_dtor(&buf);
}

static void
elog_error_cb(int type, const char *error_filename, const uint error_lineno,
              const char *format, va_list args)
{
    int error_level = EL_LEVEL_NONE;
    char *buffer, *error_type_str, *error_message;
    int error_message_len;
    va_list error_args;
    zval *message;
    int display_errors, display_startup_errors, log_errors;

    TSRMLS_FETCH();

    va_copy(error_args, args);
    vspprintf(&buffer, 0, format, error_args);
    va_end(error_args);

    switch (type) {
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            error_type_str = "Fatal error";
            error_level = EL_LEVEL_ERR;
            break;
        case E_RECOVERABLE_ERROR:
            error_type_str = "Catchable fatal error";
            error_level = EL_LEVEL_ERR;
            break;
        case E_WARNING:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
        case E_USER_WARNING:
            error_type_str = "Warning";
            error_level = EL_LEVEL_WARNING;
            break;
        case E_PARSE:
            error_type_str = "Parse error";
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            error_type_str = "Notice";
            error_level = EL_LEVEL_NOTICE;
            break;
        case E_STRICT:
            error_type_str = "Strict Standards";
            error_level = EL_LEVEL_INFO;
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            error_type_str = "Deprecated";
            error_level = EL_LEVEL_INFO;
            break;
        default:
            error_type_str = "Unknown error";
            break;
    }

    error_message_len = spprintf(&error_message, 0,
                                 "PHP %s:  %s in %s on line %d",
                                 error_type_str, buffer,
                                 error_filename, error_lineno);

    MAKE_STD_ZVAL(message);
    ZVAL_STRINGL(message, error_message, error_message_len, 0);

    elog_message_output(error_level, message TSRMLS_CC);

    zval_ptr_dtor(&message);

    efree(buffer);
    //efree(error_message);

    /* origin_error_cb */
    display_errors = PG(display_errors);
    display_startup_errors = PG(display_startup_errors);
    log_errors = PG(log_errors);

    if (!ELOG_G(error_handler_origin)) {
        PG(display_errors) = 0;
        PG(display_startup_errors) = 0;
        PG(log_errors) = 0;
    }
    origin_error_cb(type, error_filename, error_lineno, format, args);

    PG(display_errors) = display_errors;
    PG(display_startup_errors) = display_startup_errors;
    PG(log_errors) = log_errors;
}

static void
elog_throw_exception_hook(zval *exception TSRMLS_DC)
{
    zend_class_entry *ce;
    zval *message, *global_exception;
    /* zval *file, *line; */

    if (!exception) {
        return;
    }

    /* default_ce = zend_exception_get_default(TSRMLS_C); */
    ce = zend_get_class_entry(exception TSRMLS_CC);

    message = zend_read_property(ce, exception,
                                 "message", sizeof("message")-1, 0 TSRMLS_CC);
    /*
    file = zend_read_property(ce, exception,
                              "file", sizeof("file")-1, 0 TSRMLS_CC);
    line = zend_read_property(ce, exception,
                              "line",  sizeof("line")-1, 0 TSRMLS_CC);
    */

    global_exception = EG(exception);
    EG(exception) = NULL;

    elog_message_output(EL_LEVEL_ERR, message TSRMLS_CC);

    EG(exception) = global_exception;
}

static void
elog_init_globals(zend_elog_globals *elog_globals)
{
    elog_globals->type = 0;
    elog_globals->destination = NULL;
    elog_globals->options = NULL;
    elog_globals->level = EL_LEVEL_ALL;
    elog_globals->command_output = NULL;
    elog_globals->timestamp_format = NULL;
    elog_globals->json_unicode_escape = 1;
    elog_globals->json_assoc = 0;
    elog_globals->http_separator = NULL;
    elog_globals->http_encode = 0;
    elog_globals->exec_filter = NULL;
    elog_globals->label_message = NULL;
    elog_globals->label_file = NULL;
    elog_globals->label_line = NULL;
    elog_globals->label_timestamp = NULL;
    elog_globals->label_level = NULL;
    elog_globals->label_request = NULL;
    elog_globals->label_trace = NULL;
    elog_globals->to = NULL;

    elog_globals->display_errors = 1;
    elog_globals->error_log = 0;
    elog_globals->error_handler = 0;
    elog_globals->error_handler_origin = 0;
    elog_globals->exception_hook = 0;

    memset(&elog_globals->filter, 0, sizeof(elog_globals->filter));
    memset(&elog_globals->shutdown, 0, sizeof(elog_globals->shutdown));
}

ZEND_MINIT_FUNCTION(elog)
{
    REGISTER_LONG_CONSTANT("EL_FILTER_APPEND", EL_FILTER_APPEND,
                           CONST_PERSISTENT | CONST_CS);
    REGISTER_LONG_CONSTANT("EL_FILTER_PREPEND", EL_FILTER_PREPEND,
                           CONST_PERSISTENT | CONST_CS);

    REGISTER_STRING_CONSTANT("EL_NONE", "none",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_EMERG", "emerg",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_ALERT", "alert",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_CRIT", "crit",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_ERR", "err",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_WARNING", "warning",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_NOTICE", "notice",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_INFO", "info",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_DEBUG", "debug",
                             CONST_PERSISTENT | CONST_CS);
    REGISTER_STRING_CONSTANT("EL_ALL", "all",
                             CONST_PERSISTENT | CONST_CS);

    ZEND_INIT_MODULE_GLOBALS(elog, elog_init_globals, NULL);
    REGISTER_INI_ENTRIES();

    /* Storing actual error callback function for later restore */
    origin_error_cb = zend_error_cb;

    /* Override error_log function */
    if (ELOG_G(error_log)) {
        origin_error_log = elog_override_function("error_log", "elog" TSRMLS_CC);
    }

    return SUCCESS;
}

ZEND_MSHUTDOWN_FUNCTION(elog)
{
    /* Restoring saved error callback function */
    zend_error_cb = origin_error_cb;

    UNREGISTER_INI_ENTRIES();

    return SUCCESS;
}

ZEND_RINIT_FUNCTION(elog)
{
    /* Replacing current error handler */
    if (ELOG_G(error_handler)) {
        zend_error_cb = elog_error_cb;
    }

    /* Set throw exception hook */
    if (ELOG_G(exception_hook)) {
        zend_throw_exception_hook = elog_throw_exception_hook;
    }

    /* Initilize filter tabale */
    ALLOC_HASHTABLE(ELOG_G(filter).registers);
    zend_hash_init(ELOG_G(filter).registers, 5, NULL,
                   (dtor_func_t)php_elog_filter_data_dtor, 0);

    ALLOC_HASHTABLE(ELOG_G(filter).enabled);
    zend_hash_init(ELOG_G(filter).enabled, 5, NULL,
                   (dtor_func_t)php_elog_filter_data_dtor, 0);

    /* Initilize shutdown messages */
    MAKE_STD_ZVAL(ELOG_G(shutdown).messages);
    array_init(ELOG_G(shutdown).messages);

    return SUCCESS;
}

ZEND_RSHUTDOWN_FUNCTION(elog)
{
    /* Execute and Cleanup shutdown */
    if (ELOG_G(shutdown).messages) {
        ELOG_G(shutdown).enable = 0;
        if (zend_hash_num_elements(HASH_OF(ELOG_G(shutdown).messages)) > 0) {
            elog_output(ELOG_G(shutdown).type,
                        ELOG_G(shutdown).messages,
                        ELOG_G(shutdown).destination,
                        ELOG_G(shutdown).options TSRMLS_CC);
        }
        zval_ptr_dtor(&(ELOG_G(shutdown).messages));
    }
    if (ELOG_G(shutdown).destination) {
        efree(ELOG_G(shutdown).destination);
    }
    if (ELOG_G(shutdown).options) {
        efree(ELOG_G(shutdown).options);
    }

    /* Restoring error handler and throw exception hook */
    zend_error_cb = origin_error_cb;
    zend_throw_exception_hook = NULL;

    /* Cleanup enabled filters */
    if (ELOG_G(filter).enabled) {
        zend_hash_destroy(ELOG_G(filter).enabled);
        efree(ELOG_G(filter).enabled);
    }

    if (ELOG_G(filter).registers) {
        zend_hash_destroy(ELOG_G(filter).registers);
        efree(ELOG_G(filter).registers);
    }

    return SUCCESS;
}

ZEND_MINFO_FUNCTION(elog)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "elog support", "enabled");
    php_info_print_table_row(2, "Extension Version", ELOG_EXT_VERSION);
    if (ELOG_G(error_log)) {
        php_info_print_table_row(2, "Override error_log", "enabled");
    }
    if (ELOG_G(error_handler)) {
        php_info_print_table_row(2, "Override error_handler", "enabled");
    }
    php_info_print_table_end();
}

static zend_function_entry elog_functions[] = {
    ZEND_FE(elog, arginfo_elog)
    ZEND_FE(elog_emerg, arginfo_elog)
    ZEND_FE(elog_alert, arginfo_elog)
    ZEND_FE(elog_crit, arginfo_elog)
    ZEND_FE(elog_err, arginfo_elog)
    ZEND_FE(elog_warning, arginfo_elog)
    ZEND_FE(elog_notice, arginfo_elog)
    ZEND_FE(elog_info, arginfo_elog)
    ZEND_FE(elog_debug, arginfo_elog)
    ZEND_FE(elog_register_filter, arginfo_elog_register_filter)
    ZEND_FE(elog_append_filter, arginfo_elog_append_filter)
    ZEND_FE(elog_prepend_filter, arginfo_elog_prepend_filter)
    ZEND_FE(elog_remove_filter, arginfo_elog_remove_filter)
    ZEND_FE(elog_get_filter, arginfo_elog_get_filter)
    ZEND_FE(elog_filter_add_fileline, arginfo_elog_filter_function)
    ZEND_FE(elog_filter_add_timestamp, arginfo_elog_filter_function)
    ZEND_FE(elog_filter_add_request, arginfo_elog_filter_function)
    ZEND_FE(elog_filter_add_level, arginfo_elog_filter_function)
    ZEND_FE(elog_filter_add_trace, arginfo_elog_filter_function)
    ZEND_FE(elog_shutdown_execute, arginfo_elog_shutdown_execute)
    ZEND_FE_END
};

zend_module_entry elog_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    "elog",
    elog_functions,
    ZEND_MINIT(elog),
    ZEND_MSHUTDOWN(elog),
    ZEND_RINIT(elog),
    ZEND_RSHUTDOWN(elog),
    ZEND_MINFO(elog),
#if ZEND_MODULE_API_NO >= 20010901
    ELOG_EXT_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_ELOG
ZEND_GET_MODULE(elog)
#endif
