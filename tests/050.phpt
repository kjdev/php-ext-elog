--TEST--
elog_filter_add_request
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_050.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_request ]\n";
var_dump(elog_append_filter('elog_filter_add_request'));

echo "[ append: elog_filter_to_string ]\n";
var_dump(elog_append_filter('elog_filter_to_string'));

function test($out) {
    var_dump($_REQUEST);
    elog('dummy');
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);
    echo "\n";
}

echo "\n[ Test 1 ]\n";
$_REQUEST = array('test1' => 'TEST-1');
test($log);

echo "\n[ Test 2 ]\n";
$_REQUEST = array('test1' => 'TEST-1',
                  'test2' => 'TEST-2');
test($log);

echo "\n[ Test 3 ]\n";
$_REQUEST = array('test1' => 'TEST-1',
                  'test2' => 'TEST-2',
                  array('test3' =>  'TEST-3'));
test($log);

?>
--EXPECTF--
[ append: elog_filter_add_request ]
bool(true)
[ append: elog_filter_to_string ]
bool(true)

[ Test 1 ]
array(1) {
  ["test1"]=>
  string(6) "TEST-1"
}
=== output ===
dummy
elog_request: {
  "test1": "TEST-1"
}

[ Test 2 ]
array(2) {
  ["test1"]=>
  string(6) "TEST-1"
  ["test2"]=>
  string(6) "TEST-2"
}
=== output ===
dummy
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
}

[ Test 3 ]
array(3) {
  ["test1"]=>
  string(6) "TEST-1"
  ["test2"]=>
  string(6) "TEST-2"
  [0]=>
  array(1) {
    ["test3"]=>
    string(6) "TEST-3"
  }
}
=== output ===
dummy
elog_request: {
  "test1": "TEST-1"
  "test2": "TEST-2"
  0: {
    "test3": "TEST-3"
  }
}
