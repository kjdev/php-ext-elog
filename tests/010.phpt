--TEST--
elog multi type
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

$log_1 = dirname(__FILE__) . "/tmp_010_1.log";
$log_2 = dirname(__FILE__) . "/tmp_010_2.log";
$log_3 = dirname(__FILE__) . "/test.log";
$command = dirname(__FILE__) . "/test.sh";

elog("dummy\n", array(array(3, $log_1),
                      array(3, $log_2),
                      array(10, $command, 'hoge foo')));

echo "=== file 1 ===\n";
file_dump($log_1);

echo "=== file 2 ===\n";
file_dump($log_2);

echo "=== $command ===\n";
file_dump($log_3);
?>
--EXPECTF--
=== file 1 ===
dummy
=== file 2 ===
dummy
=== %stest.sh ===
hoge foo
dummy
