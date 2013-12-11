--TEST--
elog_remove_filter: invalid arguments
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

echo "=== Filter remove ===\n";

var_dump(elog_remove_filter("hoge"));
var_dump(elog_remove_filter(""));
var_dump(elog_remove_filter(array()));
var_dump(elog_remove_filter(array("function_filter", "method_filter")));
var_dump(elog_remove_filter(null));

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
=== Filter remove ===
bool(false)

Warning: elog_remove_filter(): Filter name cannot be empty in %s on line %d
bool(false)

Warning: elog_remove_filter() expects parameter 1 to be string, array given in %s on line %d
bool(false)

Warning: elog_remove_filter() expects parameter 1 to be string, array given in %s on line %d
bool(false)

Warning: elog_remove_filter(): Filter name cannot be empty in %s on line %d
bool(false)
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
