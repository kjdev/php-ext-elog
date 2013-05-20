--TEST--
override error_log: system INI
--INI--
date.timezone=Asia/Tokyo
log_errors=On
error_log="tests/tmp_062_0.log"
elog.default_type=3
elog.default_destination="tests/tmp_062_1.log"
elog.override_error_log=On
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log_1 = dirname(__FILE__) . "/tmp_062_0.log";
$log_2 = dirname(__FILE__) . "/tmp_062_1.log";


echo "\n[ elog ]\n";
elog("dummy\n");

echo "=== error_log ===\n";
file_dump($log_1);

echo "=== elog ===\n";
file_dump($log_2);

echo "\n[ error_log ]\n";
error_log("dummy\n");

echo "=== error_log ===\n";
file_dump($log_1);

echo "=== elog ===\n";
file_dump($log_2);

?>
--EXPECTF--
[ elog ]
=== error_log ===
=== elog ===
dummy

[ error_log ]
=== error_log ===
=== elog ===
dummy
