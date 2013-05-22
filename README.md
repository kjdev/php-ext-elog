
# elog function Extension for PHP

[![Build Status](https://travis-ci.org/kjdev/php-ext-elog.png?branch=master)](https://travis-ci.org/kjdev/php-ext-elog)

This extension allows elog function.

elog is a function which is an extension of the error\_log.

need at least PHP 5.4 or newer.


# Dependencies

json processing is not using the jansson library.

* [jansson](http://www.digip.org/jansson/)


# Build

    % phpize
    % ./configure
    % make
    % make test
    $ make install

NOTICE: Posix extension is required to do all the tests.


# Configration

elog.ini:

    extension=elog.so

## List of elog.ini directives

 Name                                | Default         | Changeable
 ----                                | -------         | ----------
 elog.default\_type                  | "0"             | PHP\_INI\_ALL
 elog.default\_destination           | NULL            | PHP\_INI\_ALL
 elog.default\_options               | NULL            | PHP\_INI\_ALL
 elog.command\_output                | NULL            | PHP\_INI\_ALL
 elog.level                          | NULL            | PHP\_INI\_ALL
 elog.filter\_execute                | NULL            | PHP\_INI\_ALL
 elog.filter\_array\_assoc           | "Off"           | PHP\_INI\_ALL
 elog.filter\_json\_unicode\_escape  | "On"            | PHP\_INI\_ALL
 elog.filter\_json\_assoc            | "Off"           | PHP\_INI\_ALL
 elog.filter\_http\_separator        | NULL            | PHP\_INI\_ALL
 elog.filter\_http\_encode           | "0"             | PHP\_INI\_ALL
 elog.filter\_timestamp\_format      | NULL            | PHP\_INI\_ALL
 elog.filter\_label\_scalar          | "message"       | PHP\_INI\_ALL
 elog.filter\_label\_file            | "elog\_file"    | PHP\_INI\_ALL
 elog.filter\_label\_line            | "elog\_line"    | PHP\_INI\_ALL
 elog.filter\_label\_timestamp       | "elog\_time"    | PHP\_INI\_ALL
 elog.filter\_label\_level           | "elog\_level"   | PHP\_INI\_ALL
 elog.filter\_label\_request         | "elog\_request" | PHP\_INI\_ALL
 elog.override\_error\_log           | "Off"           | PHP\_INI\_SYSTEM
 elog.override\_error\_handler       | "Off"           | PHP\_INI\_SYSTEM
 elog.called\_origin\_error\_handler | "On"            | PHP\_INI\_SYSTEM
 elog.throw\_exception\_hook         | "Off"           | PHP\_INI\_SYSTEM

## elog.default\_type _integer_

default type of elog functions.

## elog.default\_destination _string_

default destination of elog functions.

## elog.default\_options _string_

default options of elog functions.

## elog.command\_output _string_

Set the output file path of command.
Specify the type 10 in the elog.

## elog.level _string_

log level of elog functions.

The default value is all.

level(number) or type (string) can be set.

 level | type
 ----- | ----
 -1    | none
 0     | emerg
 1     | alert
 2     | crit
 3     | err
 4     | warning
 5     | notice
 6     | info
 7     | debug
 256   | all

## elog.filter\_execute _string_

Set the filter to be performed, just prior to the execution of the function
elog.

function name or filter name (which was registered in elog\_register\_filter())
can be set.

those registered with the filter name will be used if filter name and function
name is the same.

execute after the filter that is registered in the elog\_prepend\_filter() or
elog\_append\_filter().

does not run when registered the same function name or same filter name in the
elog\_filter\_append() or elog\_filter\_prepend().

## elog.filter\_array\_assoc _boolean_

Set whether to get associative array as a scalar value in
elog\_filter\_to\_array().

The keys of the array will be elog.filter\_label\_scalar.

The default value is Off.

## elog.filter\_json\_unicode\_escape _boolean_

Set whether the Unicode Escape processed by elog\_filter\_to\_json().

The default value is On.

## elog.filter\_json\_assoc _boolean_

Set whether to get associative array as a array in elog\_filter\_to\_json().

The default value is Off.

## elog.filter\_http\_separator _string_

Set the separator to be used in elog\_filter\_to\_http().

## elog.filter\_http\_encode _integer_

Set the encode type to be used in elog\_filter\_to\_http().

The default value is PHP\_QUERY\_RFC1738.

* PHP\_QUERY\_RFC1738
* PHP\_QUERY\_RFC3986

## elog.filter\_timestamp\_format _string_

Set the timestamp format to be used in elog\_filter\_add\_timestamp().

## elog.filter\_label\_scalar _string_

Set the field name of when processed the scalar value in
elog\_filter\_to\_http_query() or elog\_filter\_to\_json().

The default value is "message".

## elog.filter\_label\_file _string_

Set the field name of the file name in the elog\_filter\_add\_fileline.

The default value is "elog\_file".

## elog.filter\_label\_line _string_

Set the field name of the line in the elog\_filter\_add\_fileline.

The default value is "elog\_line".

## elog.filter\_label\_timestamp _string_

Set the field name in the elog\_filter\_add\_timestamp.

The default value is "elog\_time".

## elog.filter\_label\_level _string_

Set the field name in the elog\_filter\_add\_level.

The default value is "elog\_level".

## elog.filter\_label\_request _string_

Set the field name in the elog\_filter\_add\_request.

The default value is "elog\_request".

## elog.override\_error\_log _boolean_

If On, error\_log() override to elog().

The default value is "Off".

## elog.override\_error\_handler _boolean_

If On, changed error handler to elog.

processing of elog will be the default mode of specified (elog.default\_type).

The default value is "Off".

## elog.called\_origin\_error\_handler _boolean_

elog.override\_error\_handler When the On, set the sent to the standard error
handler.

The default value is "On".

## elog.throw\_exception\_hook _boolean_

If On, send to elog messages that are throw new Exception.

processing of elog will be the default mode of specified (elog.default\_type).

The default value is "Off".


# Function

* elog — Send an error message to the defined error handling routines
* elog\_emerg — elog: system is unusable
* elog\_alert — elog: action must be taken immediately
* elog\_crit — elog: critical conditions
* elog\_err — elog: error conditions
* elog\_warning — elog: warning conditions
* elog\_notice — elog: normal but significant condition
* elog\_info — elog: informational
* elog\_debug — elog: debug-level messages

filter function:

* elog\_register\_filter — Register a user defined elog filter
* elog\_append\_filter — Attach a filter to a elog
* elog\_prepend\_filter — Attach a filter to a elog
* elog\_remove\_filter — Remove a filter from a elog
* elog\_get\_filter — Retrieve list of elog filters

builtin filter function:

* elog\_filter\_to\_string — converted to a string
* elog\_filter\_to\_json — converted to a json string
* elog\_filter\_to\_http\_query — converted to a URL-encoded query string
* elog\_filter\_to\_array — converted to a array
* elog\_filter\_add\_eol — add a new line
* elog\_filter\_add\_fileline — add the number of rows and executable file name
* elog\_filter\_add\_timestamp — add the execution time
* elog\_filter\_add\_request — add a variable REQUEST
* elog\_filter\_add\_level — add a log level


## elog — Send an error message to the defined error handling routines

### Description

bool **elog** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

Sends an error message.

### Parameters

* _message_

  The error message.

* _type_

  Says where the error should go. The possible message types are as follows:

  type | description
  ---- | -----------
  10   | message is sent by command in the destination parameter.
  11   | message is sent by socket in the destination parameter.

  0-4 are the same as error\_log().

* _destination_

  The destination.
  Its meaning depends on the type parameter as described above.

* _options_

  The options.
  Its meaning depends on the type parameter as described above.


### Return Values

Returns TRUE on success or FALSE on failure.

## elog\_emerg — elog: system is unusable

### Description

bool **elog\_emerg** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level emergency.

## elog\_alert — elog: action must be taken immediately

### Description

bool **elog\_alert** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level alert.

parameters and return values ​​the same and elog.

## elog\_crit — elog: critical conditions

### Description

bool **elog\_crit** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level critical.

parameters and return values ​​the same and elog.

## elog\_err — elog: error conditions

### Description

bool **elog\_err** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level error.

parameters and return values ​​the same and elog.

## elog\_warning — elog: warning conditions

### Description

bool **elog\_warning** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level warning.

parameters and return values ​​the same and elog.

## elog\_notice — elog: normal but significant condition

### Description

bool **elog\_notice** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level notice.

parameters and return values ​​the same and elog.

## elog\_info — elog: informational

### Description

bool **elog\_info** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level information.

parameters and return values ​​the same and elog.

## elog\_debug — elog: debug-level messages

### Description

bool **elog\_debug** ( string _$message_ [, int _$type_ = 0 [, string _$destination_ [, string _$options_ ]]] )

elog function of log level debug.

parameters and return values ​​the same and elog.


## elog\_register\_filter — Register a user defined elog filter

### Description

bool **elog\_register\_filter** ( string _$name_ , callback _$callback_ [, int $_enabled_ ] )

register the filters available in the elog().

### Parameters

* _name_

  The filter name to be registered.

* _callback_

  The shutdown callback to register.

* _enabled_

  also grant to the elog at the same time as the registration of the filter.

  * EL\_FILTER\_APPEND
  * EL\_FILTER\_PREPEND

### Return Values

Returns TRUE on success or FALSE on failure.

return FALSE if the rname is already defined.


## elog\_append\_filter — Attach a filter to a elog

### Description

bool **elog\_append\_filter** ( string _$name_ )

Adds name to the list of filters attached to elog.

### Parameters

* _name_

  The filter name or function name.

### Return Values

Returns TRUE on success or FALSE on failure.


## elog\_prepend\_filter — Attach a filter to a elog

### Description

bool **elog\_append\_filter** ( string _$name_ )

Adds name to the list of filters attached to elog.

### Parameters

* _name_

  The filter name or function name.

### Return Values

Returns TRUE on success or FALSE on failure.


## elog\_remove\_filter — Remove a filter from a elog

### Description

bool **elog\_remove\_filter** ( string _$name_ )

Removes a elog filter previously added to elog\_prepend\_filter() or
elog\_append\_filter_append().

### Parameters

* _name_

  The filter name or function name.

### Return Values

Returns TRUE on success or FALSE on failure.


## elog\_get\_filter — Retrieve list of elog filters

### Description

array **elog\_get\_filter** ( string _$typename_ )

Retrieve the list of registered filters on the running system.

### Parameters

* _typename_

  The filter type name.

  name      | description
  ----      | -----------
  builtin   | filter function of the built-in
  registers | registered in elog\_register\_filter()
  execute   | registered in elog.filter\_execute
  enabled   | valid filter

### Return Values

Returns an indexed array containing the name of filters available.


## elog\_filter\_to\_string — converted to a string

### Description

string **elog\_filter\_to\_string** ( mixed _$value_ )

Returns a string representation of value.

### Parameters

* _value_

  The value being converted.

### Return Values

Returns a string on success or FALSE on failure.


## elog\_filter\_to\_json — converted to a json string

### Description

string **elog\_filter\_to\_json** ( mixed _$value_ )

Returns a string containing the JSON representation of value.

### Parameters

* _value_

  The value being converted.

### Return Values

Returns a JSON encoded string on success or FALSE on failure.


## elog\_filter\_to\_http\_query — converted to a URL-encoded query string

### Description

string **elog\_filter\_to\_http\_query** ( mixed _$value_ )

Generates a URL-encoded query string from value.

### Parameters

* _value_

  The value being converted.

### Return Values

Returns a URL-encoded string on success or FALSE on failure.


## elog\_filter\_to\_array — converted to a array

### Description

array **elog\_filter\_to\_array** ( mixed _$value_ )

Returns a array representation of value.

### Parameters

* _value_

  The value being converted.

### Return Values

Returns a array on success or FALSE on failure.


## elog\_filter\_add\_eol — add a new line

### Description

mixed **elog\_filter\_add\_eol** ( mixed _$value_ )

Add a new line to the end.

Does not do anything if the object or array.

Add a new line after converted to a string in the case of non-string.

### Parameters

* _value_

  The value.

### Return Values

Returns a value on success or FALSE on failure.

## elog\_filter\_add\_fileline — add the number of rows and executable file name

### Description

mixed **elog\_filter\_add\_fileline** ( mixed _$value_ )

Add the number of rows and executable file name.

If the scalar value of no-string, Add to file name and the number of rows after
converted to a string.

### Parameters

* _value_

  The value.

### Return Values

Returns a value on success or FALSE on failure.


## elog\_filter\_add\_timestamp — add the execution time

### Description

mixed **elog\_filter\_add\_timestamp** ( mixed _$value_ )

Add the execution time.

### Parameters

* _value_

  The value.

### Return Values

Returns a value on success or FALSE on failure.

If the scalar value of no-string, Add to timestamp after converted to a string.

## elog\_filter\_add\_request — add a variable REQUEST

### Description

mixed **elog\_filter\_add\_request** ( mixed _$value_ )

Add a variable REQUEST.

### Parameters

* _value_

  The value.

### Return Values

Returns a value on success or FALSE on failure.

If the scalar value of no-string, Add to variable REQUEST after converted to a
string.

## elog\_filter\_add\_level — add a log level

### Description

mixed **elog\_filter\_add\_level** ( mixed _$value_ )

Add a log level.

### Parameters

* _value_

  The value.

### Return Values

Returns a value on success or FALSE on failure.

If the scalar value of no-string, Add to log level after converted to a string.


# Examples

### default

    elog('dummy');

### file

    elog('dummy', 3, '/path/to/file');

### command

    elog('dummy', 10, '/path/to/command');
    /*
    same as:
    system("echo 'dummy' | '/path/to/command');
    // or
    $process = popen('/path/to/command', 'w');
    fwrite($process, 'dummy');
    pclose($process);
    */

#### command option

    elog('dummy', 10, '/path/to/command', 'command option');
    /*
    same as:
    system("echo 'dummy' | '/path/to/command command option');
    // or
    $process = popen('/path/to/command command option', 'w');
    fwrite($process, 'dummy');
    pclose($process);
    */

#### command output

    ini_set('elog.command_output', '/path/to/output');
    elog('dummy', 10, '/path/to/command');

The output of the /path/to/output is recorded in the /path/to/command.

### socket (transport target)

#### TCP/IP

    elog('dummy', 11, 'tcp://127.0.0.1:12342');
    elog('dummy', 11, 'tcp://localhost:12342');

#### UDP

    elog('dummy', 11, 'udp://127.0.0.1:12342');

#### HTTP

    elog('dummy=dummy', 11, 'http://127.0.0.1');

POST method, Media type is application/x-www-form-urlencoded.

#### HTTP headers

    elog('dummy=dummy', 11, 'http://127.0.0.1', "Content-Type:xxx\nUser-Agent:xxx");

### multi type

    elog('dummy', array(array(3, '/path/to/file1'),
                        array(3, '/path/to/file2'),
                        array(10, '/path/to/command'),
                        array(11, 'tcp://127.0.0.1:12342')));

* The output to /path/to/file1, /path/to/file2.
* Send to '/path/to/command'.
* Send to 'tcp://127.0.0.1:12342'.

### default set

    ini_set('elog.default_type', 3);
    ini_set('elog.default_destination', '/path/to/file');

    elog('dummy');
    // The output to /path/to/file.

    ini_set('elog.default_type', 2);
    ini_set('elog.default_destination', 'tcp://127.0.0.1:12342');

    elog('dummy');
    // Send to 'tcp://127.0.0.1:12342'.

### level

    ini_set('elog.level', 'emerg'); // or alert, crit, err, warning, notice,
                                    //     info, debug, none, all

    elog_emerg('dummy-0');
    elog_alert('dummy-1');
    elog_crit('dummy-2');
    elog_err('dummy-3');
    elog_warning('dummy-4');
    elog_notice('dummy-5');
    elog_info('dummy-6');
    elog_debug('dummy-7');

    /*
    // output: emerg
    dummy-0
    // output: alert
    dummy-0dummy-1
    // output: crit
    dummy-0dummy-1dummy-2
    // output: err
    dummy-0dummy-1dummy-2dummy-3
    // output: warning
    dummy-0dummy-1dummy-2dummy-3dummy-4
    // output: notice
    dummy-0dummy-1dummy-2dummy-3dummy-4dummy-5
    // output: info
    dummy-0dummy-1dummy-2dummy-3dummy-4dummy-5dummy-6
    // output: debug
    dummy-0dummy-1dummy-2dummy-3dummy-4dummy-5dummy-6dummy-7
    // output: none
    // output: all
    dummy-0dummy-1dummy-2dummy-3dummy-4dummy-5dummy-6dummy-7
    */

#### filter: register

    function f1($val) {
        return $val . '-a';
    }

    class F {
        public function f2($val) {
            return $val . '-b';
        }
        static public function f3($val) {
            return $val . '-c';
        }
    }

    elog_register_filter('a', 'f1');
    elog_register_filter('b', array(new F, 'f2'));
    elog_register_filter('c', 'F::f3');
    elog_register_filter('d', function ($val) { return $val . '-d'; });

    elog_append_filter('a'); // or elog_prepend_filter
    elog_append_filter('b'); // or elog_prepend_filter
    elog_append_filter('c'); // or elog_prepend_filter
    elog_append_filter('d'); // or elog_prepend_filter

    elog('dummy');
    /*
    // output: append
    dummy-a-b-c-d

    // output: prepend
    dummy-d-c-b-a
    */

### filter: elog\_filter\_to\_string

    elog_append_filter('elog_filter_to_string');
    
    elog('dummy');
    /*
    // output:
    dummy
    */

    elog(array('dummy'));
    /* output:
    [
        "dummy"
    ]
    */

    elog(array(a => 'dummy', b => 'DUMMY'));
    /* output:
    {
        "a": "dummy"
        "b": "DUMMY"
    }
    */

### filter: elog\_filter\_to\_json

    elog_append_filter('elog_filter_to_json');
    
    elog('dummy');
    /*
    // output:
    {"message":"dummy"}
    */

    elog(array('dummy'));
    /*
    // output:
    ["dummy"]
    */

    elog(array('a' => 'dummy', 'b' => 'DUMMY'));
    /*
    // output:
    {"a":"dummy","b":"DUMMY"}
    */

### filter: elog\_filter\_to\_http\_query

    elog_append_filter('elog_filter_to_http_query');
    
    elog('dummy');
    /*
    // output:
    message=dummy
    */

    elog(array('dummy'));
    /*
    // output:
    0=dummy
    */

    elog(array('a' => 'dummy', 'b' => 'DUMMY'));
    /*
    // output:
    a=dummy&b=DUMMY
    */

### filter: elog\_filter\_to\_array

    elog_append_filter(array('elog_filter_to_array','elog_filter_to_string'));

    elog('dummy');
    /*
    // output:
    [
      "dummy"
    ]
    */

    ini_set('elog.filter_array_assoc', 'On');

    elog('dummy');
    /*
    // output:
    {
      "message": "dummy"
    }
    */

    elog(array('dummy'));
    /*
    // output:
    [
      "dummy"
    ]
    */

    elog(array('a' => 'dummy', 'b' => 'DUMMY'));
    /*
    // output:
    {
      "a": "dummy"
      "b": "DUMMY"
    }
    */

### filter: elog\_filter\_add\_fileline

    elog_append_filter(array('elog_filter_add_fileline','elog_filter_to_string'));
    
    elog('dummy');
    /*
    // output:
    dummy
    elog_file: /path/to/test.php
    elog_line: 4
    */

    ini_set('elog.filter_label_file', 'file');
    
    elog(array('dummy'));
    /*
    // output:
    {
      0: "dummy"
      "file": "/path/to/test.php"
      "elog_line": 14
    }
    */

    ini_set('elog.filter_label_line', 'line');
    
    elog(array('a' => 'dummy', 'b' => 'DUMMY'));
    /*
    // output:
    {
      "a": "dummy"
      "b": "DUMMY"
      "file": "/path/to/test.php"
      "line": 26
    }
    */

### filter: elog\_filter\_add\_timestamp

    elog_append_filter(array('elog_filter_add_timestamp','elog_filter_to_string'));
    
    elog('dummy');
    /*
    // output:
    dummy
    elog_time: 20-May-2013 13:50:13 Asia/Tokyo
    */

    ini_set('elog.filter_timestamp_format', 'Y-m-d H:i:s');

    elog(array('dummy'));
    /*
    // output:
    {
      0: "dummy"
      "elog_time": "2013-05-20 13:50:13"
    }
    */

    ini_set('elog.filter_timestamp_format', 'U');
    ini_set('elog.filter_label_timestamp', 'time');
        
    elog(array('a' => 'dummy', 'b' => 'DUMMY'));
    /*
    // output:
    {
      "a": "dummy"
      "b": "DUMMY"
      "time": "1369025413"
    }
    */

### filter: elog\_filter\_add\_level

    elog_append_filter(array('elog_filter_add_level','elog_filter_to_string'));
    
    elog_emerg('dummy');
    /*
    // output:
    dummy
    elog_level: EMERGE
    */

    elog_warning(array('dummy'));
    /*
    // output:
    {
      0: "dummy"
      "elog_level": "WARNING"
    }
    */

    ini_set('elog.filter_label_level', 'level');
    
    elog_err(array('a' => 'dummy', 'b' => 'DUMMY'));
    /*
    // output:
    {
      "a": "dummy"
      "b": "DUMMY"
      "level": "ERR"
    }
    */

#### filter and HTTP

    elog_append_filter('elog_filter_to_http_query');

    elog(array('a' => 'dummy', 'b' => 'DUMMY'), 11, 'http://127.0.0.1/recv.php');
    /*
    // http://127.0.0.1/recv.php: var_dump($_REQUEST)
    array (
      'a' => 'dummy',
      'b' => 'DUMMY,
    )
   */

#### filter and multi type

    elog('dummy', array(array(3, '/path/to/file1', 'elog_filter_add_fileline'),
                        array(3, '/path/to/file2', 'elog_filter_add_timestamp')));
    /*
    // output: /path/to/file1
    dummy
    elog_file: /path/to/test.php
    elog_line: 2

    // output: /path/to/file2
    dummy
    elog_time: 20-May-2013 14:02:14 Asia/Tokyo
    */

### override error\_log

    // elog.ini:
    // elog.default_type=3
    // elog.default_destination=/path/to/file
    // elog.override_error_log=On

    error_log('dummy');
    /*
    // output: /path/to/file
    dummy
    */

### override error handler

    // elog.ini:
    // elog.default_type=3
    // elog.default_destination=/path/to/file
    // elog.override_error_handler=On
 
    echo $dummy;
    /*
    // output: /path/to/file
    PHP Notice:  Undefined variable: dummy in /path/to/test.php on line 2
    */
