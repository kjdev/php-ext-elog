--TEST--
elog to: json: object
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_072.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);


ini_set('elog.to', 'json');

function test($val, $out) {
    echo "[ ", gettype($val), " ]\n";
    var_dump($val);
    elog($val);

    echo "=== output ===\n";
    file_dump($out);
    echo "\n";
}

$obj = new stdClass;

$obj->t = true;
$obj->f = false;
$obj->i = 12345;
$obj->l = 98.765;
$obj->s = "dummy";
$obj->n = null;
$obj->a = array('a', 'b', 'c');
$obj->m = array('a' => 'A', 'b' => 'B', 'c' => 'C');
$obj->o = new stdClass;
$obj->o->o = 'O';

$file = fopen(__FILE__, "r");
$obj->r = $file;

test($obj, $log);

fclose($file);
?>
--EXPECTF--
[ object ]
object(stdClass)#%d (10) {
  ["t"]=>
  bool(true)
  ["f"]=>
  bool(false)
  ["i"]=>
  int(12345)
  ["l"]=>
  float(98.765)
  ["s"]=>
  string(5) "dummy"
  ["n"]=>
  NULL
  ["a"]=>
  array(3) {
    [0]=>
    string(1) "a"
    [1]=>
    string(1) "b"
    [2]=>
    string(1) "c"
  }
  ["m"]=>
  array(3) {
    ["a"]=>
    string(1) "A"
    ["b"]=>
    string(1) "B"
    ["c"]=>
    string(1) "C"
  }
  ["o"]=>
  object(stdClass)#%d (1) {
    ["o"]=>
    string(1) "O"
  }
  ["r"]=>
  resource(%d) of type (stream)
}

Warning: elog(): Type is not supported in %s on line %d
=== output ===
{"message":{"t":true,"f":false,"i":12345,"l":98.765%d,"s":"dummy","n":null,"a":["a","b","c"],"m":{"a":"A","b":"B","c":"C"},"o":{"o":"O"},"r":null}}
