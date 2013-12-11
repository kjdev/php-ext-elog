
#ifndef ELOG_FILTER_H
#define ELOG_FILTER_H

#include "php_elog.h"
#include "ext/standard/php_smart_str.h"

typedef struct php_elog_filter_data {
    int dtor;
    zval *function;
} php_elog_filter_data_t;

PHP_ELOG_API void php_elog_to_string(zval *msg, zval *val, smart_str *str TSRMLS_DC);
PHP_ELOG_API void php_elog_to_json(zval *msg, zval *val, smart_str *str TSRMLS_DC);
PHP_ELOG_API void php_elog_to_http(zval *msg, zval *val, smart_str *str TSRMLS_DC);
PHP_ELOG_API void php_elog_zval_to_json_string(zval *val, smart_str *str TSRMLS_DC);
PHP_ELOG_API void php_elog_filter_data_dtor(php_elog_filter_data_t *data);
PHP_ELOG_API int php_elog_filter_is_builtin(zval *func);

ZEND_BEGIN_ARG_INFO_EX(arginfo_elog_register_filter, 0, 0, 2)
    ZEND_ARG_INFO(0, name)
    ZEND_ARG_INFO(0, callback)
    ZEND_ARG_INFO(0, enabled)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_elog_append_filter, 0, 0, 1)
    ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_elog_prepend_filter, 0, 0, 1)
    ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_elog_remove_filter, 0, 0, 1)
    ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_elog_get_filter, 0, 0, 0)
    ZEND_ARG_INFO(0, type)
ZEND_END_ARG_INFO()

ZEND_FUNCTION(elog_register_filter);
ZEND_FUNCTION(elog_append_filter);
ZEND_FUNCTION(elog_prepend_filter);
ZEND_FUNCTION(elog_remove_filter);
ZEND_FUNCTION(elog_get_filter);


ZEND_BEGIN_ARG_INFO_EX(arginfo_elog_filter_function, 0, 0, 1)
    ZEND_ARG_INFO(0, message)
    ZEND_ARG_INFO(0, level)
ZEND_END_ARG_INFO()

ZEND_FUNCTION(elog_filter_add_fileline);
ZEND_FUNCTION(elog_filter_add_timestamp);
ZEND_FUNCTION(elog_filter_add_request);
ZEND_FUNCTION(elog_filter_add_level);
ZEND_FUNCTION(elog_filter_add_trace);

#endif
