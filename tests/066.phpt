--TEST--
throw exception hook: system INI try-catch
--INI--
date.timezone=Asia/Tokyo
log_errors=On
elog.throw_exception_hook=On
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log_1 = dirname(__FILE__) . "/tmp_066_0.log";
$log_2 = dirname(__FILE__) . "/tmp_066_1.log";

ini_set('display_errors', 'Off');
ini_set('error_log', $log_1);
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log_2);

if ($pid = pcntl_fork()) {
    try {
        throw new Exception('dummy-try');
    } catch (Exception $e) {
        error_log($e->getMessage(), 3, $log_1);
        elog($e->getMessage());
    }
    exit(0);
}

usleep(500);

echo "=== error_log ===\n";
file_dump($log_1);
echo "\n";

echo "=== elog ===\n";
file_dump($log_2);
echo "\n";

?>
--EXPECTF--
=== error_log ===
dummy-try
=== elog ===
dummy-trydummy-try
