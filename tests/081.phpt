--TEST--
elog js.console: type=12
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_073.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

function test($val, $out) {
    echo "[ ", gettype($val), " ]\n";
    var_dump($val);
    elog($val, 12);
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
[ boolean ]
bool(true)
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",true);console.dir({"message":true});</script>
[ boolean ]
bool(false)
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",false);console.dir({"message":false});</script>
[ integer ]
int(12345)
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",12345);console.dir({"message":12345});</script>
[ double ]
float(98.765)
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",98.765000000000001);console.dir({"message":98.765%d});</script>
[ string ]
string(5) "dummy"
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("","dummy");console.dir({"message":"dummy"});</script>
[ NULL ]
NULL
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",null);console.dir({"message":null});</script>
[ array ]
array(3) {
  [0]=>
  string(1) "a"
  [1]=>
  string(1) "b"
  [2]=>
  string(1) "c"
}
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",["a","b","c"]);console.dir({"message":["a","b","c"]});</script>
[ array ]
array(3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  string(1) "B"
  ["c"]=>
  string(1) "C"
}
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",{"a":"A","b":"B","c":"C"});console.dir({"message":{"a":"A","b":"B","c":"C"}});</script>
[ array ]
array(3) {
  [0]=>
  string(1) "a"
  ["b"]=>
  string(1) "B"
  [1]=>
  string(1) "c"
}
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",{"0":"a","b":"B","1":"c"});console.dir({"message":{"0":"a","b":"B","1":"c"}});</script>
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
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",["a",["b",["c"]]]);console.dir({"message":["a",["b",["c"]]]});</script>
[ object ]
object(stdClass)#%d (3) {
  ["a"]=>
  string(1) "A"
  ["b"]=>
  string(1) "B"
  ["c"]=>
  string(1) "C"
}
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",{"a":"A","b":"B","c":"C"});console.dir({"message":{"a":"A","b":"B","c":"C"}});</script>
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
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",{"a":"A","b":{"x":"X"},"c":{"y":{"z":"Z"}}});console.dir({"message":{"a":"A","b":{"x":"X"},"c":{"y":{"z":"Z"}}}});</script>
[ resource ]
resource(%d) of type (stream)
<script type="text/javascript">if(!('console' in window)){window.console={};window.console.log=function(s1,s2){return s2;};}console.log("",null);console.dir({"message":null});</script>
