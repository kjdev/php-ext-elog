--TEST--
override error_handler: system INI
--INI--
date.timezone=Asia/Tokyo
log_errors=On
error_log="tests/tmp_063_0.log"
elog.default_type=3
elog.default_destination="tests/tmp_063_1.log"
elog.override_error_handler=On
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log_1 = dirname(__FILE__) . "/tmp_063_0.log";
$log_2 = dirname(__FILE__) . "/tmp_063_1.log";

function test($out1, $out2) {
    echo $aa;

    echo "=== error_log ===\n";
    file_dump($out1);
    echo "\n";

    echo "=== elog ===\n";
    file_dump($out2);
    echo "\n";
}

test($log_1, $log_2);

echo "[ append: elog_filter_add_fileline ]\n";
var_dump(elog_get_filter('enabled'));
elog_append_filter('elog_filter_add_fileline');
test($log_1, $log_2);

echo "[ to: json ]\n";
echo "[ execute: elog_filter_add_fileline ]\n";
elog_remove_filter('elog_filter_add_fileline');
ini_set('elog.to', 'json');
ini_set('elog.filter_execute', 'elog_filter_add_fileline');
var_dump(elog_get_filter('enabled'));

test($log_1, $log_2);

?>
--EXPECTF--
Notice: Undefined variable: aa in %s/063.php on line 9
=== error_log ===
[%s Asia/Tokyo] PHP Notice:  Undefined variable: aa in %s/063.php on line 9

=== elog ===
PHP Notice:  Undefined variable: aa in %s/063.php on line 9

[ append: elog_filter_add_fileline ]
array(0) {
}

Notice: Undefined variable: aa in %s/063.php on line 9
=== error_log ===
[%s Asia/Tokyo] PHP Notice:  Undefined variable: aa in %s/063.php on line 9

=== elog ===
PHP Notice:  Undefined variable: aa in %s/063.php on line 9
file: %s/063.php
line: 9

[ to: json ]
[ execute: elog_filter_add_fileline ]
array(1) {
  [0]=>
  string(24) "elog_filter_add_fileline"
}

Notice: Undefined variable: aa in %s/063.php on line 9
=== error_log ===
[%s Asia/Tokyo] PHP Notice:  Undefined variable: aa in %s/063.php on line 9

=== elog ===
{"message":"PHP Notice:  Undefined variable: aa in %s/063.php on line 9","file":"%s/063.php","line":9}
