--TEST--
elog_filter: to_json and add_fileline,add_timestamp,add_level
--INI--
date.timezone=Asia/Tokyo
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_054.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_to_json ]\n";
var_dump(elog_append_filter('elog_filter_to_json'));

function test($out) {
    elog('dummy');
    echo "=== normal ===\n";
    file_dump($out);
    echo "\n";

    elog_err('dummy');
    echo "=== err ===\n";
    file_dump($out);
    echo "\n";
}

test($log);

echo "\n[ append: elog_filter_add_fileline ]\n";
var_dump(elog_append_filter('elog_filter_add_fileline'));
test($log);

echo "\n[ append: elog_filter_add_timestamp ]\n";
var_dump(elog_append_filter('elog_filter_add_timestamp'));
test($log);

echo "\n[ append: elog_filter_add_level ]\n";
var_dump(elog_append_filter('elog_filter_add_level'));
test($log);

?>
--EXPECTF--
[ append: elog_filter_to_json ]
bool(true)
=== normal ===
{"message":"dummy"}
=== err ===
{"message":"dummy"}

[ append: elog_filter_add_fileline ]
bool(true)
=== normal ===
{"message":"dummy","elog_file":"%s/054.php","elog_line":16}
=== err ===
{"message":"dummy","elog_file":"%s/054.php","elog_line":21}

[ append: elog_filter_add_timestamp ]
bool(true)
=== normal ===
{"message":"dummy","elog_file":"%s/054.php","elog_line":16,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo"}
=== err ===
{"message":"dummy","elog_file":"%s/054.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo"}

[ append: elog_filter_add_level ]
bool(true)
=== normal ===
{"message":"dummy","elog_file":"%s/054.php","elog_line":16,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo"}
=== err ===
{"message":"dummy","elog_file":"%s/054.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_level":"ERR"}
