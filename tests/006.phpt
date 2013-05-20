--TEST--
elog tcp/ip: type=11,host=localhost
--INI--
--SKIPIF--
<?php require 'test.inc'; tcp_server_skipif('tcp://localhost:12342'); ?>
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_006.log";
$host = "tcp://localhost:12342";

function tcp_client_test($host) {
    echo "=== $host ===\n";
    elog("dummy", 11, $host);
}

tcp_server_test($host, $log);

file_dump($log);
?>
--EXPECTF--
=== tcp://localhost:12342 ===
dummy
