--TEST--
elog_filter_add_timestamp: array or object
--INI--
date.timezone=Asia/Tokyo
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_049.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_timestamp ]\n";
var_dump(elog_append_filter('elog_filter_add_timestamp'));

function test($format, $var, $out) {
    ini_set('elog.filter_timestamp_format', $format);
    elog($var);
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);

    foreach (explode(PHP_EOL, $buf) as $line) {
        $pos = stripos($line, 'time:');
        if ($pos !== false) {
            $time = trim(substr($line, $pos+6));
            $time = str_replace('"', '', $time);
            if (strcmp(date($format), $time) == 0) {
                echo "TimeFormat: OK\n";
            }
        }
    }
}



echo "\n[ Array ]\n";
$var = array('dummy');
test('Y-m-d H:i', $var, $log);

echo "\n[ Array ]\n";
$var = array('dummy' => 'DUMMY');
test('Y-m-d H:i', $var, $log);

echo "\n[ Object ]\n";
$var = new stdClass;
$var->dummy = 'DUMMY';
test('Y-m-d H:i', $var, $log);

?>
--EXPECTF--
[ append: elog_filter_add_timestamp ]
bool(true)

[ Array ]
=== output ===
[
  "dummy"
]
time: %d-%d-%d %d:%d
TimeFormat: OK

[ Array ]
=== output ===
{
  "dummy": "DUMMY"
}
time: %d-%d-%d %d:%d
TimeFormat: OK

[ Object ]
=== output ===
stdClass {
  "dummy": "DUMMY"
}
time: %d-%d-%d %d:%d
TimeFormat: OK
