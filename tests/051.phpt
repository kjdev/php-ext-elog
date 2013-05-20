--TEST--
elog_filter_add_eol
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_051.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_eol ]\n";
var_dump(elog_append_filter('elog_filter_add_eol'));

function test($var, $out) {
    elog($var);
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);
}

echo "\n[ Test 1 ]\n";
test('dummy', $log);

echo "\n[ Test 2 ]\n";
test("dummy\n", $log);

echo "\n[ Test 3 ]\n";
test("dummy\n\n", $log);

?>
DONE
--EXPECTF--
[ append: elog_filter_add_eol ]
bool(true)

[ Test 1 ]
=== output ===
dummy

[ Test 2 ]
=== output ===
dummy

[ Test 3 ]
=== output ===
dummy

DONE
