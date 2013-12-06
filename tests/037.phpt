--TEST--
elog filter: function
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_037.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

function f_1st($val) {
    return str_replace("\n", "", $val) . "[" . __FUNCTION__ ."]\n";
}

function f_2nd($val) {
    return str_replace("\n", "", $val) . "[" . __FUNCTION__ ."]\n";
}

function f_3rd($val) {
    return str_replace("\n", "", $val) . "[" . __FUNCTION__ ."]\n";
}

function f_4th($val) {
    return str_replace("\n", "", $val) . "[" . __FUNCTION__ ."]\n";
}

function test($out) {
    var_dump(elog_get_filter('enabled'));

    elog("dummy\n");

    echo "=== output ===\n";
    file_dump($out);
}

echo "\n[ default ]\n";
test($log);

echo "\n[ Filter : append fist ]\n";
elog_register_filter('1st', 'f_1st', EL_FILTER_APPEND);
test($log);

echo "\n[ Filter : prepend 2nd ]\n";
elog_register_filter('2nd', 'f_2nd', EL_FILTER_PREPEND);
test($log);

echo "\n[ Filter : append 3rd ]\n";
elog_register_filter('3rd', 'f_3rd');
elog_append_filter('3rd');
test($log);

echo "\n[ Filter : remove 2nd ]\n";
elog_remove_filter('2nd');
test($log);

echo "\n[ Filter : prepend 4th ]\n";
elog_register_filter('4th', 'f_4th');
elog_prepend_filter('4th');
test($log);
?>
--EXPECTF--

[ default ]
array(0) {
}
=== output ===
dummy

[ Filter : append fist ]
array(1) {
  [0]=>
  string(3) "1st"
}
=== output ===
dummy[f_1st]

[ Filter : prepend 2nd ]
array(2) {
  [0]=>
  string(3) "2nd"
  [1]=>
  string(3) "1st"
}
=== output ===
dummy[f_2nd][f_1st]

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
dummy[f_2nd][f_1st][f_3rd]

[ Filter : remove 2nd ]
array(2) {
  [0]=>
  string(3) "1st"
  [1]=>
  string(3) "3rd"
}
=== output ===
dummy[f_1st][f_3rd]

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
dummy[f_4th][f_1st][f_3rd]
