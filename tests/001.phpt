--TEST--
elog default
--INI--
date.timezone=Asia/Tokyo
log_errors=On
--SKIPIF--
--FILE--
<?php
require 'test.inc';

$log = dirname(__FILE__) . "/tmp_001.log";
ini_set("error_log", $log);

echo $aa;
elog("dummy");

file_dump($log);
?>
--EXPECTF--
Notice: Undefined variable: aa in %s.php on line %d
[%s Asia/Tokyo] PHP Notice:  Undefined variable: aa in %s.php on line %d
[%s Asia/Tokyo] dummy
