--TEST--
elog filter: closure
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_040.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

$func_1st = function($val) { return str_replace("\n", "", $val) . "[1st]\n"; };
$func_3rd = function($val) { return str_replace("\n", "", $val) . "[3rd]\n"; };

function test($out) {
    var_dump(elog_get_filter('enabled'));

    elog("dummy\n");

    echo "=== output ===\n";
    file_dump($out);
}

echo "\n[ default ]\n";
test($log);

echo "\n[ Filter : append 1st ]\n";
elog_register_filter('1st', $func_1st, EL_FILTER_APPEND);
test($log);

echo "\n[ Filter : prepend 2nd ]\n";
elog_register_filter('2nd', function($val) { return str_replace("\n", "", $val) . "[2nd]\n"; }, EL_FILTER_PREPEND);
test($log);

echo "\n[ Filter : append 3rd ]\n";
elog_register_filter('3rd', $func_3rd);
elog_append_filter('3rd');
test($log);

echo "\n[ Filter : remove 2nd ]\n";
elog_remove_filter('2nd');
test($log);

echo "\n[ Filter : prepend 4th ]\n";
elog_register_filter('4th', function($val) { return str_replace("\n", "", $val) . "[4th]\n"; });
elog_prepend_filter('4th');
test($log);

?>
--EXPECTF--
[ default ]
array(0) {
}
=== output ===
dummy

[ Filter : append 1st ]
array(1) {
  [0]=>
  string(3) "1st"
}
=== output ===
dummy[1st]

[ Filter : prepend 2nd ]
array(2) {
  [0]=>
  string(3) "2nd"
  [1]=>
  string(3) "1st"
}
=== output ===
dummy[2nd][1st]

[ Filter : append 3rd ]
array(3) {
  [0]=>
  string(3) "2nd"
  [1]=>
  string(3) "1st"
  [2]=>
  string(3) "3rd"
}
=== output ===
dummy[2nd][1st][3rd]

[ Filter : remove 2nd ]
array(2) {
  [0]=>
  string(3) "1st"
  [1]=>
  string(3) "3rd"
}
=== output ===
dummy[1st][3rd]

[ Filter : prepend 4th ]
array(3) {
  [0]=>
  string(3) "4th"
  [1]=>
  string(3) "1st"
  [2]=>
  string(3) "3rd"
}
=== output ===
dummy[4th][1st][3rd]
