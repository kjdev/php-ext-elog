--TEST--
elog_filter_to_json: array
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_071.log";
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

test(array(true), $log);
test(array(false), $log);
test(array(12345), $log);
test(array(98.765), $log);
test(array('dummy'), $log);
test(array(null), $log);

test(array(array('a', 'b', 'c')), $log);
test(array(array('a' => 'A', 'b' => 'B', 'c' => 'C')), $log);
test(array(array('a', 'b' => 'B', 'c')), $log);
test(array(array('a', array('b', array('c')))), $log);

$obj = new stdClass;
$obj->a = 'A';
$obj->b = 'B';
$obj->c = 'C';
test(array($obj), $log);
$obj->b = new stdClass;
$obj->b->x = 'X';
$obj->c = new stdClass;
$obj->c->y = new stdClass;
$obj->c->y->z = 'Z';
test(array($obj), $log);

$file = fopen(__FILE__, "r");
test(array($file), $log);
fclose($file);
?>
--EXPECTF--
[ append: elog_filter_to_json ]
bool(true)
[ array ]
array(1) {
  [0]=>
  bool(true)
}
=== output ===
[true]
[ array ]
array(1) {
  [0]=>
  bool(false)
}
=== output ===
[false]
[ array ]
array(1) {
  [0]=>
  int(12345)
}
=== output ===
[12345]
[ array ]
array(1) {
  [0]=>
  float(98.765)
}
=== output ===
[98.765%d]
[ array ]
array(1) {
  [0]=>
  string(5) "dummy"
}
=== output ===
["dummy"]
[ array ]
array(1) {
  [0]=>
  NULL
}
=== output ===
[null]
[ array ]
array(1) {
  [0]=>
  array(3) {
    [0]=>
    string(1) "a"
    [1]=>
    string(1) "b"
    [2]=>
    string(1) "c"
  }
}
=== output ===
[["a","b","c"]]
[ array ]
array(1) {
  [0]=>
  array(3) {
    ["a"]=>
    string(1) "A"
    ["b"]=>
    string(1) "B"
    ["c"]=>
    string(1) "C"
  }
}
=== output ===
[{"a":"A","b":"B","c":"C"}]
[ array ]
array(1) {
  [0]=>
  array(3) {
    [0]=>
    string(1) "a"
    ["b"]=>
    string(1) "B"
    [1]=>
    string(1) "c"
  }
}
=== output ===
[{"0":"a","b":"B","1":"c"}]
[ array ]
array(1) {
  [0]=>
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
}
=== output ===
[["a",["b",["c"]]]]
[ array ]
array(1) {
  [0]=>
  object(stdClass)#1 (3) {
    ["a"]=>
    string(1) "A"
    ["b"]=>
    string(1) "B"
    ["c"]=>
    string(1) "C"
  }
}
=== output ===
[{"a":"A","b":"B","c":"C"}]
[ array ]
array(1) {
  [0]=>
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
}
=== output ===
[{"a":"A","b":{"x":"X"},"c":{"y":{"z":"Z"}}}]
[ array ]
array(1) {
  [0]=>
  resource(%d) of type (stream)
}

Warning: elog_filter_to_json(): Type is not supported in %s on line %d
=== output ===
[null]
