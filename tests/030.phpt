--TEST--
elog_prepend_filter
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
var_dump(elog_get_filter());

echo "=== Filter prepend: elog_filter_to_string ===\n";
var_dump(elog_prepend_filter("elog_filter_to_string"));
var_dump(elog_get_filter());

echo "=== Filter prepend: function_filter ===\n";
var_dump(elog_prepend_filter("function_filter"));
var_dump(elog_get_filter());

echo "=== Filter prepend: method_filter ===\n";
var_dump(elog_prepend_filter("method_filter"));
var_dump(elog_get_filter());

echo "=== Filter prepend: closure_filter ===\n";
var_dump(elog_prepend_filter("closure_filter"));
var_dump(elog_get_filter());

echo "=== Filter prepend: duplicate elog_filter_to_string ===\n";
var_dump(elog_prepend_filter("elog_filter_to_string"));
var_dump(elog_get_filter());

?>
--EXPECTF--
=== Filter register ===
array(4) {
  ["builtin"]=>
  array(9) {
    [0]=>
    string(21) "elog_filter_to_string"
    [1]=>
    string(19) "elog_filter_to_json"
    [2]=>
    string(25) "elog_filter_to_http_query"
    [3]=>
    string(20) "elog_filter_to_array"
    [4]=>
    string(19) "elog_filter_add_eol"
    [5]=>
    string(24) "elog_filter_add_fileline"
    [6]=>
    string(25) "elog_filter_add_timestamp"
    [7]=>
    string(23) "elog_filter_add_request"
    [8]=>
    string(21) "elog_filter_add_level"
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
=== Filter prepend: elog_filter_to_string ===
bool(true)
array(4) {
  ["builtin"]=>
  array(9) {
    [0]=>
    string(21) "elog_filter_to_string"
    [1]=>
    string(19) "elog_filter_to_json"
    [2]=>
    string(25) "elog_filter_to_http_query"
    [3]=>
    string(20) "elog_filter_to_array"
    [4]=>
    string(19) "elog_filter_add_eol"
    [5]=>
    string(24) "elog_filter_add_fileline"
    [6]=>
    string(25) "elog_filter_add_timestamp"
    [7]=>
    string(23) "elog_filter_add_request"
    [8]=>
    string(21) "elog_filter_add_level"
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
  array(1) {
    [0]=>
    string(21) "elog_filter_to_string"
  }
}
=== Filter prepend: function_filter ===
bool(true)
array(4) {
  ["builtin"]=>
  array(9) {
    [0]=>
    string(21) "elog_filter_to_string"
    [1]=>
    string(19) "elog_filter_to_json"
    [2]=>
    string(25) "elog_filter_to_http_query"
    [3]=>
    string(20) "elog_filter_to_array"
    [4]=>
    string(19) "elog_filter_add_eol"
    [5]=>
    string(24) "elog_filter_add_fileline"
    [6]=>
    string(25) "elog_filter_add_timestamp"
    [7]=>
    string(23) "elog_filter_add_request"
    [8]=>
    string(21) "elog_filter_add_level"
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
    string(15) "function_filter"
    [1]=>
    string(21) "elog_filter_to_string"
  }
}
=== Filter prepend: method_filter ===
bool(true)
array(4) {
  ["builtin"]=>
  array(9) {
    [0]=>
    string(21) "elog_filter_to_string"
    [1]=>
    string(19) "elog_filter_to_json"
    [2]=>
    string(25) "elog_filter_to_http_query"
    [3]=>
    string(20) "elog_filter_to_array"
    [4]=>
    string(19) "elog_filter_add_eol"
    [5]=>
    string(24) "elog_filter_add_fileline"
    [6]=>
    string(25) "elog_filter_add_timestamp"
    [7]=>
    string(23) "elog_filter_add_request"
    [8]=>
    string(21) "elog_filter_add_level"
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
    string(13) "method_filter"
    [1]=>
    string(15) "function_filter"
    [2]=>
    string(21) "elog_filter_to_string"
  }
}
=== Filter prepend: closure_filter ===
bool(true)
array(4) {
  ["builtin"]=>
  array(9) {
    [0]=>
    string(21) "elog_filter_to_string"
    [1]=>
    string(19) "elog_filter_to_json"
    [2]=>
    string(25) "elog_filter_to_http_query"
    [3]=>
    string(20) "elog_filter_to_array"
    [4]=>
    string(19) "elog_filter_add_eol"
    [5]=>
    string(24) "elog_filter_add_fileline"
    [6]=>
    string(25) "elog_filter_add_timestamp"
    [7]=>
    string(23) "elog_filter_add_request"
    [8]=>
    string(21) "elog_filter_add_level"
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
    string(21) "elog_filter_to_string"
  }
}
=== Filter prepend: duplicate elog_filter_to_string ===

Warning: elog_prepend_filter(): Already exists filter "elog_filter_to_string" in %s on line %d
bool(false)
array(4) {
  ["builtin"]=>
  array(9) {
    [0]=>
    string(21) "elog_filter_to_string"
    [1]=>
    string(19) "elog_filter_to_json"
    [2]=>
    string(25) "elog_filter_to_http_query"
    [3]=>
    string(20) "elog_filter_to_array"
    [4]=>
    string(19) "elog_filter_add_eol"
    [5]=>
    string(24) "elog_filter_add_fileline"
    [6]=>
    string(25) "elog_filter_add_timestamp"
    [7]=>
    string(23) "elog_filter_add_request"
    [8]=>
    string(21) "elog_filter_add_level"
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
    string(21) "elog_filter_to_string"
  }
}
