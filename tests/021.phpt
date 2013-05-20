--TEST--
elog level: system INI=info
--INI--
elog.level="info"
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_013.log";
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
ini_set('elog.level', 'emerg');
test($log);

echo "[ Test alert ]\n";
ini_set('elog.level', 'alert');
test($log);

echo "[ Test crit ]\n";
ini_set('elog.level', 'crit');
test($log);

echo "[ Test err ]\n";
ini_set('elog.level', 'err');
test($log);

echo "[ Test warning ]\n";
ini_set('elog.level', 'warning');
test($log);

echo "[ Test notice ]\n";
ini_set('elog.level', 'notice');
test($log);

echo "[ Test info ]\n";
ini_set('elog.level', 'info');
test($log);

echo "[ Test debug ]\n";
ini_set('elog.level', 'debug');
test($log);

echo "[ Test none ]\n";
ini_set('elog.level', 'none');
test($log);

echo "[ Test all ]\n";
ini_set('elog.level', 'all');
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
elog.level: info
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
info-dummy
[ Test emerg ]
=== output ===
elog.level: emerg
dummy
emerg-dummy
[ Test alert ]
=== output ===
elog.level: alert
dummy
emerg-dummy
alert-dummy
[ Test crit ]
=== output ===
elog.level: crit
dummy
emerg-dummy
alert-dummy
crit-dummy
[ Test err ]
=== output ===
elog.level: err
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
[ Test warning ]
=== output ===
elog.level: warning
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
[ Test notice ]
=== output ===
elog.level: notice
dummy
emerg-dummy
alert-dummy
crit-dummy
err-dummy
warning-dummy
notice-dummy
[ Test info ]
=== output ===
elog.level: info
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
elog.level: debug
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
elog.level: none
dummy
[ Test all ]
=== output ===
elog.level: all
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
