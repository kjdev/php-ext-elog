--TEST--
elog level: numeric
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_067.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

function test($out) {
    elog("dummy\n");
    elog_emerg("emerg-dummy\n");
    elog_alert("alert-dummy\n");
    elog_crit("crit-dummy\n");
    elog_err("err-dummy\n");
    elog_warning("warning-dummy\n");
    elog_notice("notice-dummy\n");
    elog_info("info-dummy\n");
    elog_debug("debug-dummy\n");

    echo "=== output ===\n";
    echo "elog.level: ", ini_get('elog.level'), "\n";
    file_dump($out);
}

echo "[ Test default ]\n";
test($log);

echo "[ Test emerg ]\n";
ini_set('elog.level', 0);
test($log);

echo "[ Test alert ]\n";
ini_set('elog.level', 1);
test($log);

echo "[ Test crit ]\n";
ini_set('elog.level', 2);
test($log);

echo "[ Test err ]\n";
ini_set('elog.level', 3);
test($log);

echo "[ Test warning ]\n";
ini_set('elog.level', 4);
test($log);

echo "[ Test notice ]\n";
ini_set('elog.level', 5);
test($log);

echo "[ Test info ]\n";
ini_set('elog.level', 6);
test($log);

echo "[ Test debug ]\n";
ini_set('elog.level', 7);
test($log);

echo "[ Test none ]\n";
ini_set('elog.level', -1);
test($log);

echo "[ Test all ]\n";
ini_set('elog.level', 256);
test($log);

echo "[ Test hoge ]\n";
ini_set('elog.level', 'hoge');
test($log);

echo "[ Test (empty) ]\n";
ini_set('elog.level', '');
test($log);
?>
--EXPECTF--
[ Test default ]
=== output ===
elog.level: 
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
info-dummy
debug-dummy
[ Test emerg ]
=== output ===
elog.level: 0
dummy
emerg-dummy
[ Test alert ]
=== output ===
elog.level: 1
dummy
emerg-dummy
alert-dummy
[ Test crit ]
=== output ===
elog.level: 2
dummy
emerg-dummy
alert-dummy
crit-dummy
[ Test err ]
=== output ===
elog.level: 3
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
[ Test warning ]
=== output ===
elog.level: 4
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
[ Test notice ]
=== output ===
elog.level: 5
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
[ Test info ]
=== output ===
elog.level: 6
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
info-dummy
[ Test debug ]
=== output ===
elog.level: 7
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
info-dummy
debug-dummy
[ Test none ]
=== output ===
elog.level: -1
dummy
[ Test all ]
=== output ===
elog.level: 256
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
info-dummy
debug-dummy
[ Test hoge ]
=== output ===
elog.level: hoge
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
info-dummy
debug-dummy
[ Test (empty) ]
=== output ===
elog.level: 
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
info-dummy
debug-dummy
