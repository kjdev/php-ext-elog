--TEST--
elog_filter_add_trace: add_level and add_fileline
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_079.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

ini_set('elog.filter_label_trace', 'trace');

echo "[ append: elog_filter_add_level ]\n";
var_dump(elog_append_filter('elog_filter_add_level'));

echo "[ append: elog_filter_add_fileline ]\n";
var_dump(elog_append_filter('elog_filter_add_fileline'));

echo "[ append: elog_filter_add_trace ]\n";
var_dump(elog_append_filter('elog_filter_add_trace'));

function test1($out) {
    elog_info('test1:message');
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);
}

function test2($out) {
    elog_err('test2:message');
    test1($out);
}

function test3($out) {
    elog_warning('test3:message');
    test2($out);
}

elog('message');
echo "=== output ===\n";
$buf = '';
file_dump($log, $buf);

echo "\n[ Test 1 ]\n";
test1($log);

echo "\n[ Test 2 ]\n";
test2($log);

echo "\n[ Test 3 ]\n";
test3($log);

?>
--EXPECTF--
[ append: elog_filter_add_level ]
bool(true)
[ append: elog_filter_add_fileline ]
bool(true)
[ append: elog_filter_add_trace ]
bool(true)
=== output ===
message
file: %s/079.php
line: 37
trace: [
  "#0 elog() called at [%s/079.php:37]"
]

[ Test 1 ]
=== output ===
test1:message
level: INFO
file: %s/079.php
line: 21
trace: [
  "#0 elog_info() called at [%s/079.php:21]"
  "#1 test1() called at [%s/079.php:43]"
]

[ Test 2 ]
=== output ===
test2:message
level: ERR
file: %s/079.php
line: 28
trace: [
  "#0 elog_err() called at [%s/079.php:28]"
  "#1 test2() called at [%s/079.php:46]"
]
test1:message
level: INFO
file: %s/079.php
line: 21
trace: [
  "#0 elog_info() called at [%s/079.php:21]"
  "#1 test1() called at [%s/079.php:29]"
  "#2 test2() called at [%s/079.php:46]"
]

[ Test 3 ]
=== output ===
test3:message
level: WARNING
file: %s/079.php
line: 33
trace: [
  "#0 elog_warning() called at [%s/079.php:33]"
  "#1 test3() called at [%s/079.php:49]"
]
test2:message
level: ERR
file: %s/079.php
line: 28
trace: [
  "#0 elog_err() called at [%s/079.php:28]"
  "#1 test2() called at [%s/079.php:34]"
  "#2 test3() called at [%s/079.php:49]"
]
test1:message
level: INFO
file: %s/079.php
line: 21
trace: [
  "#0 elog_info() called at [%s/079.php:21]"
  "#1 test1() called at [%s/079.php:29]"
  "#2 test2() called at [%s/079.php:34]"
  "#3 test3() called at [%s/079.php:49]"
]
