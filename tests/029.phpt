--TEST--
elog_append_filter: invalid arguments
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

var_dump(elog_append_filter("hoge"));
var_dump(elog_append_filter(""));
var_dump(elog_append_filter(array()));
var_dump(elog_append_filter(null));

?>
--EXPECTF--
Warning: elog_append_filter(): No such filter "hoge" in %s on line %d
bool(false)

Warning: elog_append_filter(): No such filter "" in %s on line %d
bool(false)

Warning: elog_append_filter(): No such filter "Array(empty)" in %s on line %d
bool(false)

Warning: elog_append_filter(): Invalid arguments in %s on line %d
bool(false)
