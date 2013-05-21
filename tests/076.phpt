--TEST--
elog_filter: ini=elog.filter_label empty
--INI--
date.timezone=Asia/Tokyo
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_076.log";
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

echo "\n[ elog.filter_label_scalar: '' ]\n";
ini_set('elog.filter_label_scalar', '');
test($log);

echo "\n[ elog.filter_label_file: '' ]\n";
ini_set('elog.filter_label_file', '');
test($log);

echo "\n[ elog.filter_label_line: '' ]\n";
ini_set('elog.filter_label_line', '');
test($log);

echo "\n[ elog.filter_label_timestamp: '' ]\n";
ini_set('elog.filter_label_timestamp', '');
test($log);

echo "\n[ elog.filter_label_level: '' ]\n";
ini_set('elog.filter_label_level', '');
test($log);

echo "\n[ elog.filter_label_request: '' ]\n";
ini_set('elog.filter_label_request', '');
test($log);
?>
--EXPECTF--
[ ini ]
elog.filter_label_scalar --> message
elog.filter_label_file --> elog_file
elog.filter_label_line --> elog_line
elog.filter_label_timestamp --> elog_time
elog.filter_label_level --> elog_level
elog.filter_label_request --> elog_request

[ default ]
{"message":"dummy","elog_file":"%s/076.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_request":{"dummy":"DUMMY"}}
["dummy"]
elog_file: %s/076.php
elog_line: 24
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo
elog_level: ERR
elog_request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_scalar: '' ]
{"message":"dummy","elog_file":"%s/076.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_request":{"dummy":"DUMMY"}}
["dummy"]
elog_file: %s/076.php
elog_line: 24
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo
elog_level: ERR
elog_request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_file: '' ]
{"message":"dummy","elog_file":"%s/076.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_request":{"dummy":"DUMMY"}}
["dummy"]
elog_file: %s/076.php
elog_line: 24
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo
elog_level: ERR
elog_request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_line: '' ]
{"message":"dummy","elog_file":"%s/076.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_request":{"dummy":"DUMMY"}}
["dummy"]
elog_file: %s/076.php
elog_line: 24
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo
elog_level: ERR
elog_request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_timestamp: '' ]
{"message":"dummy","elog_file":"%s/076.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_request":{"dummy":"DUMMY"}}
["dummy"]
elog_file: %s/076.php
elog_line: 24
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo
elog_level: ERR
elog_request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_level: '' ]
{"message":"dummy","elog_file":"%s/076.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_request":{"dummy":"DUMMY"}}
["dummy"]
elog_file: %s/076.php
elog_line: 24
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo
elog_level: ERR
elog_request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_request: '' ]
{"message":"dummy","elog_file":"%s/076.php","elog_line":21,"elog_time":"%d-%s-%d %d:%d:%d Asia/Tokyo","elog_request":{"dummy":"DUMMY"}}
["dummy"]
elog_file: %s/076.php
elog_line: 24
elog_time: %d-%s-%d %d:%d:%d Asia/Tokyo
elog_level: ERR
elog_request: {
  "dummy": "DUMMY"
}
