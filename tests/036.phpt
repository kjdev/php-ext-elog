--TEST--
elog_get_filter
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


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

elog_register_filter('function_filter', 'filter_test', EL_FILTER_APPEND);
elog_register_filter('method_filter', array($test, 'hoge'), EL_FILTER_APPEND);
elog_register_filter('static_method_filter', 'Test::foo', EL_FILTER_APPEND);
elog_register_filter('closure_filter', function($val) { return $val; }, EL_FILTER_APPEND);

echo "=== Filter get ===\n";
var_dump(elog_get_filter());

echo "=== Filter get: builtin ===\n";
var_dump(elog_get_filter('builtin'));

echo "=== Filter get: registers ===\n";
var_dump(elog_get_filter('registers'));

echo "=== Filter get: execute ===\n";
var_dump(elog_get_filter('execute'));

echo "=== Filter get: enabled ===\n";
var_dump(elog_get_filter('enabled'));

echo "=== Filter get: hoge ===\n";
var_dump(elog_get_filter('hoge'));

?>
--EXPECTF--
=== Filter get ===
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
    string(15) "function_filter"
    [1]=>
    string(13) "method_filter"
    [2]=>
    string(20) "static_method_filter"
    [3]=>
    string(14) "closure_filter"
  }
}
=== Filter get: builtin ===
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
=== Filter get: registers ===
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
=== Filter get: execute ===
array(0) {
}
=== Filter get: enabled ===
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
=== Filter get: hoge ===
array(0) {
}
