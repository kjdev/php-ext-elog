--TEST--
elog_filter: ini=elog.filter_execute, duplicate or invalid
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_057.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

function fn_1st($val) {
    return $val . "[" . __FUNCTION__ . "]\n";
}

elog_register_filter('fn_1st', 'fn_1st');

function test($out) {
    echo "=== elog_get_filter ===\n";
    var_dump(elog_get_filter('enabled'));
    echo "=== elog.filter_execute ===\n";
    echo ini_get('elog.filter_execute'), "\n";
    echo "=== output ===\n";
    elog('dummy');
    file_dump($out);
}

echo "\n[ Test 1 ]\n";
test($log);

echo "\n[ Test 2 ]\n";
ini_set('elog.filter_execute', 'fn_1st');
test($log);

echo "\n[ Test 3 ]\n";
var_dump(elog_append_filter('fn_1st'));
test($log);

echo "\n[ Test 4 ]\n";
ini_set('elog.filter_execute', 'fn_1st,fn_1st,hoge,foo');
test($log);

?>
--EXPECTF--
[ Test 1 ]
=== elog_get_filter ===
array(0) {
}
=== elog.filter_execute ===

=== output ===
dummy

[ Test 2 ]
=== elog_get_filter ===
array(1) {
  [0]=>
  string(6) "fn_1st"
}
=== elog.filter_execute ===
fn_1st
=== output ===
dummy[fn_1st]

[ Test 3 ]
bool(true)
=== elog_get_filter ===
array(1) {
  [0]=>
  string(6) "fn_1st"
}
=== elog.filter_execute ===
fn_1st
=== output ===
dummy[fn_1st]

[ Test 4 ]
=== elog_get_filter ===
array(3) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(4) "hoge"
  [2]=>
  string(3) "foo"
}
=== elog.filter_execute ===
fn_1st,fn_1st,hoge,foo
=== output ===
dummy[fn_1st]
