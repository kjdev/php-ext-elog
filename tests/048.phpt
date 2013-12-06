--TEST--
elog_filter_add_timestamp
--INI--
date.timezone=Asia/Tokyo
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_048.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_timestamp ]\n";
var_dump(elog_append_filter('elog_filter_add_timestamp'));

echo "\n[ default ]\n";
elog('dummy');
echo "=== output ===\n";
file_dump($log);
echo "\n";

echo "\n[ boolean ]\n";
elog(true);
echo "=== output ===\n";
file_dump($log);
echo "\n";

echo "\n[ integer ]\n";
elog(12345);
echo "=== output ===\n";
file_dump($log);
echo "\n";

function test($format, $out) {
    echo "\n[ elog.filter_timestamp_format: $format ]\n";
    ini_set('elog.filter_timestamp_format', $format);
    elog('dummy');
    echo "=== output ===\n";
    $buf = '';
    file_dump($out, $buf);
    echo "\n";

    foreach (explode(PHP_EOL, $buf) as $line) {
        $pos = stripos($line, 'elog_time:');
        if ($pos !== false) {
            $time = trim(substr($line, $pos+10));
            if (strcmp(date($format), $time) == 0) {
                echo "TimeFormat: OK\n";
            }
        }
    }
}

test('F j, Y, g:i a', $log);
test('m.d.y', $log);
test('j, n, Y', $log);
test('Ymd', $log);
test('h-i, j-m-y, it is w Day', $log);
test('\i\t \i\s \t\h\e jS \d\a\y.', $log);
test('D M j G:i T Y', $log);
test('H:m \m \i\s\ \m\o\n\t\h', $log);
test('H:i', $log);
test('Y-m-d H:i', $log);

?>
--EXPECTF--
[ append: elog_filter_add_timestamp ]
bool(true)

[ default ]
=== output ===
dummy
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo

[ boolean ]
=== output ===
1
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo

[ integer ]
=== output ===
12345
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo

[ elog.filter_timestamp_format: F j, Y, g:i a ]
=== output ===
dummy
elog_time: %s %d, %d, %d:%d %s
TimeFormat: OK

[ elog.filter_timestamp_format: m.d.y ]
=== output ===
dummy
elog_time: %d.%d.%d
TimeFormat: OK

[ elog.filter_timestamp_format: j, n, Y ]
=== output ===
dummy
elog_time: %d, %d, %d
TimeFormat: OK

[ elog.filter_timestamp_format: Ymd ]
=== output ===
dummy
elog_time: %d
TimeFormat: OK

[ elog.filter_timestamp_format: h-i, j-m-y, it is w Day ]
=== output ===
dummy
elog_time: %d-%d, %d-%d-%d, %d %d %d %s
TimeFormat: OK

[ elog.filter_timestamp_format: \i\t \i\s \t\h\e jS \d\a\y. ]
=== output ===
dummy
elog_time: it is the %s day.
TimeFormat: OK

[ elog.filter_timestamp_format: D M j G:i T Y ]
=== output ===
dummy
elog_time: %s %s %d %d:%d %s %d
TimeFormat: OK

[ elog.filter_timestamp_format: H:m \m \i\s\ \m\o\n\t\h ]
=== output ===
dummy
elog_time: %d:%d m is month
TimeFormat: OK

[ elog.filter_timestamp_format: H:i ]
=== output ===
dummy
elog_time: %d:%d
TimeFormat: OK

[ elog.filter_timestamp_format: Y-m-d H:i ]
=== output ===
dummy
elog_time: %d-%d-%d %d:%d
TimeFormat: OK
