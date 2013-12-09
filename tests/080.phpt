--TEST--
elog_shutdown_execute
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


ini_set('elog.default_type', -1);
elog_append_filter('elog_filter_add_eol');

elog('test1', -1);
elog('test2', -1);
elog('test3', -1);

echo "[ elog_shutdown_execute ]\n";
elog_shutdown_execute(-1);

elog('test1', -1);
elog('test2', -1);
elog('test3', -1);

echo "[ __END__ ]\n";
?>
--EXPECTF--
test1
test2
test3
[ elog_shutdown_execute ]
[ __END__ ]
test1
test2
test3
