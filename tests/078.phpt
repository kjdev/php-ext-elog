--TEST--
elog_filter_add_trace: to_array
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_078.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_to_array ]\n";
var_dump(elog_append_filter('elog_filter_to_array'));

echo "[ append: elog_filter_add_trace ]\n";
var_dump(elog_append_filter('elog_filter_add_trace'));

echo "[ append: elog_filter_to_string ]\n";
var_dump(elog_append_filter('elog_filter_to_string'));

echo "[ append: elog_filter_add_eol ]\n";
var_dump(elog_append_filter('elog_filter_add_eol'));

function test1($out) {
    elog('test1:message');
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);
    echo "\n";
}

function test2($out) {
    elog('test2:message');
    test1($out);
}

function test3($out) {
    elog('test3:message');
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
[ append: elog_filter_add_trace ]
bool(true)
[ append: elog_filter_to_string ]
bool(true)
[ append: elog_filter_add_eol ]
bool(true)
=== output ===
{
  0: "message"
  "elog_trace": [
    "#0 elog() called at [%s/078.php:39]"
  ]
}


[ Test 1 ]
=== output ===
{
  0: "test1:message"
  "elog_trace": [
    "#0 elog() called at [%s/078.php:22]"
    "#1 test1() called at [%s/078.php:46]"
  ]
}


[ Test 2 ]
=== output ===
{
  0: "test2:message"
  "elog_trace": [
    "#0 elog() called at [%s/078.php:30]"
    "#1 test2() called at [%s/078.php:49]"
  ]
}
{
  0: "test1:message"
  "elog_trace": [
    "#0 elog() called at [%s/078.php:22]"
    "#1 test1() called at [%s/078.php:31]"
    "#2 test2() called at [%s/078.php:49]"
  ]
}


[ Test 3 ]
=== output ===
{
  0: "test3:message"
  "elog_trace": [
    "#0 elog() called at [%s/078.php:35]"
    "#1 test3() called at %s/078.php:52]"
  ]
}
{
  0: "test2:message"
  "elog_trace": [
    "#0 elog() called at [%s/078.php:30]"
    "#1 test2() called at [%s/078.php:36]"
    "#2 test3() called at [%s/078.php:52]"
  ]
}
{
  0: "test1:message"
  "elog_trace": [
    "#0 elog() called at [%s/078.php:22]"
    "#1 test1() called at [%s/078.php:31]"
    "#2 test2() called at [%s/078.php:36]"
    "#3 test3() called at [%s/078.php:52]"
  ]
}