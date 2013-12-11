--TEST--
elog_filter_add_fileline
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_046.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_fileline ]\n";
var_dump(elog_append_filter('elog_filter_add_fileline'));

echo "\n[ Test 1 ]\n";
elog('dummy');
echo "=== output ===\n";
file_dump($log);

echo "\n[ Test 2 ]\n";
elog(true);
echo "=== output ===\n";
file_dump($log);

echo "\n[ Test 3 ]\n";
elog(12345);
echo "=== output ===\n";
file_dump($log);

?>
--EXPECTF--
[ append: elog_filter_add_fileline ]
bool(true)

[ Test 1 ]
=== output ===
dummy
file: %s/046.php
line: 13

[ Test 2 ]
=== output ===
true
file: %s/046.php
line: 18

[ Test 3 ]
=== output ===
12345
file: %s/046.php
line: 23
