--TEST--
phpinfo() displays elog info
--SKIPIF--
--FILE--
<?php

phpinfo();
?>
--EXPECTF--
%a
elog

elog support => enabled
Extension Version => %d.%d.%d
%a
