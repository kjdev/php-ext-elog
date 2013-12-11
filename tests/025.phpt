--TEST--
elog_register_filter
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


echo "=== Filter default ===\n";
var_dump(elog_get_filter());


echo "=== Filter function ===\n";

function filter_test($val) {
    return $val;
}

var_dump(elog_register_filter('function_filter', 'filter_test'));
var_dump(elog_get_filter());


echo "=== Filter class ===\n";
class Test {
    public function hoge($val) {
        return $val;
    }
    static public function foo($val) {
        return $val;
    }
}
$test = new Test;

var_dump(elog_register_filter('method_filter', array($test, 'hoge')));
var_dump(elog_register_filter('static_method_filter', 'Test::foo'));
var_dump(elog_get_filter());


echo "=== Filter closure ===\n";

var_dump(elog_register_filter('closure_filter',
                              function($val) { return $val; }));
var_dump(elog_get_filter());
?>
--EXPECTF--
=== Filter default ===
array(4) {
  ["builtin"]=>
  array(5) {
    [0]=>
    string(24) "elog_filter_add_fileline"
    [1]=>
    string(25) "elog_filter_add_timestamp"
    [2]=>
    string(23) "elog_filter_add_request"
    [3]=>
    string(21) "elog_filter_add_level"
    [4]=>
    string(21) "elog_filter_add_trace"
  }
  ["registers"]=>
  array(0) {
  }
  ["execute"]=>
  array(0) {
  }
  ["enabled"]=>
  array(0) {
  }
}
=== Filter function ===
bool(true)
array(4) {
  ["builtin"]=>
  array(5) {
    [0]=>
    string(24) "elog_filter_add_fileline"
    [1]=>
    string(25) "elog_filter_add_timestamp"
    [2]=>
    string(23) "elog_filter_add_request"
    [3]=>
    string(21) "elog_filter_add_level"
    [4]=>
    string(21) "elog_filter_add_trace"
  }
  ["registers"]=>
  array(1) {
    [0]=>
    string(15) "function_filter"
  }
  ["execute"]=>
  array(0) {
  }
  ["enabled"]=>
  array(0) {
  }
}
=== Filter class ===
bool(true)
bool(true)
array(4) {
  ["builtin"]=>
  array(5) {
    [0]=>
    string(24) "elog_filter_add_fileline"
    [1]=>
    string(25) "elog_filter_add_timestamp"
    [2]=>
    string(23) "elog_filter_add_request"
    [3]=>
    string(21) "elog_filter_add_level"
    [4]=>
    string(21) "elog_filter_add_trace"
  }
  ["registers"]=>
  array(3) {
    [0]=>
    string(15) "function_filter"
    [1]=>
    string(13) "method_filter"
    [2]=>
    string(20) "static_method_filter"
  }
  ["execute"]=>
  array(0) {
  }
  ["enabled"]=>
  array(0) {
  }
}
=== Filter closure ===
bool(true)
array(4) {
  ["builtin"]=>
  array(5) {
    [0]=>
    string(24) "elog_filter_add_fileline"
    [1]=>
    string(25) "elog_filter_add_timestamp"
    [2]=>
    string(23) "elog_filter_add_request"
    [3]=>
    string(21) "elog_filter_add_level"
    [4]=>
    string(21) "elog_filter_add_trace"
  }
  ["registers"]=>
  array(4) {
    [0]=>
    string(15) "function_filter"
    [1]=>
    string(13) "method_filter"
    [2]=>
    string(20) "static_method_filter"
    [3]=>
    string(14) "closure_filter"
  }
  ["execute"]=>
  array(0) {
  }
  ["enabled"]=>
  array(0) {
  }
}
