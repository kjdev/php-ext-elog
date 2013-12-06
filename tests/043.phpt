--TEST--
elog_filter_to_json
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_043.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);


echo "[ append: elog_filter_to_json ]\n";
var_dump(elog_append_filter('elog_filter_to_json'));

function test($val, $out) {
    echo "[ ", gettype($val), " ]\n";
    var_dump($val);
    elog($val);

    echo "=== output ===\n";
    file_dump($out);
    echo "\n";
}

test(true, $log);
test(false, $log);
test(12345, $log);
test(98.765, $log);
test('dummy', $log);
test(null, $log);

test(array('a', 'b', 'c'), $log);
test(array('a' => 'A', 'b' => 'B', 'c' => 'C'), $log);
test(array('a', 'b' => 'B', 'c'), $log);
test(array('a', array('b', array('c'))), $log);

$obj = new stdClass;
$obj->a = 'A';
$obj->b = 'B';
$obj->c = 'C';
test($obj, $log);
$obj->b = new stdClass;
$obj->b->x = 'X';
$obj->c = new stdClass;
$obj->c->y = new stdClass;
$obj->c->y->z = 'Z';
test($obj, $log);

$file = fopen(__FILE__, "r");
test($file, $log);
fclose($file);
?>
--EXPECTF--
[ append: elog_filter_to_json ]
bool(true)
[ boolean ]
bool(true)
=== output ===
{"message":true}
[ boolean ]
bool(false)
=== output ===
{"message":false}
[ integer ]
int(12345)
=== output ===
{"message":12345}
[ double ]
float(98.765)
=== output ===
{"message":98.765%d}
[ string ]
string(5) "dummy"
=== output ===
{"message":"dummy"}
[ NULL ]
NULL
=== output ===
{"message":null}
[ array ]
array(3) {
  [0]=>
  string(1) "a"
  [1]=>
  string(1) "b"
  [2]=>
  string(1) "c"
}
=== output ===
["a","b","c"]
[ array ]
array(3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  string(1) "B"
  ["c"]=>
  string(1) "C"
}
=== output ===
{"a":"A","b":"B","c":"C"}
[ array ]
array(3) {
  [0]=>
  string(1) "a"
  ["b"]=>
  string(1) "B"
  [1]=>
  string(1) "c"
}
=== output ===
{"0":"a","b":"B","1":"c"}
[ array ]
array(2) {
  [0]=>
  string(1) "a"
  [1]=>
  array(2) {
    [0]=>
    string(1) "b"
    [1]=>
    array(1) {
      [0]=>
      string(1) "c"
    }
  }
}
=== output ===
["a",["b",["c"]]]
[ object ]
object(stdClass)#1 (3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  string(1) "B"
  ["c"]=>
  string(1) "C"
}
=== output ===
{"a":"A","b":"B","c":"C"}
[ object ]
object(stdClass)#1 (3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  object(stdClass)#2 (1) {
    ["x"]=>
    string(1) "X"
  }
  ["c"]=>
  object(stdClass)#3 (1) {
    ["y"]=>
    object(stdClass)#4 (1) {
      ["z"]=>
      string(1) "Z"
    }
  }
}
=== output ===
{"a":"A","b":{"x":"X"},"c":{"y":{"z":"Z"}}}
[ resource ]
resource(%d) of type (stream)

Warning: elog_filter_to_json(): Type is not supported in %s on line %d
=== output ===
{"message":null}
