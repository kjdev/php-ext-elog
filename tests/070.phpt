--TEST--
elog to: json: ini=elog.filter_json_assoc
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_070.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

ini_set('elog.to', 'json');

function test($val, $out) {
    echo "[ ", gettype($val), " ]\n";
    var_dump($val);
    elog($val);

    echo "=== elog.filter_json_assoc ===\n";
    echo ini_get('elog.filter_json_assoc'), "\n";

    echo "=== output ===\n";
    file_dump($out);
    echo "\n";
}

test(array('a', 'b', 'c'), $log);
test(array('a' => 'A', 'b' => 'B', 'c' => 'C'), $log);

ini_set('elog.filter_json_assoc', 'On');
test(array('a', 'b', 'c'), $log);
test(array('a' => 'A', 'b' => 'B', 'c' => 'C'), $log);

ini_set('elog.filter_json_assoc', 'Off');

test(array('a', 'b', 'c'), $log);
test(array('a' => 'A', 'b' => 'B', 'c' => 'C'), $log);

?>
--EXPECTF--
[ array ]
array(3) {
  [0]=>
  string(1) "a"
  [1]=>
  string(1) "b"
  [2]=>
  string(1) "c"
}
=== elog.filter_json_assoc ===
Off
=== output ===
{"message":["a","b","c"]}
[ array ]
array(3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  string(1) "B"
  ["c"]=>
  string(1) "C"
}
=== elog.filter_json_assoc ===
Off
=== output ===
{"message":{"a":"A","b":"B","c":"C"}}
[ array ]
array(3) {
  [0]=>
  string(1) "a"
  [1]=>
  string(1) "b"
  [2]=>
  string(1) "c"
}
=== elog.filter_json_assoc ===
On
=== output ===
{"message":{"0":"a","1":"b","2":"c"}}
[ array ]
array(3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  string(1) "B"
  ["c"]=>
  string(1) "C"
}
=== elog.filter_json_assoc ===
On
=== output ===
{"message":{"a":"A","b":"B","c":"C"}}
[ array ]
array(3) {
  [0]=>
  string(1) "a"
  [1]=>
  string(1) "b"
  [2]=>
  string(1) "c"
}
=== elog.filter_json_assoc ===
Off
=== output ===
{"message":["a","b","c"]}
[ array ]
array(3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  string(1) "B"
  ["c"]=>
  string(1) "C"
}
=== elog.filter_json_assoc ===
Off
=== output ===
{"message":{"a":"A","b":"B","c":"C"}}
