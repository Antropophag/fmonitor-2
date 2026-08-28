<?php
declare(strict_types=1);

$port = isset($argv[1]) ? (int) $argv[1] : 0;
$target = $argv[2] ?? '/pilot/assets/shlz.css';
$socket = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $error, 5);
if ($socket === false) {
    exit(70);
}
fwrite($socket, "GET {$target} HTTP/1.1\r\nHost: adversarial.example\r\nConnection: close\r\n\r\n");
stream_set_timeout($socket, 5);
while (!feof($socket)) {
    $bytes = fread($socket, 65536);
    if ($bytes === false) {
        fclose($socket);
        exit(70);
    }
    echo $bytes;
}
fclose($socket);
