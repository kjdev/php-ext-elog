<?php
function tcp_server_skipif($connect) {
    if (!function_exists('pcntl_fork')) {
        die('skip pcntl_fork() not available');
    }
    if (!function_exists('posix_kill')) {
        die('skip posix_kill() not available');
    }
    /*
    if (!stream_socket_server($connect)) {
        die('skip stream_socket_server() failed');
    }
    */
    $host = parse_url($connect, PHP_URL_HOST);
    $port = parse_url($connect, PHP_URL_PORT);
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!is_resource($socket)) {
        die('skip socket_create() failed');
    }
    socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
    if (!socket_bind($socket, $host, $port)) {
        socket_close($socket);
        die('skip socket_bind() failed');
    }
    socket_close($socket);
}

function udp_server_skipif($connect) {
    if (!function_exists('pcntl_fork')) {
        die('skip pcntl_fork() not available');
    }
    if (!function_exists('posix_kill')) {
        die('skip posix_kill() not available');
    }
    /*
    if (!stream_socket_server($connect, $errno, $errstr, STREAM_SERVER_BIND)) {
        die('skip stream_socket_server() failed');
    }
    */
    $host = parse_url($connect, PHP_URL_HOST);
    $port = parse_url($connect, PHP_URL_PORT);
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if (!is_resource($socket)) {
        die('skip socket_create() failed');
    }
    socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
    if (!socket_bind($socket, $host, $port)) {
        socket_close($socket);
        die('skip socket_bind() failed');
    }
    socket_close($socket);
}

function http_server_skipif($connect) {
    if (!function_exists('pcntl_fork')) {
        die('skip pcntl_fork() not available');
    }
    if (!function_exists('posix_kill')) {
        die('skip posix_kill() not available');
    }
    /*
    if (!socket_create_listen(parse_url($connect, PHP_URL_PORT))) {
        die('skip socket_create_listen() failed');
    }
    */
    $host = parse_url($connect, PHP_URL_HOST);
    $port = parse_url($connect, PHP_URL_PORT);
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!is_resource($socket)) {
        die('skip socket_create() failed');
    }
    socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
    if (!socket_bind($socket, $host, $port)) {
        socket_close($socket);
        die('skip socket_bind() failed');
    }
    socket_close($socket);
}

function file_dump($file, &$buf = null) {
    if (is_file($file)) {
        @readfile($file);
        if ($buf !== null) {
            $buf = file_get_contents($file);
        }
        @unlink($file);
    }
}

function file_wait($file) {
    for ($i = 0; $i < 5; $i++) {
        usleep(500);
        if (is_file($file)) {
            $ret = filesize($file);
            if ($ret !== false && $ret > 0) {
                return;
            }
        }
    }
}

function server_finish($pid) {
    if ($pid) {
        posix_kill($pid, SIGHUP);
        pcntl_wait($status);
    }
    exit(1);
}

function tcp_server_test($connect, $output = null)
{
    if ($pid = pcntl_fork()) {
        usleep(500);
        return $pid;
    }

    $host = parse_url($connect, PHP_URL_HOST);
    $port = parse_url($connect, PHP_URL_PORT);

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!is_resource($socket)) {
        echo socket_strerror(socket_last_error()), "\n";
        exit(1);
    }

    if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
        echo socket_strerror(socket_last_error()), "\n";
        socket_close($socket);
        exit(1);
    }

    if (!socket_bind($socket, $host, $port)) {
        echo socket_strerror(socket_last_error()), "\n";
        socket_close($socket);
        exit(1);
    }

    if (!socket_listen($socket, 0)) {
        echo socket_strerror(socket_last_error()), "\n";
        socket_close($socket);
        exit(1);
    }

    $r = array($socket);
    $w = array($socket);
    $e = array($socket);
    $timeout = 3;

    while (1) {
        if (socket_select($r, $w, $e, $timeout) == 1) {
            $sock = socket_accept($socket);
        } else {
            $sock = false;
        }

        if ($sock === false) {
            echo socket_strerror(socket_last_error()), "\n";
            socket_close($socket);
            exit(1);
        }

        $buf = socket_read($sock, 8192, PHP_BINARY_READ);

        if ($output) {
            file_put_contents($output, $buf);
        }

        socket_close($sock);
    }

    socket_close($socket);
}

function udp_server_test($connect, $output = null)
{
    if ($pid = pcntl_fork()) {
        usleep(500);
        return $pid;
    }

    $host = parse_url($connect, PHP_URL_HOST);
    $port = parse_url($connect, PHP_URL_PORT);

    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if (!is_resource($socket)) {
        echo socket_strerror(socket_last_error()), "\n";
        exit(1);
    }

    if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
        echo socket_strerror(socket_last_error()), "\n";
        exit(1);
    }

    if (!socket_bind($socket, $host, $port)) {
        echo socket_strerror(socket_last_error()), "\n";
        exit(1);
    }

    while (1) {
        $from = '';
        $port = 0;

        socket_recvfrom($socket, $buf, 8192, 0, $from, $port);

        if ($output) {
            file_put_contents($output, $buf);
        }

        usleep(300);
    }

    socket_close($socket);
}

function http_server_test($connect, $output = null)
{
    if ($pid = pcntl_fork()) {
        usleep(500);
        return $pid;
    }

    $host = parse_url($connect, PHP_URL_HOST);
    $port = parse_url($connect, PHP_URL_PORT);

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!is_resource($socket)) {
        echo socket_strerror(socket_last_error()), "\n";
        exit(1);
    }

    if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
        echo socket_strerror(socket_last_error()), "\n";
        socket_close($socket);
        exit(1);
    }

    if (!socket_bind($socket, $host, $port)) {
        echo socket_strerror(socket_last_error()), "\n";
        socket_close($socket);
        exit(1);
    }

    if (!socket_listen($socket, 0)) {
        echo socket_strerror(socket_last_error()), "\n";
        socket_close($socket);
        exit(1);
    }

    $r = array($socket);
    $w = array($socket);
    $e = array($socket);
    $timeout = 3;

    while (1) {
        if (socket_select($r, $w, $e, $timeout) == 1) {
            $sock = socket_accept($socket);
        } else {
            $sock = false;
        }

        if ($sock === false) {
            echo socket_strerror(socket_last_error()), "\n";
            socket_close($socket);
            exit(1);
        }

        $server_method = "";
        $server_uri = "";
        $server_body = "";
        $server_headers = array();
        $request = "";

        while (1) {
            $buf = socket_read($sock, 8192, PHP_BINARY_READ);
            $request .= $buf;
            if ($buf === false) {
                echo socket_strerror(socket_last_error()), "\n";
                exit(1);
            } else if (empty($buf)) {
                break;
            }
            $pos = strpos($request, "\r\n\r\n");
            if ($pos !== false) {
                $header = substr($request, 0, $pos);
                if ($header) {
                    $lines = explode("\r\n", $header);
                    list($server_method, $server_uri) = explode(" ", array_shift($lines));
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) {
                            continue;
                        }
                        $headers = explode(":", $line);
                        $server_headers[trim(array_shift($headers))] = trim(join(":", $headers));
                    }
                }
                $server_body = substr($request, $pos + 4);

                if ($server_method === "POST" &&
                    !empty($server_headers['Content-Length'])) {
                    if (strlen($server_body) == $server_headers['Content-Length']) {
                        break;
                    }
                    continue;
                }
                break;
            }
        }

        if ($output) {
            $contents = "Method: " . $server_method . "\n"
                . "Uri: " . $server_uri . "\n"
                . "Body: " . $server_body . "\n"
                . "Headers: " . print_r($server_headers, true) . "\n";
            file_put_contents($output, $contents);
        }

        $header = "HTTP/1.x 200 OK\r\n";
        $header .= "Content-Type: text/html\r\n";
        $header .= "\r\n\r\n";
        socket_write($sock, $header, strlen($header));

        $response = "dummy";
        socket_write($sock, $response, strlen($response));
        socket_close($sock);

    }
    socket_close($socket);
}
