
#ifdef HAVE_CONFIG_H
#    include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/php_var.h"
#include "ext/standard/php_string.h"
#include "ext/standard/php_smart_str.h"
#include "ext/standard/php_http.h"
#include "ext/standard/url.h"
#include "ext/date/php_date.h"

#include "jansson/jansson.h"

#include "elog_filter.h"

ZEND_DECLARE_MODULE_GLOBALS(elog)

#define elog_err(_flag,...) php_error_docref(NULL TSRMLS_CC, _flag, __VA_ARGS__)

#define ELOG_TYPE_ARRAY 1
#define ELOG_TYPE_ASSOC 2

#define ELOG_MODE_DUMP    1
#define ELOG_MODE_CONVERT 2

#define ELOG_BUFFER_APPEND_SPACES(_buf, _num)                       \
    do {                                                            \
        char *tmp_spaces;                                           \
        int tmp_spaces_len;                                         \
        tmp_spaces_len = spprintf(&tmp_spaces, 0,"%*c", _num, ' '); \
        smart_str_appendl(_buf, tmp_spaces, tmp_spaces_len);        \
        efree(tmp_spaces);                                          \
    } while(0)

static inline void elog_var_dump(zval **struc, int level,
                                 smart_str *buf, int mode TSRMLS_DC);

static inline int
elog_array_type(zval *val TSRMLS_DC)
{
    ulong index = 0;
    HashTable *myht;
    Bucket *p;

    myht = Z_ARRVAL_P(val);
    p = myht->pListHead;
    while (p) {
        if (p->nKeyLength || index != p->h) {
            return ELOG_TYPE_ASSOC;
        }
        p = p->pListNext;
        index++;
    }

    return ELOG_TYPE_ARRAY;
}


static inline int
elog_array_element_dump(zval **zv TSRMLS_DC, int num_args,
                        va_list args, zend_hash_key *hash_key)
{
    int level, array_type;
    smart_str *buf;

    level = va_arg(args, int);
    buf = va_arg(args, smart_str *);
    array_type = va_arg(args, int);

    ELOG_BUFFER_APPEND_SPACES(buf, level+1);

    if (array_type == ELOG_TYPE_ASSOC) {
        if (hash_key->nKeyLength == 0) {
            smart_str_append_long(buf, (long)hash_key->h);
        } else {
            char *key, *tmp_str;
            int key_len, tmp_len;

            key = php_addcslashes(hash_key->arKey, hash_key->nKeyLength-1,
                                  &key_len, 0, "\"\\", 2 TSRMLS_CC);
            tmp_str = php_str_to_str_ex(key, key_len, "\0", 1,
                                        "\" . '\\0' . \"", 12,
                                        &tmp_len, 0, NULL);
            smart_str_appendc(buf, '"');
            smart_str_appendl(buf, tmp_str, tmp_len);
            smart_str_appendc(buf, '"');

            efree(key);
            efree(tmp_str);
        }

        smart_str_appendl(buf, ": ", 2);
    }

    elog_var_dump(zv, level+2, buf, ELOG_MODE_DUMP TSRMLS_CC);

    smart_str_appendl(buf, PHP_EOL, strlen(PHP_EOL));

    return 0;
}

static inline int
elog_object_element_dump(zval **zv TSRMLS_DC, int num_args,
                         va_list args, zend_hash_key *hash_key)
{
    int level;
    smart_str *buf;

    level = va_arg(args, int);
    buf = va_arg(args, smart_str *);

    ELOG_BUFFER_APPEND_SPACES(buf, level+1);

    if (hash_key->nKeyLength == 0) {
        smart_str_append_long(buf, (long)hash_key->h);
    } else {
        const char *class_name;
        const char *pname;
        char *pname_esc;
        int  pname_esc_len;

        zend_unmangle_property_name(hash_key->arKey, hash_key->nKeyLength-1,
                                    &class_name, &pname);
        pname_esc = php_addcslashes(pname, strlen(pname), &pname_esc_len, 0,
                                    "\"\\", 2 TSRMLS_CC);

        smart_str_appendc(buf, '"');
        smart_str_appendl(buf, pname_esc, pname_esc_len);
        smart_str_appendc(buf, '"');

        efree(pname_esc);
    }

    smart_str_appendl(buf, ": ", 2);

    elog_var_dump(zv, level+2, buf, ELOG_MODE_DUMP TSRMLS_CC);

    smart_str_appendl(buf, PHP_EOL, strlen(PHP_EOL));

    return 0;
}

static inline void
elog_var_dump(zval **struc, int level, smart_str *buf, int mode TSRMLS_DC)
{
    HashTable *myht;
#if ZEND_MODULE_API_NO >= 20100525
    const char *class_name;
#else
    char *class_name;
#endif
    zend_uint class_name_len;
    int (*elog_dump_func)(zval** TSRMLS_DC, int, va_list, zend_hash_key*);
    int is_temp;
    char *tmp_str, *tmp_str2;
    int tmp_len, tmp_len2;
    int array_type;

    switch (Z_TYPE_PP(struc)) {
        case IS_BOOL:
            if (mode == ELOG_MODE_CONVERT) {
                if (Z_LVAL_PP(struc)) {
                    smart_str_append_long(buf, 1);
                } else {
                    smart_str_append_long(buf, 0);
                }
            } else {
                if (Z_LVAL_PP(struc)) {
                    smart_str_appendl(buf, "true", 4);
                } else {
                    smart_str_appendl(buf, "false", 5);
                }
            }
            break;
        case IS_NULL:
            if (mode != ELOG_MODE_CONVERT) {
                smart_str_appendl(buf, "NULL", 4);
            }
            break;
        case IS_LONG:
            smart_str_append_long(buf, Z_LVAL_PP(struc));
            break;
        case IS_DOUBLE:
            tmp_len = spprintf(&tmp_str, 0,"%.*G",
                               (int)EG(precision), Z_DVAL_PP(struc));
            smart_str_appendl(buf, tmp_str, tmp_len);
            efree(tmp_str);
            break;
        case IS_STRING:
            if (mode == ELOG_MODE_CONVERT) {
                smart_str_appendl(buf, Z_STRVAL_PP(struc), Z_STRLEN_PP(struc));
            } else {
                tmp_str = php_addcslashes(Z_STRVAL_PP(struc), Z_STRLEN_PP(struc),
                                          &tmp_len, 0, "\"\\", 2 TSRMLS_CC);
                tmp_str2 = php_str_to_str_ex(tmp_str, tmp_len, "\0", 1,
                                             "\" . '\\0' . \"", 12,
                                             &tmp_len2, 0, NULL);
                smart_str_appendc(buf, '"');
                smart_str_appendl(buf, tmp_str2, tmp_len2);
                smart_str_appendc(buf, '"');
                efree(tmp_str2);
                efree(tmp_str);
            }
            break;
        case IS_ARRAY:
            array_type = elog_array_type(*struc TSRMLS_CC);
            myht = Z_ARRVAL_PP(struc);
            if (++myht->nApplyCount > 1) {
                smart_str_appendl(buf, "*RECURSION*", 11);
                --myht->nApplyCount;
                return;
            }
            if (array_type == ELOG_TYPE_ARRAY) {
                smart_str_appendl(buf, "["PHP_EOL, 1 + strlen(PHP_EOL));
            } else {
                smart_str_appendl(buf, "{"PHP_EOL, 1 + strlen(PHP_EOL));
            }
            elog_dump_func = elog_array_element_dump;
            is_temp = 0;
            goto head_done;
        case IS_OBJECT:
            array_type = ELOG_TYPE_ASSOC;
#if ZEND_MODULE_API_NO >= 20090626
            myht = Z_OBJDEBUG_PP(struc, is_temp);
#else
            myht = Z_OBJPROP_PP(struc);
#endif
            if (myht && ++myht->nApplyCount > 1) {
                smart_str_appendl(buf, "*RECURSION*", 11);
                --myht->nApplyCount;
                return;
            }
            if (Z_OBJ_HANDLER(**struc, get_class_name)) {
                Z_OBJ_HANDLER(**struc, get_class_name)(*struc,
                                                       &class_name,
                                                       &class_name_len,
                                                       0 TSRMLS_CC);
                smart_str_appendl(buf, class_name, class_name_len);
                smart_str_appendl(buf, " {"PHP_EOL, 2 + strlen(PHP_EOL));
                efree((char*)class_name);
            } else {
                smart_str_appendl(buf, "{"PHP_EOL, 1 + strlen(PHP_EOL));
            }
            elog_dump_func = elog_object_element_dump;
        head_done:
            if (myht) {
#if ZEND_MODULE_API_NO >= 20090626
                zend_hash_apply_with_arguments(myht TSRMLS_CC,
                                               (apply_func_args_t)elog_dump_func,
                                               3, level, buf, array_type);
#else
                zend_hash_apply_with_arguments(myht,
                                               (apply_func_args_t)elog_dump_func,
                                               3, level, buf, array_type);
#endif
                --myht->nApplyCount;
                if (is_temp) {
                    zend_hash_destroy(myht);
                    efree(myht);
                }
            }
            if (level > 1) {
                ELOG_BUFFER_APPEND_SPACES(buf, level-1);
            }
            if (array_type == ELOG_TYPE_ARRAY) {
                smart_str_appendl(buf, "]", 1);
            } else {
                smart_str_appendl(buf, "}", 1);
            }
            break;
        case IS_RESOURCE: {
            const char *type_name;
            type_name = zend_rsrc_list_get_rsrc_type(Z_LVAL_PP(struc) TSRMLS_CC);
            if (type_name) {
                smart_str_appendl(buf, "resource of type(", 17);
                smart_str_appendl(buf, type_name, strlen(type_name));
                smart_str_appendc(buf, ')');
            } else {
                smart_str_appendl(buf, "resource of type(Unknown)", 25);
            }
            break;
        }
        default:
            smart_str_appendl(buf, "UNKNOWN:0", 9);
            break;
    }
}

static inline int elog_var_json(zval **struc, json_t *obj,
                                int json_type TSRMLS_DC);

static inline void
elog_json_array_append(json_t *obj, zval *val TSRMLS_DC)
{
    json_t *element;
    int json_type;

    switch (Z_TYPE_P(val)) {
        case IS_BOOL:
            json_array_append_new(obj, json_boolean(Z_LVAL_P(val)));
            break;
        case IS_NULL:
            json_array_append_new(obj, json_null());
            break;
        case IS_LONG:
            json_array_append_new(obj, json_integer(Z_LVAL_P(val)));
            break;
        case IS_DOUBLE: {
            double dbl = Z_DVAL_P(val);
            if (!zend_isinf(dbl) && !zend_isnan(dbl)) {
                json_array_append_new(obj, json_real(dbl));
            } else {
                elog_err(E_WARNING, "Inf and Nan cannot be JSON encoded");
                json_array_append_new(obj, json_integer(0));
            }
            break;
        }
        case IS_STRING:
            json_array_append_new(obj, json_string(Z_STRVAL_P(val)));
            break;
        case IS_ARRAY:
            json_type = elog_array_type(val TSRMLS_CC);
            if (json_type == ELOG_TYPE_ARRAY) {
                element = json_array();
            } else {
                element = json_object();
            }
            if (!element) {
                return;
            }
            if (elog_var_json(&val, element, json_type TSRMLS_CC) == 0) {
                json_array_append_new(obj, element);
            } else {
                json_array_append_new(obj, json_null());
                json_delete(element);
            }
            break;
        case IS_OBJECT:
            json_type = ELOG_TYPE_ASSOC;
            element = json_object();
            if (!element) {
                return;
            }
            if (elog_var_json(&val, element, json_type TSRMLS_CC) == 0) {
                json_array_append_new(obj, element);
            } else {
                json_array_append_new(obj, json_null());
                json_delete(element);
            }
            break;
        default:
            elog_err(E_WARNING, "Type is not supported");
            json_array_append_new(obj, json_null());
            break;
    }
}

static inline void
elog_json_object_append(json_t *obj, char *key, zval *val TSRMLS_DC)
{
    json_t *element;
    int json_type;

    switch (Z_TYPE_P(val)) {
        case IS_BOOL:
            json_object_set_new(obj, key, json_boolean(Z_LVAL_P(val)));
            break;
        case IS_NULL:
            json_object_set_new(obj, key, json_null());
            break;
        case IS_LONG:
            json_object_set_new(obj, key, json_integer(Z_LVAL_P(val)));
            break;
        case IS_DOUBLE: {
            double dbl = Z_DVAL_P(val);
            if (!zend_isinf(dbl) && !zend_isnan(dbl)) {
                json_object_set_new(obj, key, json_real(dbl));
            } else {
                elog_err(E_WARNING, "Inf and Nan cannot be JSON encoded");
                json_object_set_new(obj, key, json_integer(0));
            }
            break;
        }
        case IS_STRING:
            json_object_set_new(obj, key, json_string(Z_STRVAL_P(val)));
            break;
        case IS_ARRAY:
            json_type = elog_array_type(val TSRMLS_CC);
            if (json_type == ELOG_TYPE_ARRAY) {
                element = json_array();
            } else {
                element = json_object();
            }
            if (!element) {
                return;
            }
            if (elog_var_json(&val, element, json_type TSRMLS_CC) == 0) {
                json_object_set_new(obj, key, element);
            } else {
                json_object_set_new(obj, key, json_null());
                json_delete(element);
            }
            break;
        case IS_OBJECT:
            element = json_object();
            json_type = ELOG_TYPE_ASSOC;
            if (!element) {
                return;
            }
            if (elog_var_json(&val, element, json_type TSRMLS_CC) == 0) {
                json_object_set_new(obj, key, element);
            } else {
                json_object_set_new(obj, key, json_null());
                json_delete(element);
            }
            break;
        default:
            elog_err(E_WARNING, "Type is not supported");
            json_object_set_new(obj, key, json_null());
            break;
    }
}

static inline int
elog_var_json(zval **struc, json_t *obj, int json_type TSRMLS_DC)
{
    HashTable *myht;
    int n = 0;

    if (Z_TYPE_PP(struc) == IS_ARRAY) {
        myht = Z_ARRVAL_PP(struc);
    } else {
        myht = Z_OBJPROP_PP(struc);
    }

    if (myht && myht->nApplyCount > 1) {
        elog_err(E_WARNING, "Recursion detected");
        return -1;
    }

    if (myht) {
        n = zend_hash_num_elements(myht);
    }

    if (n > 0) {
        zval **data;
        char *str_key;
        uint str_key_len;
        ulong num_key;
        HashPosition pos;
        HashTable *tmp_ht;

        zend_hash_internal_pointer_reset_ex(myht, &pos);
        for (;; zend_hash_move_forward_ex(myht, &pos)) {
            n = zend_hash_get_current_key_ex(myht, &str_key, &str_key_len,
                                             &num_key, 0, &pos);
            if (n == HASH_KEY_NON_EXISTANT) {
                break;
            }

            if (zend_hash_get_current_data_ex(myht,
                                              (void **)&data,
                                              &pos) == SUCCESS) {
                tmp_ht = HASH_OF(*data);
                if (tmp_ht) {
                    tmp_ht->nApplyCount++;
                }

                if (json_type == ELOG_TYPE_ARRAY) {
                    elog_json_array_append(obj, *data TSRMLS_CC);
                } else {
                    if (n == HASH_KEY_IS_STRING) {
                        if (str_key[0] == '\0' &&
                            Z_TYPE_PP(struc) == IS_OBJECT) {
                            if (tmp_ht) {
                                tmp_ht->nApplyCount--;
                            }
                            continue;
                        }
                        elog_json_object_append(obj, str_key, *data TSRMLS_CC);
                    } else {
                        char *tmp_str;
                        spprintf(&tmp_str, 0, "%ld", (long)num_key);
                        elog_json_object_append(obj, tmp_str, *data TSRMLS_CC);
                        efree(tmp_str);
                    }
                }

                if (tmp_ht) {
                    tmp_ht->nApplyCount--;
                }
            }
        }
    }

    return 0;
}

PHP_ELOG_API void
php_elog_filter_data_dtor(php_elog_filter_data_t *data)
{
    if (data && data->function && data->dtor) {
        zval_ptr_dtor(&data->function);
    }
}

static inline int
elog_filter_data_append(char *name, int len TSRMLS_DC)
{
    php_elog_filter_data_t *pdata = NULL;

    if (zend_hash_exists(ELOG_G(filter).enabled, name, len+1)) {
        elog_err(E_WARNING, "Already exists filter \"%s\"", name);
        return FAILURE;
    }

    if (ELOG_G(filter).registers &&
        zend_hash_find(ELOG_G(filter).registers, name, len+1,
                       (void **)&pdata) == SUCCESS) {
        Z_ADDREF_P(pdata->function);
        if (zend_hash_add(ELOG_G(filter).enabled, name, len+1,
                          pdata, sizeof(*pdata), NULL) == SUCCESS) {
            return SUCCESS;
        }
        Z_DELREF_P(pdata->function);
    }

    if (zend_hash_exists(EG(function_table), name, len+1)) {
        php_elog_filter_data_t data = { 1, NULL };

        MAKE_STD_ZVAL(data.function);
        ZVAL_STRINGL(data.function, name, len, 1);

        if (zend_hash_add(ELOG_G(filter).enabled, name, len+1,
                          &data, sizeof(php_elog_filter_data_t),
                          NULL) == SUCCESS) {
            return SUCCESS;
        }
        zval_ptr_dtor(&data.function);
    }

    elog_err(E_WARNING, "No such filter \"%s\"", name);

    return FAILURE;
}

static inline int
elog_filter_data_prepend(char *name, int len TSRMLS_DC)
{
    int is_dtor = 0;
    php_elog_filter_data_t *pdata = NULL;

    if (zend_hash_exists(ELOG_G(filter).enabled, name, len+1)) {
        elog_err(E_WARNING, "Already exists filter \"%s\"", name);
        return FAILURE;
    }

    if (ELOG_G(filter).registers &&
        zend_hash_find(ELOG_G(filter).registers, name, len+1,
                       (void **)&pdata) == SUCCESS) {
        Z_ADDREF_P(pdata->function);
    }
    if (pdata == NULL &&
        zend_hash_exists(EG(function_table), name, len+1)) {
        php_elog_filter_data_t data = { 1, NULL };
        MAKE_STD_ZVAL(data.function);
        ZVAL_STRINGL(data.function, name, len, 1);
        pdata = &data;
        is_dtor = 1;
    }

    if (pdata == NULL) {
        elog_err(E_WARNING, "No such filter \"%s\"", name);
        return FAILURE;
    }

    if (zend_hash_num_elements(ELOG_G(filter).enabled) == 0) {
        if (zend_hash_add(ELOG_G(filter).enabled, name, len+1,
                          (void *)pdata, sizeof(*pdata), NULL) != SUCCESS) {
            if (is_dtor) {
                zval_ptr_dtor(&pdata->function);
            } else {
                Z_DELREF_P(pdata->function);
            }
            elog_err(E_WARNING, "Already exists filter \"%s\"", name);
            return FAILURE;
        }
    } else {
        php_elog_filter_data_t *val;
        HashTable *retval;
        HashPosition pos;
        char *str_key;
        uint str_key_len;
        ulong num_key;
        size_t n = zend_hash_num_elements(ELOG_G(filter).enabled);

        ALLOC_HASHTABLE(retval);
        zend_hash_init(retval, n > 5 ? n : 5, NULL,
                       (dtor_func_t)php_elog_filter_data_dtor, 0);

        if (zend_hash_add(retval, name, len+1,
                          (void *)pdata, sizeof(*pdata), NULL) != SUCCESS) {
            if (is_dtor) {
                zval_ptr_dtor(&pdata->function);
            } else {
                Z_DELREF_P(pdata->function);
                elog_err(E_WARNING, "Already exists filter \"%s\"", name);
            }
        }

        zend_hash_internal_pointer_reset_ex(ELOG_G(filter).enabled, &pos);
        while (zend_hash_get_current_data_ex(ELOG_G(filter).enabled,
                                             (void **)&val, &pos) == SUCCESS) {
            if (zend_hash_get_current_key_ex(ELOG_G(filter).enabled,
                                             &str_key, &str_key_len,
                                             &num_key, 1,
                                             &pos) == HASH_KEY_IS_STRING) {
                Z_ADDREF_P(val->function);
                zend_hash_add(retval, str_key, str_key_len,
                              (void *)val, sizeof(*val), NULL);
                efree(str_key);
            }
            zend_hash_move_forward_ex(ELOG_G(filter).enabled, &pos);
        }
        zend_hash_destroy(ELOG_G(filter).enabled);
        efree(ELOG_G(filter).enabled);
        ELOG_G(filter).enabled = retval;
    }

    return SUCCESS;
}

ZEND_FUNCTION(elog_register_filter)
{
    char *function_name = NULL;
    char *name = NULL;
    long name_len = 0, enabled_mode = 0;
    php_elog_filter_data_t data = { 1, NULL };

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sz|l",
                              &name, &name_len, &data.function,
                              &enabled_mode) == FAILURE) {
        return;
    }

    RETVAL_FALSE;

    if (!name || name_len == 0) {
        elog_err(E_WARNING, "Filter name cannot be empty");
        return;
    }

    if (!zend_is_callable(data.function, 0, &function_name TSRMLS_CC)) {
        elog_err(E_WARNING, "Invalid callback function '%s'", function_name);
        if (function_name) {
            efree(function_name);
        }
        return;
    }

    Z_ADDREF_P(data.function);

    if (zend_hash_add(ELOG_G(filter).registers, name, name_len+1,
                      &data, sizeof(php_elog_filter_data_t), NULL) == SUCCESS) {
        RETVAL_TRUE;
    } else {
        zval_ptr_dtor(&data.function);
        enabled_mode = 0;
    }

    if (enabled_mode == EL_FILTER_APPEND) {
        elog_filter_data_append(name, name_len TSRMLS_CC);
    } else if (enabled_mode == EL_FILTER_PREPEND) {
        elog_filter_data_prepend(name, name_len TSRMLS_CC);
    }

    if (function_name) {
        efree(function_name);
    }
}

ZEND_FUNCTION(elog_append_filter)
{
    zval *name;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z", &name) == FAILURE) {
        RETURN_FALSE;
    }

    RETVAL_FALSE;

    if (Z_TYPE_P(name) == IS_STRING) {
        if (elog_filter_data_append(Z_STRVAL_P(name),
                                    Z_STRLEN_P(name) TSRMLS_CC) != SUCCESS) {
            return;
        }
    } else if (Z_TYPE_P(name) == IS_ARRAY) {
        zval **val;
        HashPosition pos;
        HashTable *myht = HASH_OF(name);

        if (zend_hash_num_elements(myht) == 0) {
            elog_err(E_WARNING, "No such filter \"Array(empty)\"");
            return;
        }

        zend_hash_internal_pointer_reset_ex(myht, &pos);
        while (zend_hash_get_current_data_ex(myht, (void **)&val,
                                             &pos) == SUCCESS) {
            if (Z_TYPE_PP(val) == IS_STRING) {
                elog_filter_data_append(Z_STRVAL_PP(val),
                                        Z_STRLEN_PP(val) TSRMLS_CC);
            }
            zend_hash_move_forward_ex(myht, &pos);
        }
    } else {
        elog_err(E_WARNING, "Invalid arguments");
        return;
    }

    RETVAL_TRUE;
}

ZEND_FUNCTION(elog_prepend_filter)
{
    zval *name;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z", &name) == FAILURE) {
        RETURN_FALSE;
    }

    RETVAL_FALSE;

    if (Z_TYPE_P(name) == IS_STRING) {
        if (elog_filter_data_prepend(Z_STRVAL_P(name),
                                     Z_STRLEN_P(name) TSRMLS_CC) != SUCCESS) {
            return;
        }
    } else if (Z_TYPE_P(name) == IS_ARRAY) {
        zval **val;
        php_elog_filter_data_t *filter;
        HashPosition pos;
        HashTable *retval;
        char *str_key;
        uint str_key_len;
        ulong num_key;
        size_t n;
        zend_bool add_only = 0;
        zend_bool is_dtor = 0;
        HashTable *myht = HASH_OF(name);

        if (zend_hash_num_elements(myht) == 0) {
            elog_err(E_WARNING, "No such filter \"Array(empty)\"");
            return;
        }

        if (zend_hash_num_elements(ELOG_G(filter).enabled) == 0) {
            add_only = 1;
        }

        n = zend_hash_num_elements(ELOG_G(filter).enabled);

        ALLOC_HASHTABLE(retval);
        zend_hash_init(retval, n > 5 ? n : 5, NULL,
                       (dtor_func_t)php_elog_filter_data_dtor, 0);

        zend_hash_internal_pointer_reset_ex(myht, &pos);
        while (zend_hash_get_current_data_ex(myht, (void **)&val,
                                             &pos) == SUCCESS) {
            if (Z_TYPE_PP(val) == IS_STRING) {
                if (ELOG_G(filter).registers) {
                    if (zend_hash_find(ELOG_G(filter).registers,
                                       Z_STRVAL_PP(val), Z_STRLEN_PP(val)+1,
                                       (void **)&filter) == FAILURE) {
                        filter = NULL;
                    }
                }
                if (filter == NULL) {
                    if (zend_hash_exists(EG(function_table),
                                         Z_STRVAL_PP(val),
                                         Z_STRLEN_PP(val)+1)) {
                        php_elog_filter_data_t data = { 1, NULL };
                        MAKE_STD_ZVAL(data.function);
                        ZVAL_STRINGL(data.function,
                                     Z_STRVAL_PP(val), Z_STRLEN_PP(val), 1);
                        filter = &data;
                        is_dtor = 1;
                    }
                }
                if (filter == NULL) {
                    continue;
                }

                if (add_only) {
                    if (zend_hash_add(ELOG_G(filter).enabled,
                                      Z_STRVAL_PP(val), Z_STRLEN_PP(val)+1,
                                      (void *)filter, sizeof(*filter),
                                      NULL) == FAILURE) {
                        elog_err(E_WARNING, "Already exists filter \"%s\"",
                                 Z_STRVAL_PP(val));
                        if (is_dtor) {
                            zval_ptr_dtor(&filter->function);
                        }
                    }
                } else {
                    zend_hash_add(retval,
                                  Z_STRVAL_PP(val), Z_STRLEN_PP(val)+1,
                                  (void *)filter, sizeof(*filter),
                                  NULL);
                    Z_ADDREF_P(filter->function);
                }
            }
            zend_hash_move_forward_ex(myht, &pos);
        }

        zend_hash_internal_pointer_reset_ex(ELOG_G(filter).enabled, &pos);
        while (zend_hash_get_current_data_ex(ELOG_G(filter).enabled,
                                             (void **)&filter,
                                             &pos) == SUCCESS) {
            if (zend_hash_get_current_key_ex(ELOG_G(filter).enabled,
                                             &str_key, &str_key_len, &num_key,
                                             1, &pos) == HASH_KEY_IS_STRING) {
                if (!zend_hash_exists(retval, str_key, str_key_len)) {
                    zend_hash_add(retval, str_key, str_key_len,
                                  (void *)filter, sizeof(*filter), NULL);
                    Z_ADDREF_P(filter->function);
                }
                efree(str_key);
            }
            zend_hash_move_forward_ex(ELOG_G(filter).enabled, &pos);
        }

        zend_hash_destroy(ELOG_G(filter).enabled);
        efree(ELOG_G(filter).enabled);

        ELOG_G(filter).enabled = retval;
    } else {
        elog_err(E_WARNING, "Invalid arguments");
        return;
    }

    RETVAL_TRUE;
}

ZEND_FUNCTION(elog_remove_filter)
{
    char *name = NULL;
    int name_len = 0;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "s", &name, &name_len) == FAILURE) {
        RETURN_FALSE;
    }

    RETVAL_FALSE;

    if (!name || name_len == 0) {
        elog_err(E_WARNING, "Filter name cannot be empty");
        return;
    }

    if (ELOG_G(filter).enabled) {
        if (zend_hash_del(ELOG_G(filter).enabled,
                          name, name_len+1) == SUCCESS) {
            RETVAL_TRUE;
        }
    }
}

#define ELOG_BEGIN_FILTER_GET(_name)                      \
    if (filter == NULL || strcmp(filter, # _name) == 0) { \
        if (filter == NULL) {                             \
            MAKE_STD_ZVAL(arr);                           \
            array_init(arr);                              \
        } else {                                          \
            arr = return_value;                           \
        }

#define ELOG_END_FILTER_GET(_name)                                          \
        if (return_value != arr) {                                          \
            add_assoc_zval_ex(return_value, # _name, sizeof(# _name), arr); \
        }                                                                   \
    }

#define ELOG_FILTER_GET_TABLE(_ht)                                        \
    zend_hash_internal_pointer_reset_ex(_ht, &pos);                       \
    do {                                                                  \
        int n = zend_hash_get_current_key_ex(_ht, &str_key, &str_key_len, \
                                             &num_key, 1, &pos);          \
        if (n == HASH_KEY_IS_STRING) {                                    \
            MAKE_STD_ZVAL(val);                                           \
            ZVAL_STRINGL(val, str_key, str_key_len - 1, 1);               \
            add_next_index_zval(arr, val);                                \
            efree(str_key);                                               \
        } else if (n == HASH_KEY_IS_LONG) {                               \
            MAKE_STD_ZVAL(val);                                           \
            Z_TYPE_P(val) = IS_LONG;                                      \
            Z_LVAL_P(val) = num_key;                                      \
            add_next_index_zval(arr, val);                                \
        } else {                                                          \
            break;                                                        \
        }                                                                 \
        zend_hash_move_forward_ex(ELOG_G(filter).enabled, &pos);          \
    } while (1)


#define ELOG_FILTER_GET_EXECUTE(_ht)                            \
    if (ELOG_G(exec_filter)) {                                  \
        char *str, *token;                                      \
        spprintf(&str, 0, "%s", ELOG_G(exec_filter));           \
        token = str;                                            \
        while (1) {                                             \
            int len;                                            \
            char *name = strtok(token, ",");                    \
            if (name == NULL) {                                 \
                break;                                          \
            }                                                   \
            while (*name == ' ') {                              \
                name++;                                         \
            }                                                   \
            len = strlen(name) - 1;                             \
            while (*(name+len) == ' ' && len > 0) {             \
                *(name+len) = '\0';                             \
                len--;                                          \
            }                                                   \
            if (_ht == NULL ||                                  \
                !zend_hash_exists(_ht, name, strlen(name)+1)) { \
                MAKE_STD_ZVAL(val);                             \
                ZVAL_STRINGL(val, name, strlen(name), 1);       \
                add_next_index_zval(arr, val);                  \
            }                                                   \
            token = NULL;                                       \
        }                                                       \
        efree(str);                                             \
    }

ZEND_FUNCTION(elog_get_filter)
{
    char *filter = NULL;
    long filter_len = 0;
    zval *val, *arr;
    char *str_key;
    uint str_key_len;
    ulong num_key;
    HashPosition pos;
    int i, buitin_count = 9;
    char *builtin_name[9] = { "elog_filter_to_string",
                              "elog_filter_to_json",
                              "elog_filter_to_http_query",
                              "elog_filter_to_array",
                              "elog_filter_add_eol",
                              "elog_filter_add_fileline",
                              "elog_filter_add_timestamp",
                              "elog_filter_add_request",
                              "elog_filter_add_level" };

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "|s", &filter, &filter_len) == FAILURE) {
        return;
    }

    array_init(return_value);

    ELOG_BEGIN_FILTER_GET(builtin);
    for (i = 0; i < buitin_count; i++) {
        MAKE_STD_ZVAL(val);
        ZVAL_STRINGL(val, builtin_name[i], strlen(builtin_name[i]), 1);
        add_next_index_zval(arr, val);
    }
    ELOG_END_FILTER_GET(builtin);

    ELOG_BEGIN_FILTER_GET(registers);
    ELOG_FILTER_GET_TABLE(ELOG_G(filter).registers);
    ELOG_END_FILTER_GET(registers);

    ELOG_BEGIN_FILTER_GET(execute);
    ELOG_FILTER_GET_EXECUTE(NULL);
    ELOG_END_FILTER_GET(execute);

    ELOG_BEGIN_FILTER_GET(enabled);
    ELOG_FILTER_GET_TABLE(ELOG_G(filter).enabled);
    ELOG_FILTER_GET_EXECUTE(ELOG_G(filter).enabled);
    ELOG_END_FILTER_GET(enabled);
}

ZEND_FUNCTION(elog_filter_to_string)
{
    zval *msg;
    long level = EL_LEVEL_ALL;
    smart_str buf = {0};

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    if (Z_TYPE_P(msg) != IS_STRING) {
        elog_var_dump(&msg, 1, &buf, ELOG_MODE_DUMP TSRMLS_CC);
    } else {
        smart_str_appendl(&buf, Z_STRVAL_P(msg), Z_STRLEN_P(msg));
    }

    /*
    if (buf.c && buf.c[buf.len-1] != '\n') {
        smart_str_appendl(&buf, PHP_EOL, strlen(PHP_EOL));
    }
    */
    smart_str_0(&buf);

    RETVAL_STRINGL(buf.c, buf.len, 1);

    smart_str_free(&buf);
}

ZEND_FUNCTION(elog_filter_to_array)
{
    zval *msg;
    long level = EL_LEVEL_ALL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    if (Z_TYPE_P(msg) == IS_ARRAY) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
    } else if (Z_TYPE_P(msg) == IS_OBJECT) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        convert_to_array(return_value);
    } else {
        Z_ADDREF_P(msg);
        array_init(return_value);
        if (ELOG_G(array_assoc)) {
            char *label_scalar = ELOG_G(label_scalar);
            if (!label_scalar || strlen(label_scalar) <= 0) {
                label_scalar = EL_LABEL_SCALAR;
            }
            add_assoc_zval_ex(return_value, label_scalar,
                              strlen(label_scalar)+1, msg);
        } else {
            add_next_index_zval(return_value, msg);
        }
    }
}

ZEND_FUNCTION(elog_filter_to_json)
{
    zval *msg;
    long level = EL_LEVEL_ALL;
    json_t *obj = NULL;
    char *tmp_str, *label_scalar;
    int json_type = ELOG_TYPE_ASSOC;
    int json_flags = JSON_COMPACT | JSON_ENCODE_ANY | JSON_PRESERVE_ORDER;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    label_scalar = ELOG_G(label_scalar);
    if (!label_scalar || strlen(label_scalar) <= 0) {
        label_scalar = EL_LABEL_SCALAR;
    }

    obj = json_object();
    if (!obj) {
        return;
    }

    switch (Z_TYPE_P(msg)) {
        case IS_BOOL:
            json_object_set_new(obj, label_scalar, json_boolean(Z_LVAL_P(msg)));
            break;
        case IS_NULL:
            json_object_set_new(obj, label_scalar, json_null());
            break;
        case IS_LONG:
            json_object_set_new(obj, label_scalar, json_integer(Z_LVAL_P(msg)));
            break;
        case IS_DOUBLE: {
            double dbl = Z_DVAL_P(msg);
            if (!zend_isinf(dbl) && !zend_isnan(dbl)) {
                json_object_set_new(obj, label_scalar, json_real(dbl));
            } else {
                elog_err(E_WARNING, "Inf and Nan cannot be JSON encoded");
                json_object_set_new(obj, label_scalar, json_integer(0));
            }
            break;
        }
        case IS_STRING:
            json_object_set_new(obj, label_scalar, json_string(Z_STRVAL_P(msg)));
            break;
        case IS_ARRAY:
            if (!ELOG_G(json_assoc)) {
                json_type = elog_array_type(msg TSRMLS_CC);
                if (json_type == ELOG_TYPE_ARRAY) {
                    json_delete(obj);
                    obj = json_array();
                    if (!obj) {
                        return;
                    }
                }
            }
        case IS_OBJECT:
            elog_var_json(&msg, obj, json_type TSRMLS_CC);
            break;
        default:
            elog_err(E_WARNING, "Type is not supported");
            json_object_set_new(obj, label_scalar, json_null());
            break;
    }

    if (ELOG_G(json_unicode_escape)) {
        json_flags |= JSON_ENSURE_ASCII;
    }

    tmp_str = json_dumps(obj, json_flags);

    if (tmp_str) {
        RETVAL_STRING(tmp_str, 1);
        free(tmp_str);
    } else {
        RETVAL_STRINGL("{}", 2, 1);
    }

    json_delete(obj);
}

ZEND_FUNCTION(elog_filter_to_http_query)
{
    zval *msg;
    long level = EL_LEVEL_ALL;
    char *label_scalar;
    smart_str buf = {0};

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    label_scalar = ELOG_G(label_scalar);
    if (!label_scalar || strlen(label_scalar) <= 0) {
        label_scalar = EL_LABEL_SCALAR;
    }

    switch (Z_TYPE_P(msg)) {
        case IS_BOOL:
            smart_str_appendl(&buf, label_scalar, strlen(label_scalar));
            if (Z_LVAL_P(msg)) {
                smart_str_appendl(&buf, "=1", 2);
            } else {
                smart_str_appendl(&buf, "=0", 2);
            }
            break;
        case IS_LONG:
        case IS_DOUBLE:
            convert_to_string(msg);
        case IS_STRING: {
            char *str;
            int str_len;

            if (ELOG_G(http_encode) == PHP_QUERY_RFC3986) {
                str = php_raw_url_encode(Z_STRVAL_P(msg), Z_STRLEN_P(msg),
                                         &str_len);
            } else {
                str = php_url_encode(Z_STRVAL_P(msg), Z_STRLEN_P(msg), &str_len);
            }

            smart_str_appendl(&buf, label_scalar, strlen(label_scalar));
            smart_str_appendl(&buf, "=", 1);
            smart_str_appendl(&buf, str, str_len);
            break;
        }
        case IS_ARRAY:
        case IS_OBJECT: {
            char *separator = ELOG_G(http_separator);
            long encode = ELOG_G(http_encode);

            if (!encode) {
                encode = PHP_QUERY_RFC1738;
            }

            php_url_encode_hash_ex(HASH_OF(msg), &buf,
                                   NULL, 0, NULL, 0, NULL, 0,
                                   (Z_TYPE_P(msg) == IS_OBJECT ? msg : NULL),
                                   separator, encode TSRMLS_CC);
            break;
        }
        case IS_NULL:
        default:
            break;
    }

    smart_str_0(&buf);

    RETVAL_STRINGL(buf.c, buf.len, 1);

    smart_str_free(&buf);
}

#define ELOG_BEGIN_IS_JSON_STRING(_buf)                                     \
    if (_buf.c && _buf.c[0] == '{' && _buf.c[_buf.len-1] == '}') {          \
        json_t *json = json_loadb(_buf.c, _buf.len, JSON_DECODE_ANY, NULL); \
        int json_flags = JSON_COMPACT|JSON_ENCODE_ANY|JSON_PRESERVE_ORDER;  \
        char *json_str;                                                     \
        if (json) {                                                         \
            smart_str_free(&_buf)

#define ELOG_END_IS_JSON_STRING(_ret) \
            json_delete(json);        \
            if (_ret) {               \
                return;               \
            }                         \
        }                             \
    }

#define ELOG_JSON_SET(_key, _val) json_object_set_new(json, _key, _val)

#define ELOG_JSON_RETVAL()                   \
    if (ELOG_G(json_unicode_escape)) {       \
        json_flags |= JSON_ENSURE_ASCII;     \
    }                                        \
    json_str = json_dumps(json, json_flags); \
    if (json_str) {                          \
        RETVAL_STRING(json_str, 1);          \
        free(json_str);                      \
    }

#define ELOG_HASH_ADD_STRINGL(_ht, _key, _str, _len, _duplicate)     \
    zval *zv;                                                        \
    MAKE_STD_ZVAL(zv);                                               \
    ZVAL_STRINGL(zv, _str, _len, _duplicate);                        \
    if (zend_hash_add(_ht, _key, strlen(_key)+1,                     \
                      (void *)&zv, sizeof(zval), NULL) != SUCCESS) { \
        zval_ptr_dtor(&zv);                                          \
    }

#define ELOG_HASH_ADD_LONG(_ht, _key, _val)                          \
    zval *zv;                                                        \
    MAKE_STD_ZVAL(zv);                                               \
    ZVAL_LONG(zv, _val);                                             \
    if (zend_hash_add(_ht, _key, strlen(_key)+1,                     \
                      (void *)&zv, sizeof(zval), NULL) != SUCCESS) { \
        zval_ptr_dtor(&zv);                                          \
    }

#define ELOG_HASH_ADD_NULL(_ht, _key)                                \
    zval *zv;                                                        \
    MAKE_STD_ZVAL(zv);                                               \
    ZVAL_NULL(zv);                                                   \
    if (zend_hash_add(_ht, _key, strlen(_key)+1,                     \
                      (void *)&zv, sizeof(zval), NULL) != SUCCESS) { \
        zval_ptr_dtor(&zv);                                          \
    }

#define ELOG_HASH_ADD_ZVAL(_ht, _key, _val) \
    zend_hash_add(_ht, _key, strlen(_key)+1, (void *)_val, sizeof(zval), NULL)

#define ELOG_ADD_EOL(_buf)                                  \
    if (!_buf.c || _buf.c[_buf.len-1] != '\n') {            \
        smart_str_appendl(&_buf, PHP_EOL, strlen(PHP_EOL)); \
    }

ZEND_FUNCTION(elog_filter_add_eol)
{
    zval *msg;
    long level = EL_LEVEL_ALL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    if (Z_TYPE_P(msg) == IS_ARRAY) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
    } else if (Z_TYPE_P(msg) == IS_OBJECT) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
    } else {
        smart_str buf = {0};

        elog_var_dump(&msg, 1, &buf, ELOG_MODE_CONVERT TSRMLS_CC);

        ELOG_ADD_EOL(buf);
        smart_str_0(&buf);

        RETVAL_STRINGL(buf.c, buf.len, 1);
        smart_str_free(&buf);
    }
}

ZEND_FUNCTION(elog_filter_add_fileline)
{
    zval *msg;
    long level = EL_LEVEL_ALL;
    char *filename = NULL, *label_file, *label_line;
    long lineno = 0;
    HashTable *ht_retval = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    if (zend_is_compiling(TSRMLS_C)) {
        filename = zend_get_compiled_filename(TSRMLS_C);
        lineno = zend_get_compiled_lineno(TSRMLS_C);
    } else if (zend_is_executing(TSRMLS_C)) {
        filename = (char *)zend_get_executed_filename(TSRMLS_C);
        lineno = zend_get_executed_lineno(TSRMLS_C);
    } else {
        filename = NULL;
        lineno = 0;
    }

    label_file = ELOG_G(label_file);
    if (!label_file || strlen(label_file) <= 0) {
        label_file = EL_LABEL_FILE;
    }
    label_line = ELOG_G(label_line);
    if (!label_line || strlen(label_line) <= 0) {
        label_line = EL_LABEL_LINE;
    }

    if (Z_TYPE_P(msg) == IS_ARRAY) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_ARRVAL_P(return_value);
    } else if (Z_TYPE_P(msg) == IS_OBJECT) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_OBJPROP_P(return_value);
    }

    if (ht_retval) {
        if (filename) {
            ELOG_HASH_ADD_STRINGL(ht_retval, label_file,
                                  filename, strlen(filename), 1);
        }
        if (lineno) {
            ELOG_HASH_ADD_LONG(ht_retval, label_line, lineno);
        }
    } else {
        smart_str buf = {0};

        elog_var_dump(&msg, 1, &buf, ELOG_MODE_CONVERT TSRMLS_CC);

        if (filename) {
            ELOG_BEGIN_IS_JSON_STRING(buf);
            ELOG_JSON_SET(label_file, json_string(filename));
            ELOG_JSON_SET(label_line, json_integer(lineno));
            ELOG_JSON_RETVAL();
            ELOG_END_IS_JSON_STRING(1);

            ELOG_ADD_EOL(buf);

            smart_str_appendl(&buf, label_file, strlen(label_file));
            smart_str_appendl(&buf, ": ", 2);
            smart_str_appendl(&buf, filename, strlen(filename));
            if (lineno) {
                smart_str_appendl(&buf, PHP_EOL, strlen(PHP_EOL));
                smart_str_appendl(&buf, label_line, strlen(label_line));
                smart_str_appendl(&buf, ": ", 2);
                smart_str_append_long(&buf, lineno);
            }
            /* smart_str_appendl(&buf, PHP_EOL, strlen(PHP_EOL)); */
        }

        smart_str_0(&buf);

        RETVAL_STRINGL(buf.c, buf.len, 1);

        smart_str_free(&buf);
    }
}

ZEND_FUNCTION(elog_filter_add_timestamp)
{
    zval *msg;
    long level = EL_LEVEL_ALL;
    char *timestamp = NULL, *label_timestamp;
    char *format = "d-M-Y H:i:s e";
    HashTable *ht_retval = NULL;
    time_t error_time;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    label_timestamp = ELOG_G(label_timestamp);
    if (!label_timestamp || strlen(label_timestamp) <= 0) {
        label_timestamp = EL_LABEL_TIMESTAMP;
    }

    time(&error_time);

    if (ELOG_G(timestamp_format) && strlen(ELOG_G(timestamp_format)) > 0) {
        format = ELOG_G(timestamp_format);
    }

    /*
#ifdef ZTS
    if (!php_during_module_startup()) {
        timestamp = php_format_date("d-M-Y H:i:s e", 13,
                                    error_time, 1 TSRMLS_CC);
    } else {
        timestamp = php_format_date("d-M-Y H:i:s e", 13,
                                    error_time, 0 TSRMLS_CC);
    }
#else
    timestamp = php_format_date("d-M-Y H:i:s e", 13,
                                error_time, 1 TSRMLS_CC);
#endif
    */

    timestamp = php_format_date(format, strlen(format), error_time, 1 TSRMLS_CC);

    if (Z_TYPE_P(msg) == IS_ARRAY) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_ARRVAL_P(return_value);
    } else if (Z_TYPE_P(msg) == IS_OBJECT) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_OBJPROP_P(return_value);
    }

    if (ht_retval) {
        if (timestamp) {
            ELOG_HASH_ADD_STRINGL(ht_retval, label_timestamp,
                                  timestamp, strlen(timestamp), 1);
        }
    } else {
        smart_str buf = {0};

        elog_var_dump(&msg, 1, &buf, ELOG_MODE_CONVERT TSRMLS_CC);

        if (timestamp) {
            ELOG_BEGIN_IS_JSON_STRING(buf);
            ELOG_JSON_SET(label_timestamp, json_string(timestamp));
            ELOG_JSON_RETVAL();
            efree(timestamp);
            ELOG_END_IS_JSON_STRING(1);

            ELOG_ADD_EOL(buf);

            smart_str_appendl(&buf, label_timestamp, strlen(label_timestamp));
            smart_str_appendl(&buf, ": ", 2);
            smart_str_appendl(&buf, timestamp, strlen(timestamp));
            /* smart_str_appendl(&buf, PHP_EOL, strlen(PHP_EOL)); */
        }

        smart_str_0(&buf);

        RETVAL_STRINGL(buf.c, buf.len, 1);

        smart_str_free(&buf);
    }

    if (timestamp) {
        efree(timestamp);
    }
}

ZEND_FUNCTION(elog_filter_add_request)
{
    zval *msg;
    long level = EL_LEVEL_ALL;
    char *label_request;
    HashTable *ht_retval = NULL;
    zval **request = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    label_request = ELOG_G(label_request);
    if (!label_request || strlen(label_request) <= 0) {
        label_request = EL_LABEL_REQUEST;
    }

    if (Z_TYPE_P(msg) == IS_ARRAY) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_ARRVAL_P(return_value);
    } else if (Z_TYPE_P(msg) == IS_OBJECT) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_OBJPROP_P(return_value);
    }

    if (zend_hash_find(&EG(symbol_table), "_REQUEST", sizeof("_REQUEST"),
                       (void **)&request) == SUCCESS) {
        if (ht_retval) {
            Z_ADDREF_PP(request);
            ELOG_HASH_ADD_ZVAL(ht_retval, label_request, request);
        } else {
            smart_str buf = {0};

            elog_var_dump(&msg, 1, &buf, ELOG_MODE_CONVERT TSRMLS_CC);

            ELOG_BEGIN_IS_JSON_STRING(buf);
            json_t *element = json_object();
            if (element) {
                if (elog_var_json(request, element,
                                  ELOG_TYPE_ASSOC TSRMLS_CC) == 0) {
                    json_object_set_new(json, label_request, element);
                } else {
                    json_delete(element);
                }
            }
            ELOG_JSON_RETVAL();
            ELOG_END_IS_JSON_STRING(1);

            ELOG_ADD_EOL(buf);

            smart_str_appendl(&buf, label_request, strlen(label_request));
            smart_str_appendl(&buf, ": ", 2);

            elog_var_dump(request, 1, &buf, ELOG_MODE_DUMP TSRMLS_CC);

            smart_str_0(&buf);

            RETVAL_STRINGL(buf.c, buf.len, 1);

            smart_str_free(&buf);
        }
    } else {
        if (ht_retval) {
            ELOG_HASH_ADD_NULL(ht_retval, label_request);
        } else {
            smart_str buf = {0};

            elog_var_dump(&msg, 1, &buf, ELOG_MODE_CONVERT TSRMLS_CC);

            ELOG_BEGIN_IS_JSON_STRING(buf);
            ELOG_JSON_SET(label_request, json_null());
            ELOG_JSON_RETVAL();
            ELOG_END_IS_JSON_STRING(1);

            ELOG_ADD_EOL(buf);

            smart_str_appendl(&buf, label_request, strlen(label_request));
            smart_str_appendl(&buf, ": NULL", 6);
            smart_str_0(&buf);

            RETVAL_STRINGL(buf.c, buf.len, 1);

            smart_str_free(&buf);
        }
    }
}

ZEND_FUNCTION(elog_filter_add_level)
{
    zval *msg;
    long level = EL_LEVEL_ALL;
    char *level_name = NULL, *label_level;
    HashTable *ht_retval = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|l", &msg, &level) == FAILURE) {
        return;
    }

    label_level = ELOG_G(label_level);
    if (!label_level || strlen(label_level) <= 0) {
        label_level = EL_LABEL_LEVEL;
    }

    switch (level) {
        case EL_LEVEL_EMERG:
            level_name = "EMERGE";
            break;
        case EL_LEVEL_ALERT:
            level_name = "ALERT";
            break;
        case EL_LEVEL_CRIT:
            level_name = "CRIT";
            break;
        case EL_LEVEL_ERR:
            level_name = "ERR";
            break;
        case EL_LEVEL_WARNING:
            level_name = "WARNING";
            break;
        case EL_LEVEL_NOTICE:
            level_name = "NOTICE";
            break;
        case EL_LEVEL_INFO:
            level_name = "INFO";
            break;
        case EL_LEVEL_DEBUG:
            level_name = "DEBUG";
            break;
        case EL_LEVEL_ALL:
        case EL_LEVEL_NONE:
        default:
            break;
    }

    if (Z_TYPE_P(msg) == IS_ARRAY) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_ARRVAL_P(return_value);
    } else if (Z_TYPE_P(msg) == IS_OBJECT) {
        COPY_PZVAL_TO_ZVAL(*return_value, msg);
        Z_ADDREF_P(msg);
        ht_retval = Z_OBJPROP_P(return_value);
    }

    if (ht_retval) {
        if (level_name) {
            ELOG_HASH_ADD_STRINGL(ht_retval, label_level,
                                  level_name, strlen(level_name), 1);
        }
    } else {
        smart_str buf = {0};

        elog_var_dump(&msg, 1, &buf, ELOG_MODE_CONVERT TSRMLS_CC);

        if (level_name) {
            ELOG_BEGIN_IS_JSON_STRING(buf);
            ELOG_JSON_SET(label_level, json_string(level_name));
            ELOG_JSON_RETVAL();
            ELOG_END_IS_JSON_STRING(1);

            ELOG_ADD_EOL(buf);

            smart_str_appendl(&buf, label_level, strlen(label_level));
            smart_str_appendl(&buf, ": ", 2);
            smart_str_appendl(&buf, level_name, strlen(level_name));
        }

        smart_str_0(&buf);

        RETVAL_STRINGL(buf.c, buf.len, 1);

        smart_str_free(&buf);
    }
}
