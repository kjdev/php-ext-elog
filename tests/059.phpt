--TEST--
elog_filter: ini=elog.filter_label
--INI--
date.timezone=Asia/Tokyo
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_059.log";
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

echo "\n[ elog.filter_label_message: MESSAGE ]\n";
ini_set('elog.filter_label_message', 'MESSAGE');
test($log);

echo "\n[ elog.filter_label_file: FILE ]\n";
ini_set('elog.filter_label_file', 'FILE');
test($log);

echo "\n[ elog.filter_label_line: LINE ]\n";
ini_set('elog.filter_label_line', 'LINE');
test($log);

echo "\n[ elog.filter_label_timestamp: TIME ]\n";
ini_set('elog.filter_label_timestamp', 'TIME');
test($log);

echo "\n[ elog.filter_label_level: LEVEL ]\n";
ini_set('elog.filter_label_level', 'LEVEL');
test($log);

echo "\n[ elog.filter_label_request: REQ ]\n";
ini_set('elog.filter_label_request', 'REQ');
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
{"message":"dummy","file":"%s/059.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/059.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_message: MESSAGE ]
{"MESSAGE":"dummy","file":"%s/059.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
file: %s/059.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_file: FILE ]
{"MESSAGE":"dummy","FILE":"%s/059.php","line":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
FILE: %s/059.php
line: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_line: LINE ]
{"MESSAGE":"dummy","FILE":"%s/059.php","LINE":18,"time":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
FILE: %s/059.php
LINE: 22
time: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_timestamp: TIME ]
{"MESSAGE":"dummy","FILE":"%s/059.php","LINE":18,"TIME":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
FILE: %s/059.php
LINE: 22
TIME: %d-%s-%d %d:%d:%d Asia/Tokyo
level: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_level: LEVEL ]
{"MESSAGE":"dummy","FILE":"%s/059.php","LINE":18,"TIME":"%d-%s-%d %d:%d:%d Asia/Tokyo","request":{"dummy":"DUMMY"}}
[
  "dummy"
]
FILE: %s/059.php
LINE: 22
TIME: %d-%s-%d %d:%d:%d Asia/Tokyo
LEVEL: ERR
request: {
  "dummy": "DUMMY"
}

[ elog.filter_label_request: REQ ]
{"MESSAGE":"dummy","FILE":"%s/059.php","LINE":18,"TIME":"%d-%s-%d %d:%d:%d Asia/Tokyo","REQ":{"dummy":"DUMMY"}}
[
  "dummy"
]
FILE: %s/059.php
LINE: 22
TIME: %d-%s-%d %d:%d:%d Asia/Tokyo
LEVEL: ERR
REQ: {
  "dummy": "DUMMY"
}
