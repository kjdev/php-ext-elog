--TEST--
elog filter: mixed
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_041.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);


function fn_1st($val) {
    return str_replace("\n", "", $val) . "[" . __FUNCTION__ . "]\n";
}
function fn_2nd($val) {
    return str_replace("\n", "", $val) . "[" . __FUNCTION__ . "]\n";
}

class Test {
    public function me_1st($val) {
        return str_replace("\n", "", $val) . "[" . __METHOD__ . "]\n";
    }
    public function me_2nd($val) {
        return str_replace("\n", "", $val) . "[" . __METHOD__ . "]\n";
    }
    static public function st_me_1st($val) {
        return str_replace("\n", "", $val) . "[" . __METHOD__ . "]\n";
    }
    static public function st_me_2nd($val) {
        return str_replace("\n", "", $val) . "[" . __METHOD__ . "]\n";
    }
}

$closure_1st = function($val) { return str_replace("\n", "", $val) . "[closure_1st]\n"; };

$test = new Test;

function test($out) {
    var_dump(elog_get_filter('enabled'));

    elog("dummy\n");

    echo "=== output ===\n";
    file_dump($out);
}

echo "\n[ default ]\n";
test($log);

echo "\n[ Filter : append 1st ]\n";
elog_register_filter('fn_1st', 'fn_1st', EL_FILTER_APPEND);
elog_register_filter('me_1st', array($test, 'me_1st'), EL_FILTER_APPEND);
elog_register_filter('st_me_1st', 'Test::st_me_1st', EL_FILTER_APPEND);
elog_register_filter('cj_1st', $closure_1st, EL_FILTER_APPEND);
test($log);

echo "\n[ Filter : prepend 2nd ]\n";
elog_register_filter('fn_2nd', 'fn_2nd', EL_FILTER_PREPEND);
elog_register_filter('me_2nd', array($test, 'me_2nd'), EL_FILTER_PREPEND);
elog_register_filter('st_me_2nd', 'Test::st_me_2nd', EL_FILTER_PREPEND);
elog_register_filter('cj_2nd', function($val) { return str_replace("\n", "", $val) . "[closure_2nd]\n"; }, EL_FILTER_PREPEND);
test($log);

echo "\n[ Filter : remove ALL ]\n";
foreach (elog_get_filter('enabled') as $name) {
    elog_remove_filter($name);
}
test($log);

echo "\n[ Filter : prepend 1st ]\n";
elog_register_filter('fn_1st', 'fn_1st');
elog_register_filter('me_1st', array($test, 'me_1st'));
elog_register_filter('st_me_1st', 'Test::st_me_1st');
elog_register_filter('cj_1st', $closure_1st);
elog_prepend_filter('fn_1st');
elog_prepend_filter('me_1st');
elog_prepend_filter(array('st_me_1st', 'cj_1st'));
test($log);

echo "\n[ Filter : append 2nd ]\n";
elog_register_filter('fn_2nd', 'fn_2nd');
elog_register_filter('me_2nd', array($test, 'me_2nd'));
elog_register_filter('st_me_2nd', 'Test::st_me_2nd');
elog_register_filter('cj_2nd', function($val) { return str_replace("\n", "", $val) . "[closure_2nd]\n"; });
elog_append_filter('fn_2nd');
elog_append_filter('me_2nd');
elog_append_filter(array('st_me_2nd', 'cj_2nd'));
test($log);

?>
--EXPECTF--
[ default ]
array(0) {
}
=== output ===
dummy

[ Filter : append 1st ]
array(4) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(6) "me_1st"
  [2]=>
  string(9) "st_me_1st"
  [3]=>
  string(6) "cj_1st"
}
=== output ===
dummy[fn_1st][Test::me_1st][Test::st_me_1st][closure_1st]

[ Filter : prepend 2nd ]
array(8) {
  [0]=>
  string(6) "cj_2nd"
  [1]=>
  string(9) "st_me_2nd"
  [2]=>
  string(6) "me_2nd"
  [3]=>
  string(6) "fn_2nd"
  [4]=>
  string(6) "fn_1st"
  [5]=>
  string(6) "me_1st"
  [6]=>
  string(9) "st_me_1st"
  [7]=>
  string(6) "cj_1st"
}
=== output ===
dummy[closure_2nd][Test::st_me_2nd][Test::me_2nd][fn_2nd][fn_1st][Test::me_1st][Test::st_me_1st][closure_1st]

[ Filter : remove ALL ]
array(0) {
}
=== output ===
dummy

[ Filter : prepend 1st ]
array(4) {
  [0]=>
  string(9) "st_me_1st"
  [1]=>
  string(6) "cj_1st"
  [2]=>
  string(6) "me_1st"
  [3]=>
  string(6) "fn_1st"
}
=== output ===
dummy[Test::st_me_1st][closure_1st][Test::me_1st][fn_1st]

[ Filter : append 2nd ]
array(8) {
  [0]=>
  string(9) "st_me_1st"
  [1]=>
  string(6) "cj_1st"
  [2]=>
  string(6) "me_1st"
  [3]=>
  string(6) "fn_1st"
  [4]=>
  string(6) "fn_2nd"
  [5]=>
  string(6) "me_2nd"
  [6]=>
  string(9) "st_me_2nd"
  [7]=>
  string(6) "cj_2nd"
}
=== output ===
dummy[Test::st_me_1st][closure_1st][Test::me_1st][fn_1st][fn_2nd][Test::me_2nd][Test::st_me_2nd][closure_2nd]
