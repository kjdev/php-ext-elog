--TEST--
elog_filter: ini=elog.filter_execute
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_056.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

function fn_1st($val) {
    if (is_scalar($val)) {
        return $val . "[" . __FUNCTION__ . "]\n";
    } else {
        return var_export($val, true) . "[" . __FUNCTION__ . "]\n";
    }
}

class Test {
    public function me_2nd($val) {
        return $val . "[" . __METHOD__ . "]\n";
    }
    static public function st_me_3rd($val) {
        return $val . "[" . __METHOD__ . "]\n";
    }
}

$closure_4th = function($val) { return $val . "[closure_4th]\n"; };

$test = new Test;

elog_register_filter('fn_1st', 'fn_1st');
elog_register_filter('me_2nd', array($test, 'me_2nd'));
elog_register_filter('st_me_3rd', 'Test::st_me_3rd');
elog_register_filter('cj_4th', $closure_4th);

function test($out) {
    echo "=== elog_get_filter: execute ===\n";
    var_dump(elog_get_filter('execute'));
    echo "=== elog_get_filter: enabled ===\n";
    var_dump(elog_get_filter('enabled'));
    echo "=== elog.filter_execute ===\n";
    echo ini_get('elog.filter_execute'), "\n";
    foreach (array('dummy', array('dummy'), new stdClass) as $val) {
        echo "=== dump ===\n";
        var_dump($val);
        elog($val);
        echo "=== output ===\n";
        file_dump($out);
    }
}

echo "\n[ Test 1 ]\n";
test($log);

echo "\n[ Test 2 ]\n";
ini_set('elog.filter_execute', 'elog_filter_add_fileline');
test($log);

echo "\n[ Test 3 ]\n";
ini_set('elog.filter_execute', 'fn_1st, me_2nd ,elog_filter_add_level , elog_filter_add_fileline');
test($log);

echo "\n[ Test 4 ]\n";
elog_append_filter('fn_1st');
elog_append_filter('st_me_3rd');

ini_set('elog.filter_execute', 'me_2nd,elog_filter_add_fileline,cj_4th');
test($log);

?>
--EXPECTF--
[ Test 1 ]
=== elog_get_filter: execute ===
array(0) {
}
=== elog_get_filter: enabled ===
array(0) {
}
=== elog.filter_execute ===

=== dump ===
string(5) "dummy"
=== output ===
dummy
=== dump ===
array(1) {
  [0]=>
  string(5) "dummy"
}
=== output ===
[
  "dummy"
]
=== dump ===
object(stdClass)#%d (0) {
}
=== output ===
stdClass {
}

[ Test 2 ]
=== elog_get_filter: execute ===
array(1) {
  [0]=>
  string(24) "elog_filter_add_fileline"
}
=== elog_get_filter: enabled ===
array(1) {
  [0]=>
  string(24) "elog_filter_add_fileline"
}
=== elog.filter_execute ===
elog_filter_add_fileline
=== dump ===
string(5) "dummy"
=== output ===
dummy
file: %s/056.php
line: 45
=== dump ===
array(1) {
  [0]=>
  string(5) "dummy"
}
=== output ===
[
  "dummy"
]
file: %s/056.php
line: 45
=== dump ===
object(stdClass)#%d (0) {
}
=== output ===
stdClass {
}
file: %s/056.php
line: 45

[ Test 3 ]
=== elog_get_filter: execute ===
array(4) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(6) "me_2nd"
  [2]=>
  string(21) "elog_filter_add_level"
  [3]=>
  string(24) "elog_filter_add_fileline"
}
=== elog_get_filter: enabled ===
array(4) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(6) "me_2nd"
  [2]=>
  string(21) "elog_filter_add_level"
  [3]=>
  string(24) "elog_filter_add_fileline"
}
=== elog.filter_execute ===
fn_1st, me_2nd ,elog_filter_add_level , elog_filter_add_fileline
=== dump ===
string(5) "dummy"
=== output ===
dummy[fn_1st]
[Test::me_2nd]
file: %s/056.php
line: 45
=== dump ===
array(1) {
  [0]=>
  string(5) "dummy"
}
=== output ===
array (
  0 => 'dummy',
)[fn_1st]
[Test::me_2nd]
file: %s/056.php
line: 45
=== dump ===
object(stdClass)#%d (0) {
}
=== output ===
stdClass::__set_state(array(
))[fn_1st]
[Test::me_2nd]
file: %s/056.php
line: 45

[ Test 4 ]
=== elog_get_filter: execute ===
array(3) {
  [0]=>
  string(6) "me_2nd"
  [1]=>
  string(24) "elog_filter_add_fileline"
  [2]=>
  string(6) "cj_4th"
}
=== elog_get_filter: enabled ===
array(5) {
  [0]=>
  string(6) "fn_1st"
  [1]=>
  string(9) "st_me_3rd"
  [2]=>
  string(6) "me_2nd"
  [3]=>
  string(24) "elog_filter_add_fileline"
  [4]=>
  string(6) "cj_4th"
}
=== elog.filter_execute ===
me_2nd,elog_filter_add_fileline,cj_4th
=== dump ===
string(5) "dummy"
=== output ===
dummy[fn_1st]
[Test::st_me_3rd]
[Test::me_2nd]
[closure_4th]
file: %s/056.php
line: 45
=== dump ===
array(1) {
  [0]=>
  string(5) "dummy"
}
=== output ===
array (
  0 => 'dummy',
)[fn_1st]
[Test::st_me_3rd]
[Test::me_2nd]
[closure_4th]
file: %s/056.php
line: 45
=== dump ===
object(stdClass)#%d (0) {
}
=== output ===
stdClass::__set_state(array(
))[fn_1st]
[Test::st_me_3rd]
[Test::me_2nd]
[closure_4th]
file: %s/056.php
line: 45
