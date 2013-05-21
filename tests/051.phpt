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

echo "\n[ Test 4 ]\n";
test(true, $log);

echo "\n[ Test 5 ]\n";
test(12345, $log);

echo "\n[ Test 6 ]\n";
test(array("dummy"), $log);

echo "\n[ Test 7 ]\n";
$obj = new stdClass;
$obj->dummy = 'dummy';
test($obj, $log);

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


[ Test 4 ]
=== output ===
1

[ Test 5 ]
=== output ===
12345

[ Test 6 ]

Notice: Array to string conversion in %s on line %d
=== output ===
Array
[ Test 7 ]

Catchable fatal error: Object of class stdClass could not be converted to string in %s on line %d
