--TEST--
elog command: type=10
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

$log = dirname(__FILE__) . "/test.log";
$command = dirname(__FILE__) . "/test.sh";

echo "=== $command ===\n";
elog("dummy", 10, $command);

file_dump($log);

echo "=== $command hoge foo ===\n";
elog("dummy", 10, $command, 'hoge foo');

file_dump($log);
?>
--EXPECTF--
=== %s/test.sh ===

dummy
=== %s/test.sh hoge foo ===
hoge foo
dummy
