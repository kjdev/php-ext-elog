--TEST--
elog_filter_add_level: array or object
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_053.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_level ]\n";
var_dump(elog_append_filter('elog_filter_add_level'));

function test($var, $out) {
    elog($var);
    echo "=== normal ===\n";
    file_dump($out);

    elog_emerg($var);
    echo "=== emerg ===\n";
    file_dump($out);

    elog_alert($var);
    echo "=== alert ===\n";
    file_dump($out);

    elog_crit($var);
    echo "=== crit ===\n";
    file_dump($out);

    elog_err($var);
    echo "=== err ===\n";
    file_dump($out);

    elog_warning($var);
    echo "=== warning ===\n";
    file_dump($out);

    elog_notice($var);
    echo "=== notice ===\n";
    file_dump($out);

    elog_info($var);
    echo "=== info ===\n";
    file_dump($out);

    elog_debug($var);
    echo "=== debug ===\n";
    file_dump($out);
}

echo "\n[ Array ]\n";
$var = array('dummy');
test($var, $log);

echo "\n[ Array 2 ]\n";
ini_set('elog.level', 'alert');
echo "elog.level: ", ini_get('elog.level'), "\n";
$var = array('dummy' => 'DUMMY');
test($var, $log);

echo "\n[ Object ]\n";
ini_set('elog.level', 'notice');
echo "elog.level: ", ini_get('elog.level'), "\n";
$var = new stdClass;
$var->dummy = 'DUMMY';
test($var, $log);

?>
--EXPECTF--
[ append: elog_filter_add_level ]
bool(true)

[ Array ]
=== normal ===
[
  "dummy"
]
=== emerg ===
[
  "dummy"
]
level: EMERGE
=== alert ===
[
  "dummy"
]
level: ALERT
=== crit ===
[
  "dummy"
]
level: CRIT
=== err ===
[
  "dummy"
]
level: ERR
=== warning ===
[
  "dummy"
]
level: WARNING
=== notice ===
[
  "dummy"
]
level: NOTICE
=== info ===
[
  "dummy"
]
level: INFO
=== debug ===
[
  "dummy"
]
level: DEBUG

[ Array 2 ]
elog.level: alert
=== normal ===
{
  "dummy": "DUMMY"
}
=== emerg ===
{
  "dummy": "DUMMY"
}
level: EMERGE
=== alert ===
{
  "dummy": "DUMMY"
}
level: ALERT
=== crit ===
=== err ===
=== warning ===
=== notice ===
=== info ===
=== debug ===

[ Object ]
elog.level: notice
=== normal ===
stdClass {
  "dummy": "DUMMY"
}
=== emerg ===
stdClass {
  "dummy": "DUMMY"
}
level: EMERGE
=== alert ===
stdClass {
  "dummy": "DUMMY"
}
level: ALERT
=== crit ===
stdClass {
  "dummy": "DUMMY"
}
level: CRIT
=== err ===
stdClass {
  "dummy": "DUMMY"
}
level: ERR
=== warning ===
stdClass {
  "dummy": "DUMMY"
}
level: WARNING
=== notice ===
stdClass {
  "dummy": "DUMMY"
}
level: NOTICE
=== info ===
=== debug ===
