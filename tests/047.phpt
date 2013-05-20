--TEST--
elog_filter_add_fileline: array or object
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_047.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_fileline ]\n";
var_dump(elog_append_filter('elog_filter_add_fileline'));

echo "[ append: elog_filter_to_string ]\n";
var_dump(elog_append_filter('elog_filter_to_string'));

echo "\n[ Array ]\n";
$var = array('dummy');
var_dump($var);
elog($var);
echo "=== output ===\n";
file_dump($log);

echo "\n[ Array ]\n";
$var = array('dummy' => 'DUMMY');
var_dump($var);
elog($var);
echo "=== output ===\n";
file_dump($log);

echo "\n[ Object ]\n";
$var = new stdClass;
$var->dummy = 'DUMMY';
var_dump($var);
elog($var);
echo "=== output ===\n";
file_dump($log);

?>
--EXPECTF--
[ append: elog_filter_add_fileline ]
bool(true)
[ append: elog_filter_to_string ]
bool(true)

[ Array ]
array(1) {
  [0]=>
  string(5) "dummy"
}
=== output ===
{
  0: "dummy"
  "elog_file": "%s/047.php"
  "elog_line": 21
}
[ Array ]
array(1) {
  ["dummy"]=>
  string(5) "DUMMY"
}
=== output ===
{
  "dummy": "DUMMY"
  "elog_file": "%s/047.php"
  "elog_line": 28
}
[ Object ]
object(stdClass)#%d (1) {
  ["dummy"]=>
  string(5) "DUMMY"
}
=== output ===
stdClass {
  "dummy": "DUMMY"
  "elog_file": "%s/047.php"
  "elog_line": 36
}
