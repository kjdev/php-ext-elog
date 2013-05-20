--TEST--
elog command: type=10,elog.command_output=FILE
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/test.log";
$command = dirname(__FILE__) . "/test.sh";
$out = dirname(__FILE__) . "/test.out";

ini_set('elog.command_output', $out);

echo "=== $command ===\n";
elog("dummy", 10, $command);

file_dump($log);
file_dump($out);

echo "=== $command hoge foo ===\n";
elog("dummy", 10, $command, 'hoge foo');

file_dump($log);
file_dump($out);
?>
--EXPECTF--
=== %s/test.sh ===

dummy
test.sh
=== %s/test.sh hoge foo ===
hoge foo
dummy
test.sh
