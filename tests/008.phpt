--TEST--
elog http: type=11,host=127.0.0.1
--INI--
--SKIPIF--
<?php require 'test.inc'; http_server_skipif('http://127.0.0.1:12342'); ?>
--FILE--
<?php
require 'test.inc';

$log = dirname(__FILE__) . "/tmp_008.log";
$host = "http://127.0.0.1:12342";

echo "=== $host ===\n";

$pid = http_server_test($host, $log);

elog("dummy", 11, $host);

file_wait($log);
file_dump($log);

server_finish($pid);
?>
--EXPECTF--
=== http://127.0.0.1:12342 ===
Method: POST
Uri: /
Body: dummy
Headers: Array
(
    [Host] => 127.0.0.1:12342
    [Content-Length] => 5
    [Content-Type] => application/x-www-form-urlencoded
)
