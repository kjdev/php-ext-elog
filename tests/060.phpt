--TEST--
elog_filter: system=INI elog.filter_label
--INI--
date.timezone=Asia/Tokyo
elog.filter_label_message="MESSAGE"
elog.filter_label_file="FILE"
elog.filter_label_line="LINE"
elog.filter_label_timestamp="TIME"
elog.filter_label_level="LEVEL"
elog.filter_label_request="REQ"
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_060.log";
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
?>
--EXPECTF--
[ ini ]
elog.filter_label_message --> MESSAGE
elog.filter_label_file --> FILE
elog.filter_label_line --> LINE
elog.filter_label_timestamp --> TIME
elog.filter_label_level --> LEVEL
elog.filter_label_request --> REQ

[ default ]
{"MESSAGE":"dummy","FILE":"%s/060.php","LINE":18,"TIME":"%d-%s-%d %d:%d:%d Asia/Tokyo","REQ":{"dummy":"DUMMY"}}
[
  "dummy"
]
FILE: %s/060.php
LINE: 22
TIME: %d-%s-%d %d:%d:%d Asia/Tokyo
LEVEL: ERR
REQ: {
  "dummy": "DUMMY"
}
