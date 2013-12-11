--TEST--
multi type elog with filter
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';



$log_1 = dirname(__FILE__) . "/tmp_061_1.log";
$log_2 = dirname(__FILE__) . "/tmp_061_2.log";
$log_3 = dirname(__FILE__) . "/test.log";
$command = dirname(__FILE__) . "/test.sh";

function fn_1st($val) {
    return $val . "[" . __FUNCTION__ . "]\n";
}

function fn_2nd($val) {
    return $val . "[" . __FUNCTION__ . "]\n";
}

elog_register_filter('fn_1st', 'fn_1st');
elog_register_filter('fn_2nd', 'fn_2nd');

function test($out1, $out2, $out3, $command) {
    echo "=== elog_get_filter: registers ===\n";
    var_dump(elog_get_filter('registers'));
    echo "=== elog_get_filter: execute ===\n";
    var_dump(elog_get_filter('execute'));
    echo "=== elog_get_filter: enabled ===\n";
    var_dump(elog_get_filter('enabled'));

    elog("dummy\n",
         array(array(3, $out1, null, 'elog_filter_add_fileline'),
               array(3, $out2, null, null, 'json'),
               array(10, $command, 'hoge foo', 'elog_filter_add_fileline', 'json')));

    echo "=== file 1: fileline ===\n";
    file_dump($out1);

    echo "=== file 2: to.json ===\n";
    file_dump($out2);
    echo "\n";

    echo "=== $command: fileline, to.json ===\n";
    file_dump($out3);
    echo "\n";
};

echo "\n[ Test 1 ]\n";
test($log_1, $log_2, $log_3, $command);

echo "\n[ Test 2 ]\n";
echo "=== filter append: fn_1st ===\n";
var_dump(elog_append_filter('fn_1st'));
test($log_1, $log_2, $log_3, $command);

echo "\n[ Test 3 ]\n";
echo "=== elog.filter_execute: fn_2nd ===\n";
ini_set('elog.filter_execute', 'fn_2nd');
echo ini_get('elog.filter_execute'), "\n";
test($log_1, $log_2, $log_3, $command);

?>
--EXPECTF--
[ Test 1 ]
=== elog_get_filter: registers ===
array(2) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(6) "fn_2nd"
}
=== elog_get_filter: execute ===
array(0) {
}
=== elog_get_filter: enabled ===
array(0) {
}
=== file 1: fileline ===
dummy
file: %s/061.php
line: 33
=== file 2: to.json ===
{"message":"dummy\n"}
=== %s/test.sh: fileline, to.json ===
hoge foo
{"message":"dummy\n","file":"%s/061.php","line":33}


[ Test 2 ]
=== filter append: fn_1st ===
bool(true)
=== elog_get_filter: registers ===
array(2) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(6) "fn_2nd"
}
=== elog_get_filter: execute ===
array(0) {
}
=== elog_get_filter: enabled ===
array(1) {
  [0]=>
  string(6) "fn_1st"
}
=== file 1: fileline ===
dummy
[fn_1st]
file: %s/061.php
line: 33
=== file 2: to.json ===
{"message":"dummy\n[fn_1st]\n"}
=== %s/test.sh: fileline, to.json ===
hoge foo
{"message":"dummy\n[fn_1st]\n","file":"%s/061.php","line":33}


[ Test 3 ]
=== elog.filter_execute: fn_2nd ===
fn_2nd
=== elog_get_filter: registers ===
array(2) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(6) "fn_2nd"
}
=== elog_get_filter: execute ===
array(1) {
  [0]=>
  string(6) "fn_2nd"
}
=== elog_get_filter: enabled ===
array(2) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(6) "fn_2nd"
}
=== file 1: fileline ===
dummy
[fn_1st]
[fn_2nd]
file: %s/061.php
line: 33
=== file 2: to.json ===
{"message":"dummy\n[fn_1st]\n[fn_2nd]\n"}
=== %s/test.sh: fileline, to.json ===
hoge foo
{"message":"dummy\n[fn_1st]\n[fn_2nd]\n","file":"%s/061.php","line":33}
