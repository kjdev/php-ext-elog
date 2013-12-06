--TEST--
elog_filter_add_request: types
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_074.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_request ]\n";
var_dump(elog_append_filter('elog_filter_add_request'));

echo "[ append: elog_filter_to_string ]\n";
var_dump(elog_append_filter('elog_filter_to_string'));

echo "[ REQUEST ]\n";
$_REQUEST = array('test1' => 'TEST-1',
                  'test2' => 'TEST-2');
var_dump($_REQUEST);


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
[ append: elog_filter_add_request ]
bool(true)
[ append: elog_filter_to_string ]
bool(true)
[ REQUEST ]
array(2) {
  ["test1"]=>
  string(6) "TEST-1"
  ["test2"]=>
  string(6) "TEST-2"
}
[ boolean ]
bool(true)
=== output ===
1
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}
[ boolean ]
bool(false)
=== output ===
0
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}
[ integer ]
int(12345)
=== output ===
12345
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}
[ double ]
float(98.765)
=== output ===
98.765
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}
[ string ]
string(5) "dummy"
=== output ===
dummy
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}
[ NULL ]
NULL
=== output ===

elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}
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
{
  0: "a"
  1: "b"
  2: "c"
  "elog_request": {
    "test1": "TEST-1"
    "test2": "TEST-2"
  }
}
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
{
  "a": "A"
  "b": "B"
  "c": "C"
  "elog_request": {
    "test1": "TEST-1"
    "test2": "TEST-2"
  }
}
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
{
  0: "a"
  "b": "B"
  1: "c"
  "elog_request": {
    "test1": "TEST-1"
    "test2": "TEST-2"
  }
}
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
{
  0: "a"
  1: [
    "b"
    [
      "c"
    ]
  ]
  "elog_request": {
    "test1": "TEST-1"
    "test2": "TEST-2"
  }
}
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
stdClass {
  "a": "A"
  "b": "B"
  "c": "C"
  "elog_request": {
    "test1": "TEST-1"
    "test2": "TEST-2"
  }
}
[ object ]
object(stdClass)#%d (3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  object(stdClass)#%d (1) {
    ["x"]=>
    string(1) "X"
  }
  ["c"]=>
  object(stdClass)#%d (1) {
    ["y"]=>
    object(stdClass)#%d (1) {
      ["z"]=>
      string(1) "Z"
    }
  }
}
=== output ===
stdClass {
  "a": "A"
  "b": stdClass {
    "x": "X"
  }
  "c": stdClass {
    "y": stdClass {
      "z": "Z"
    }
  }
  "elog_request": {
    "test1": "TEST-1"
    "test2": "TEST-2"
  }
}
[ resource ]
resource(30) of type (stream)
=== output ===
resource of type(stream)
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}
