--TEST--
elog udp: type=11,host=127.0.0.1
--INI--
--SKIPIF--
<?php require 'test.inc'; udp_server_skipif('udp://127.0.0.1:12342'); ?>
--FILE--
<?php
require 'test.inc';

$log = dirname(__FILE__) . "/tmp_007.log";
$host = "udp://127.0.0.1:12342";

echo "=== $host ===\n";

$pid = udp_server_test($host, $log);

elog("dummy", 11, $host);

file_wait($log);
file_dump($log);

server_finish($pid);
?>
--EXPECTF--
=== udp://127.0.0.1:12342 ===
dummy
