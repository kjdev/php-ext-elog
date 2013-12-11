--TEST--
elog_filter: to=json and add_fileline,add_timestamp,add_level
--INI--
date.timezone=Asia/Tokyo
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_054.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

ini_set('elog.to', 'json');

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
=== normal ===
{"message":"dummy"}
=== err ===
{"message":"dummy"}

[ append: elog_filter_add_fileline ]
bool(true)
=== normal ===
{"message":"dummy","file":"%s/054.php","line":12}
=== err ===
{"message":"dummy","file":"%s/054.php","line":17}

[ append: elog_filter_add_timestamp ]
bool(true)
=== normal ===
{"message":"dummy","file":"%s/054.php","line":12,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo"}
=== err ===
{"message":"dummy","file":"%s/054.php","line":17,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo"}

[ append: elog_filter_add_level ]
bool(true)
=== normal ===
{"message":"dummy","file":"%s/054.php","line":12,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo"}
=== err ===
{"message":"dummy","file":"%s/054.php","line":17,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","level":"ERR"}
