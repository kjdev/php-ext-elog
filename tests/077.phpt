--TEST--
elog_filter_add_trace
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_077.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_trace ]\n";
var_dump(elog_append_filter('elog_filter_add_trace'));

function test1($out) {
    elog('test1:message');
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);
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

echo "\n[ Test 1 ]\n";
test1($log);

echo "\n[ Test 2 ]\n";
test2($log);

echo "\n[ Test 3 ]\n";
test3($log);

?>
--EXPECTF--
[ append: elog_filter_add_trace ]
bool(true)
=== output ===
message
trace: [
  "#0 elog() called at [%s/077.php:29]"
]

[ Test 1 ]
=== output ===
test1:message
trace: [
  "#0 elog() called at [%s/077.php:13]"
  "#1 test1() called at [%s/077.php:35]"
]

[ Test 2 ]
=== output ===
test2:message
trace: [
  "#0 elog() called at [%s/077.php:20]"
  "#1 test2() called at [%s/077.php:38]"
]
test1:message
trace: [
  "#0 elog() called at [%s/077.php:13]"
  "#1 test1() called at [%s/077.php:21]"
  "#2 test2() called at [%s/077.php:38]"
]

[ Test 3 ]
=== output ===
test3:message
trace: [
  "#0 elog() called at [%s/077.php:25]"
  "#1 test3() called at %s/077.php:41]"
]
test2:message
trace: [
  "#0 elog() called at [%s/077.php:20]"
  "#1 test2() called at [%s/077.php:26]"
  "#2 test3() called at [%s/077.php:41]"
]
test1:message
trace: [
  "#0 elog() called at [%s/077.php:13]"
  "#1 test1() called at [%s/077.php:21]"
  "#2 test2() called at [%s/077.php:26]"
  "#3 test3() called at [%s/077.php:41]"
]
