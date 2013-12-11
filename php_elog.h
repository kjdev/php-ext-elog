
#ifndef PHP_ELOG_H
#define PHP_ELOG_H

#define ELOG_NAMESPACE "elog"
#define ELOG_EXT_VERSION "0.2.0"

#define EL_FILTER_APPEND  1
#define EL_FILTER_PREPEND 2

#define EL_LEVEL_NONE    -1
#define EL_LEVEL_EMERG   0
#define EL_LEVEL_ALERT   1
#define EL_LEVEL_CRIT    2
#define EL_LEVEL_ERR     3
#define EL_LEVEL_WARNING 4
#define EL_LEVEL_NOTICE  5
#define EL_LEVEL_INFO    6
#define EL_LEVEL_DEBUG   7
#define EL_LEVEL_ALL     256

#define EL_LABEL_MESSAGE   "message"
#define EL_LABEL_FILE      "file"
#define EL_LABEL_LINE      "line"
#define EL_LABEL_TIMESTAMP "time"
#define EL_LABEL_LEVEL     "level"
#define EL_LABEL_REQUEST   "request"
#define EL_LABEL_TRACE     "trace"

#define EL_TO_STRING "string"
#define EL_TO_JSON   "json"
#define EL_TO_HTTP   "http"

extern zend_module_entry elog_module_entry;
#define phpext_elog_ptr &elog_module_entry

#ifdef PHP_WIN32
#    define PHP_ELOG_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#    define PHP_ELOG_API __attribute__ ((visibility("default")))
#else
#    define PHP_ELOG_API
#endif

#ifdef ZTS
#    include "TSRM.h"
#endif

ZEND_BEGIN_MODULE_GLOBALS(elog)
    long type;
    char *destination;
    char *options;
    long level;
    char *command_output;
    char *timestamp_format;
    zend_bool json_unicode_escape;
    zend_bool json_assoc;
    char *http_separator;
    long http_encode;
    char *to;
    char *exec_filter;
    char *label_message;
    char *label_file;
    char *label_line;
    char *label_timestamp;
    char *label_level;
    char *label_request;
    char *label_trace;
    zend_bool display_errors;
    zend_bool error_log;
    zend_bool error_handler;
    zend_bool error_handler_origin;
    zend_bool exception_hook;
    struct {
        HashTable *registers;
        HashTable *enabled;
    } filter;
    struct {
        zend_bool enable;
        long type;
        char *destination;
        char *options;
        zval *messages;
    } shutdown;
ZEND_END_MODULE_GLOBALS(elog)

#ifdef ZTS
#    define ELOG_G(v) TSRMG(elog_globals_id, zend_elog_globals *, v)
#else
#    define ELOG_G(v) (elog_globals.v)
#endif

#endif  /* PHP_ELOG_H */
