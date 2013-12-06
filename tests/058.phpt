--TEST--
elog_filter: system INI=elog.filter_execute
--INI--
elog.filter_execute="elog_filter_add_fileline,fn_1st,2nd"
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_058.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

function fn_1st($val) {
    return $val . "[" . __FUNCTION__ . "]\n";
}

function fn_2nd($val) {
    return $val . "[" . __FUNCTION__ . "]\n";
}

function test($out) {
    echo "=== elog_get_filter ===\n";
    var_dump(elog_get_filter('enabled'));
    echo "=== elog.filter_execute ===\n";
    echo ini_get('elog.filter_execute'), "\n";
    echo "=== output ===\n";
    elog('dummy');
    file_dump($out);
    echo "\n";
}

echo "\n[ Test 1 ]\n";
test($log);

echo "\n[ Test 2 ]\n";
echo "filter register: fn_1st(fn_2nd)\n";
var_dump(elog_register_filter('fn_1st', 'fn_2nd'));
test($log);

echo "\n[ Test 2 ]\n";
echo "filter register: 2nd\n";
var_dump(elog_register_filter('2nd', 'fn_2nd'));
test($log);

?>
--EXPECTF--
[ Test 1 ]
=== elog_get_filter ===
array(3) {
  [0]=>
  string(24) "elog_filter_add_fileline"
  [1]=>
  string(6) "fn_1st"
  [2]=>
  string(3) "2nd"
}
=== elog.filter_execute ===
elog_filter_add_fileline,fn_1st,2nd
=== output ===
dummy
elog_file: %s/058.php
elog_line: 23[fn_1st]


[ Test 2 ]
filter register: fn_1st(fn_2nd)
bool(true)
=== elog_get_filter ===
array(3) {
  [0]=>
  string(24) "elog_filter_add_fileline"
  [1]=>
  string(6) "fn_1st"
  [2]=>
  string(3) "2nd"
}
=== elog.filter_execute ===
elog_filter_add_fileline,fn_1st,2nd
=== output ===
dummy
elog_file: %s/058.php
elog_line: 23[fn_2nd]


[ Test 2 ]
filter register: 2nd
bool(true)
=== elog_get_filter ===
array(3) {
  [0]=>
  string(24) "elog_filter_add_fileline"
  [1]=>
  string(6) "fn_1st"
  [2]=>
  string(3) "2nd"
}
=== elog.filter_execute ===
elog_filter_add_fileline,fn_1st,2nd
=== output ===
dummy
elog_file: %s/058.php
elog_line: 23[fn_2nd]
[fn_2nd]
