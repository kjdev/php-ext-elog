--TEST--
elog_remove_filter
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


echo "=== Filter register ===\n";
function filter_test($val) {
    return $val;
}
class Test {
    public function hoge($val) {
        return $val;
    }
    static public function foo($val) {
        return $val;
    }
}
$test = new Test;
elog_register_filter('function_filter', 'filter_test');
elog_register_filter('method_filter', array($test, 'hoge'));
elog_register_filter('static_method_filter', 'Test::foo');
elog_register_filter('closure_filter', function($val) { return $val; });

elog_append_filter(
    array("elog_filter_add_fileline", "elog_filter_add_level",
          "function_filter", "method_filter", "closure_filter"));
var_dump(elog_get_filter());

echo "=== Filter remove: elog_filter_add_fileline ===\n";
var_dump(elog_remove_filter("elog_filter_add_fileline"));
var_dump(elog_get_filter());

echo "=== Filter remove: function_filter ===\n";
var_dump(elog_remove_filter("function_filter"));
var_dump(elog_get_filter());

echo "=== Filter remove: closure_filter ===\n";
var_dump(elog_remove_filter("closure_filter"));
var_dump(elog_get_filter());

?>
--EXPECTF--
=== Filter register ===
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
  array(5) {
    [0]=>
    string(24) "elog_filter_add_fileline"
    [1]=>
    string(21) "elog_filter_add_level"
    [2]=>
    string(15) "function_filter"
    [3]=>
    string(13) "method_filter"
    [4]=>
    string(14) "closure_filter"
  }
}
=== Filter remove: elog_filter_add_fileline ===
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
  array(4) {
    [0]=>
    string(21) "elog_filter_add_level"
    [1]=>
    string(15) "function_filter"
    [2]=>
    string(13) "method_filter"
    [3]=>
    string(14) "closure_filter"
  }
}
=== Filter remove: function_filter ===
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
  array(3) {
    [0]=>
    string(21) "elog_filter_add_level"
    [1]=>
    string(13) "method_filter"
    [2]=>
    string(14) "closure_filter"
  }
}
=== Filter remove: closure_filter ===
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
  array(2) {
    [0]=>
    string(21) "elog_filter_add_level"
    [1]=>
    string(13) "method_filter"
  }
}
