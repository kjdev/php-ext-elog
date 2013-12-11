--TEST--
elog to: json: ini=elog.filter_json_unicode_escape
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_044.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

ini_set('elog.to', 'json');

function test($val, $out) {
    echo "[ ", gettype($val), " ]\n";
    var_dump($val);
    elog($val);

    echo "=== elog.filter_json_unicode_escape ===\n";
    echo ini_get('elog.filter_json_unicode_escape'), "\n";

    echo "=== output ===\n";
    file_dump($out);
    echo "\n";
}

test('dummy', $log);
test('テスト', $log);

ini_set('elog.filter_json_unicode_escape', 'On');
test('dummy', $log);
test('テスト', $log);

ini_set('elog.filter_json_unicode_escape', 'Off');

test('dummy', $log);
test('テスト', $log);


?>
--EXPECTF--
[ string ]
string(5) "dummy"
=== elog.filter_json_unicode_escape ===
On
=== output ===
{"message":"dummy"}
[ string ]
string(9) "テスト"
=== elog.filter_json_unicode_escape ===
On
=== output ===
{"message":"\u30c6\u30b9\u30c8"}
[ string ]
string(5) "dummy"
=== elog.filter_json_unicode_escape ===
On
=== output ===
{"message":"dummy"}
[ string ]
string(9) "テスト"
=== elog.filter_json_unicode_escape ===
On
=== output ===
{"message":"\u30c6\u30b9\u30c8"}
[ string ]
string(5) "dummy"
=== elog.filter_json_unicode_escape ===
Off
=== output ===
{"message":"dummy"}
[ string ]
string(9) "テスト"
=== elog.filter_json_unicode_escape ===
Off
=== output ===
{"message":"テスト"}
