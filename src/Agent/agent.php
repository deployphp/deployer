<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$address = '0.0.0.0';
$port = 10000;

if (($socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    die("ERROR: socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
}

if (@socket_bind($socket, $address, $port) === false) {
    die("ERROR: socket_bind() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n");
}

if (@socket_listen($socket, 5) === false) {
    die("ERROR: socket_listen() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n");
}

if (($conn = @socket_accept($socket)) === false) {
    die("ERROR: socket_accept() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n");
}

/* Send instructions. */
$msg = "\nWelcome to the PHP Test Server. \n" .
    "To quit, type 'quit'. To shut down the server type 'shutdown'.\n";
socket_write($conn, $msg, strlen($msg));

$startTime = time();
do {
    if (false === ($buf = @socket_read($conn, 2048, PHP_NORMAL_READ))) {
        echo "ERROR: socket_read() failed: reason: " . socket_strerror(socket_last_error($conn)) . "\n";
        break;
    }
    if (!$buf = trim($buf)) {
        continue;
    }
    if ($buf == 'quit') {
        break;
    }
    if ($buf == 'shutdown') {
        socket_close($conn);
        break;
    }
    $talkback = "PHP: You said '$buf'.\n";
    socket_write($conn, $talkback, strlen($talkback));
    echo "$buf\n";
} while (time() - $startTime < 5);

socket_close($conn);
socket_close($socket);
