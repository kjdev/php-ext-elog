--TEST--
elog default: ini_set
--INI--
date.timezone=Asia/Tokyo
log_errors=On
--SKIPIF--
--FILE--
<?php
require 'test.inc';

$log_0 = dirname(__FILE__) . "/tmp_011_0.log";
$log_1 = dirname(__FILE__) . "/tmp_011_1.log";
$log_2 = dirname(__FILE__) . "/tmp_011_2.log";
$log_3 = dirname(__FILE__) . "/test.log";
$command = dirname(__FILE__) . "/test.sh";

function test($out_0, $out_1, $out_2, $out_3) {
    elog("dummy\n");

    echo "=== output 0 ===\n";
    file_dump($out_0);

    echo "=== output 1 ===\n";
    file_dump($out_1);

    echo "=== output 2 ===\n";
    file_dump($out_2);

    echo "=== output 3 ===\n";
    file_dump($out_3);
}

echo "[ Test 0 ]\n";

ini_set("error_log", $log_0);

test($log_0, $log_1, $log_2, $log_3);

echo "[ Test 1 ]\n";

ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log_1);

test($log_0, $log_1, $log_2, $log_3);

echo "[ Test 2 ]\n";

ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log_2);

test($log_0, $log_1, $log_2, $log_3);

echo "[ Test 3 ]\n";

ini_set('elog.default_type', 10);
ini_set('elog.default_destination', $command);
ini_set('elog.default_options', 'hoge foo');

test($log_0, $log_1, $log_2, $log_3);
?>
--EXPECTF--
[ Test 0 ]
=== output 0 ===
[%s Asia/Tokyo] dummy
=== output 1 ===
=== output 2 ===
=== output 3 ===
[ Test 1 ]
=== output 0 ===
=== output 1 ===
dummy
=== output 2 ===
=== output 3 ===
[ Test 2 ]
=== output 0 ===
=== output 1 ===
=== output 2 ===
dummy
=== output 3 ===
[ Test 3 ]
=== output 0 ===
=== output 1 ===
=== output 2 ===
=== output 3 ===
hoge foo
dummy
