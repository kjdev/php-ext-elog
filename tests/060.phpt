--TEST--
elog_filter: system=INI elog.filter_label
--INI--
date.timezone=Asia/Tokyo
elog.filter_label_scalar="msg"
elog.filter_label_file="file"
elog.filter_label_line="line"
elog.filter_label_timestamp="time"
elog.filter_label_level="level"
elog.filter_label_request="req"
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_060.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

elog_append_filter('elog_filter_to_json');
elog_append_filter('elog_filter_add_fileline');
elog_append_filter('elog_filter_add_timestamp');
elog_append_filter('elog_filter_add_level');
elog_append_filter('elog_filter_add_request');

$_REQUEST = array('dummy' => 'DUMMY');

function test($out) {
    elog('dummy');
    file_dump($out);
    echo "\n";
    elog_err(array('dummy'));
    file_dump($out);
    echo "\n";
}

$ini = array('elog.filter_label_scalar',
             'elog.filter_label_file',
             'elog.filter_label_line',
             'elog.filter_label_timestamp',
             'elog.filter_label_level',
             'elog.filter_label_request');

echo "\n[ ini ]\n";
foreach ($ini as $val) {
    echo "$val --> ", ini_get($val), "\n";
}

echo "\n[ default ]\n";
test($log);
?>
--EXPECTF--
[ ini ]
elog.filter_label_scalar --> msg
elog.filter_label_file --> file
elog.filter_label_line --> line
elog.filter_label_timestamp --> time
elog.filter_label_level --> level
elog.filter_label_request --> req

[ default ]
{"msg":"dummy","file":"%s/060.php","line":21,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","req":{"dummy":"DUMMY"}}
["dummy"]
file: %s/060.php
line: 24
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
req: {
  "dummy": "DUMMY"
}
