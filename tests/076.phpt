--TEST--
elog_filter: ini=elog.filter_label empty
--INI--
date.timezone=Asia/Tokyo
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_076.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

elog_append_filter('elog_filter_add_fileline');
elog_append_filter('elog_filter_add_timestamp');
elog_append_filter('elog_filter_add_level');
elog_append_filter('elog_filter_add_request');

$_REQUEST = array('dummy' => 'DUMMY');

function test($out) {
    ini_set('elog.to', 'json');
    elog('dummy');
    file_dump($out);
    echo "\n";
    ini_set('elog.to', 'string');
    elog_err(array('dummy'));
    file_dump($out);
}


$ini = array('elog.filter_label_message',
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

echo "\n[ elog.filter_label_message: '' ]\n";
ini_set('elog.filter_label_message', '');
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
elog.filter_label_message --> message
elog.filter_label_file --> file
elog.filter_label_line --> line
elog.filter_label_timestamp --> time
elog.filter_label_level --> level
elog.filter_label_request --> request

[ default ]
{"message":"dummy","file":"%s/076.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/076.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_message: '' ]
{"message":"dummy","file":"%s/076.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/076.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_file: '' ]
{"message":"dummy","file":"%s/076.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/076.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_line: '' ]
{"message":"dummy","file":"%s/076.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/076.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_timestamp: '' ]
{"message":"dummy","file":"%s/076.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/076.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_level: '' ]
{"message":"dummy","file":"%s/076.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/076.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_request: '' ]
{"message":"dummy","file":"%s/076.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/076.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}
