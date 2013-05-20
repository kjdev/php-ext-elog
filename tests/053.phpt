--TEST--
elog_filter_add_level: array or object
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';

if (!extension_loaded('elog')) {
    dl('elog.' . PHP_SHLIB_SUFFIX);
}

$log = dirname(__FILE__) . "/tmp_053.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_add_level ]\n";
var_dump(elog_append_filter('elog_filter_add_level'));

echo "[ append: elog_filter_to_string ]\n";
var_dump(elog_append_filter('elog_filter_to_string'));

echo "[ append: elog_filter_add_eol ]\n";
var_dump(elog_append_filter('elog_filter_add_eol'));

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
[ append: elog_filter_to_string ]
bool(true)
[ append: elog_filter_add_eol ]
bool(true)

[ Array ]
=== normal ===
[
  "dummy"
]
=== emerg ===
{
  0: "dummy"
  "elog_level": "EMERGE"
}
=== alert ===
{
  0: "dummy"
  "elog_level": "ALERT"
}
=== crit ===
{
  0: "dummy"
  "elog_level": "CRIT"
}
=== err ===
{
  0: "dummy"
  "elog_level": "ERR"
}
=== warning ===
{
  0: "dummy"
  "elog_level": "WARNING"
}
=== notice ===
{
  0: "dummy"
  "elog_level": "NOTICE"
}
=== info ===
{
  0: "dummy"
  "elog_level": "INFO"
}
=== debug ===
{
  0: "dummy"
  "elog_level": "DEBUG"
}

[ Array 2 ]
elog.level: alert
=== normal ===
{
  "dummy": "DUMMY"
}
=== emerg ===
{
  "dummy": "DUMMY"
  "elog_level": "EMERGE"
}
=== alert ===
{
  "dummy": "DUMMY"
  "elog_level": "ALERT"
}
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
  "elog_level": "EMERGE"
}
=== alert ===
stdClass {
  "dummy": "DUMMY"
  "elog_level": "EMERGE"
}
=== crit ===
stdClass {
  "dummy": "DUMMY"
  "elog_level": "EMERGE"
}
=== err ===
stdClass {
  "dummy": "DUMMY"
  "elog_level": "EMERGE"
}
=== warning ===
stdClass {
  "dummy": "DUMMY"
  "elog_level": "EMERGE"
}
=== notice ===
stdClass {
  "dummy": "DUMMY"
  "elog_level": "EMERGE"
}
=== info ===
=== debug ===
