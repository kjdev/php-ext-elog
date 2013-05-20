--TEST--
elog_register_filter: invalid arguments
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

var_dump(elog_register_filter());
var_dump(elog_register_filter("test"));
var_dump(elog_register_filter("test", ""));
var_dump(elog_register_filter("", ""));
var_dump(elog_register_filter("", "test"));
var_dump(elog_register_filter("------", "\\nonexistentclass"));
var_dump(elog_register_filter(array(), "aa"));
var_dump(elog_register_filter("", array()));

?>
--EXPECTF--
Warning: elog_register_filter() expects at least 2 parameters, 0 given in %s on line %d
NULL

Warning: elog_register_filter() expects at least 2 parameters, 1 given in %s on line %d
NULL

Warning: elog_register_filter(): Invalid callback function '' in %s on line %d
bool(false)

Warning: elog_register_filter(): Filter name cannot be empty in %s on line %d
bool(false)

Warning: elog_register_filter(): Filter name cannot be empty in %s on line %d
bool(false)

Warning: elog_register_filter(): Invalid callback function '\nonexistentclass' in %s on line %d
bool(false)

Warning: elog_register_filter() expects parameter 1 to be string, array given in %s on line %d
NULL

Warning: elog_register_filter(): Filter name cannot be empty in %s on line %d
bool(false)
