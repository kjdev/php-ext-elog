--TEST--
elog udp: type=11,host=127.0.0.1
--INI--
--SKIPIF--
<?php require 'test.inc'; udp_server_skipif('udp://127.0.0.1:12342'); ?>
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_007.log";
$host = "udp://127.0.0.1:12342";

function udp_client_test($host) {
    echo "=== $host ===\n";
    elog("dummy", 11, $host);
}

udp_server_test($host, $log);

file_dump($log);
?>
--EXPECTF--
=== udp://127.0.0.1:12342 ===
dummy
