--TEST--
override error_handler: system INI elog.called_origin_error_handler=Off
--INI--
date.timezone=Asia/Tokyo
log_errors=On
error_log="tests/tmp_064_0.log"
elog.default_type=3
elog.default_destination="tests/tmp_064_1.log"
elog.override_error_handler=On
elog.called_origin_error_handler=Off
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log_1 = dirname(__FILE__) . "/tmp_064_0.log";
$log_2 = dirname(__FILE__) . "/tmp_064_1.log";

echo $aa;

echo "=== error_log ===\n";
file_dump($log_1);

echo "=== elog ===\n";
file_dump($log_2);

?>
--EXPECTF--
=== error_log ===
=== elog ===
PHP Notice:  Undefined variable: aa in %s/064.php on line %d
