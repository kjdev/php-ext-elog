--TEST--
elog_filter_to_http_query
--INI--
--SKIPIF--
--FILE--
<?php
require 'test.inc';


$log = dirname(__FILE__) . "/tmp_045.log";
ini_set('elog.default_type', 3);
ini_set('elog.default_destination', $log);

echo "[ append: elog_filter_to_http_query ]\n";
var_dump(elog_append_filter('elog_filter_to_http_query'));

function test($val, $out) {
    elog($val);

    echo "=== elog.filter_http_separator ===\n";
    echo ini_get('elog.filter_http_separator'), "\n";

    echo "=== elog.filter_http_encode ===\n";
    echo ini_get('elog.filter_http_encode'), "\n";

    echo "=== output ===\n";
    file_dump($out);
    echo "\n";
}

$val = array('key' => 'dummy', 'msg' => 'test message');
var_dump($val);

echo "\n[ default ]\n";
test($val, $log);

echo "\n[ elog.filter_http_separator: &nbsp; ]\n";
ini_set('elog.filter_http_separator', '&nbsp;');
test($val, $log);

echo "\n[ elog.filter_http_encode: PHP_QUERY_RFC1738 ]\n";
ini_set('elog.filter_http_encode', PHP_QUERY_RFC1738);
test($val, $log);

echo "\n[ elog.filter_http_encode: PHP_QUERY_RFC3986 ]\n";
ini_set('elog.filter_http_encode', PHP_QUERY_RFC3986);
test($val, $log);
?>
--EXPECTF--
[ append: elog_filter_to_http_query ]
bool(true)
array(2) {
  ["key"]=>
  string(5) "dummy"
  ["msg"]=>
  string(12) "test message"
}

[ default ]
=== elog.filter_http_separator ===

=== elog.filter_http_encode ===
0
=== output ===
key=dummy&msg=test+message

[ elog.filter_http_separator: &nbsp; ]
=== elog.filter_http_separator ===
&nbsp;
=== elog.filter_http_encode ===
0
=== output ===
key=dummy&nbsp;msg=test+message

[ elog.filter_http_encode: PHP_QUERY_RFC1738 ]
=== elog.filter_http_separator ===
&nbsp;
=== elog.filter_http_encode ===
1
=== output ===
key=dummy&nbsp;msg=test+message

[ elog.filter_http_encode: PHP_QUERY_RFC3986 ]
=== elog.filter_http_separator ===
&nbsp;
=== elog.filter_http_encode ===
2
=== output ===
key=dummy&nbsp;msg=test%20message
