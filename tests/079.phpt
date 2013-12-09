--TEST--
elog_filter_add_trace: add_level and add_fileline
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_078.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

ini_set('elog.filter_label_trace', 'trace');

echo "[ append: elog_filter_to_array ]\n";
var_dump(elog_append_filter('elog_filter_to_array'));

echo "[ append: elog_filter_add_level ]\n";
var_dump(elog_append_filter('elog_filter_add_level'));

echo "[ append: elog_filter_add_fileline ]\n";
var_dump(elog_append_filter('elog_filter_add_fileline'));

echo "[ append: elog_filter_add_trace ]\n";
var_dump(elog_append_filter('elog_filter_add_trace'));

echo "[ append: elog_filter_to_string ]\n";
var_dump(elog_append_filter('elog_filter_to_string'));

echo "[ append: elog_filter_add_eol ]\n";
var_dump(elog_append_filter('elog_filter_add_eol'));

function test1($out) {
    elog_info('test1:message');
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);
    echo "\n";
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
echo "\n";

echo "\n[ Test 1 ]\n";
test1($log);

echo "\n[ Test 2 ]\n";
test2($log);

echo "\n[ Test 3 ]\n";
test3($log);

?>
--EXPECTF--
[ append: elog_filter_to_array ]
bool(true)
[ append: elog_filter_add_level ]
bool(true)
[ append: elog_filter_add_fileline ]
bool(true)
[ append: elog_filter_add_trace ]
bool(true)
[ append: elog_filter_to_string ]
bool(true)
[ append: elog_filter_add_eol ]
bool(true)
=== output ===
{
  0: "message"
  "elog_file": "%s/079.php"
  "elog_line": 47
  "trace": [
    "#0 elog() called at [%s/079.php:47]"
  ]
}


[ Test 1 ]
=== output ===
{
  0: "test1:message"
  "elog_level": "INFO"
  "elog_file": "%s/079.php"
  "elog_line": 30
  "trace": [
    "#0 elog_info() called at [%s/079.php:30]"
    "#1 test1() called at [%s/079.php:54]"
  ]
}


[ Test 2 ]
=== output ===
{
  0: "test2:message"
  "elog_level": "ERR"
  "elog_file": "%s/079.php"
  "elog_line": 38
  "trace": [
    "#0 elog_err() called at [%s/079.php:38]"
    "#1 test2() called at [%s/079.php:57]"
  ]
}
{
  0: "test1:message"
  "elog_level": "INFO"
  "elog_file": "%s/079.php"
  "elog_line": 30
  "trace": [
    "#0 elog_info() called at [%s/079.php:30]"
    "#1 test1() called at [%s/079.php:39]"
    "#2 test2() called at [%s/079.php:57]"
  ]
}


[ Test 3 ]
=== output ===
{
  0: "test3:message"
  "elog_level": "WARNING"
  "elog_file": "%s/079.php"
  "elog_line": 43
  "trace": [
    "#0 elog_warning() called at [%s/079.php:43]"
    "#1 test3() called at [%s/079.php:60]"
  ]
}
{
  0: "test2:message"
  "elog_level": "ERR"
  "elog_file": "%s/079.php"
  "elog_line": 38
  "trace": [
    "#0 elog_err() called at [%s/079.php:38]"
    "#1 test2() called at [%s/079.php:44]"
    "#2 test3() called at [%s/079.php:60]"
  ]
}
{
  0: "test1:message"
  "elog_level": "INFO"
  "elog_file": "%s/079.php"
  "elog_line": 30
  "trace": [
    "#0 elog_info() called at [%s/079.php:30]"
    "#1 test1() called at [%s/079.php:39]"
    "#2 test2() called at [%s/079.php:44]"
    "#3 test3() called at [%s/079.php:60]"
  ]
}
