--TEST--
elog file: type=3
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

$log = dirname(__FILE__) . "/tmp_002.log";

elog("dummy", 3, $log);

file_dump($log);
?>
--EXPECTF--
dummy
