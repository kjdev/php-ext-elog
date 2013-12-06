--TEST--
elog_prepend_filter: invalid arguments
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


var_dump(elog_prepend_filter("hoge"));
var_dump(elog_prepend_filter(""));
var_dump(elog_prepend_filter(array()));
var_dump(elog_prepend_filter(null));

?>
--EXPECTF--
Warning: elog_prepend_filter(): No such filter "hoge" in %s on line %d
bool(false)

Warning: elog_prepend_filter(): No such filter "" in %s on line %d
bool(false)

Warning: elog_prepend_filter(): No such filter "Array(empty)" in %s on line %d
bool(false)

Warning: elog_prepend_filter(): Invalid arguments in %s on line %d
bool(false)
