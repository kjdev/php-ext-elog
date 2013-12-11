--TEST--
elog_register_filter: EL_FILTER_APPEND or EL_FILTER_PREPEND
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

echo "=== Filter default ===\n";
var_dump(elog_get_filter());

echo "=== Filter register&append: function_filter ===\n";
var_dump(elog_register_filter('function_filter', 'filter_test', EL_FILTER_APPEND));
var_dump(elog_get_filter());

echo "=== Filter register&prepend: method_filter ===\n";
var_dump(elog_register_filter('method_filter', array($test, 'hoge'), EL_FILTER_PREPEND));
var_dump(elog_get_filter());

echo "=== Filter register&append: static_method_filter ===\n";
var_dump(elog_register_filter('static_method_filter', 'Test::foo', EL_FILTER_APPEND));
var_dump(elog_get_filter());

echo "=== Filter register&prepend: closure_filter ===\n";
var_dump(elog_register_filter('closure_filter', function($val) { return $val; }, EL_FILTER_PREPEND));
var_dump(elog_get_filter());

echo "=== Filter register: duplicate ===\n";
var_dump(elog_append_filter("function_filter"));
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
=== Filter register&append: function_filter ===
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
  array(1) {
    [0]=>
    string(15) "function_filter"
  }
}
=== Filter register&prepend: method_filter ===
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
  array(2) {
    [0]=>
    string(15) "function_filter"
    [1]=>
    string(13) "method_filter"
  }
  ["execute"]=>
  array(0) {
  }
  ["enabled"]=>
  array(2) {
    [0]=>
    string(13) "method_filter"
    [1]=>
    string(15) "function_filter"
  }
}
=== Filter register&append: static_method_filter ===
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
  array(3) {
    [0]=>
    string(13) "method_filter"
    [1]=>
    string(15) "function_filter"
    [2]=>
    string(20) "static_method_filter"
  }
}
=== Filter register&prepend: closure_filter ===
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
    string(14) "closure_filter"
    [1]=>
    string(13) "method_filter"
    [2]=>
    string(15) "function_filter"
    [3]=>
    string(20) "static_method_filter"
  }
}
=== Filter register: duplicate ===

Warning: elog_append_filter(): Already exists filter "function_filter" in %s on line %d
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
  array(4) {
    [0]=>
    string(14) "closure_filter"
    [1]=>
    string(13) "method_filter"
    [2]=>
    string(15) "function_filter"
    [3]=>
    string(20) "static_method_filter"
  }
}