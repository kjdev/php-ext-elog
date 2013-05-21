--TEST--
phpinfo() displays elog info
--SKIPIF--
--FILE--
<?php
if (!extension_loaded('elog')) {
    dl('callmap.' . PHP_SHLIB_SUFFIX);
}

phpinfo();
?>
--EXPECTF--
%a
elog

elog support => enabled
Extension Version => %d.%d.%d
%a
