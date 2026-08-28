<?php

declare(strict_types=1);

function readPacket($stream): ?array
{
    $header = fread($stream, 4);
    if ($header === '' || strlen($header) !== 4) {
        return null;
    }
    $length = ord($header[0]) | (ord($header[1]) << 8) | (ord($header[2]) << 16);
    $payload = '';
    while (strlen($payload) < $length) {
        $chunk = fread($stream, $length - strlen($payload));
        if ($chunk === '' || $chunk === false) {
            return null;
        }
        $payload .= $chunk;
    }
    return ['sequence' => ord($header[3]), 'payload' => $payload];
}

function writePacket($stream, int $sequence, string $payload): void
{
    $length = strlen($payload);
    fwrite($stream, chr($length & 0xff) . chr(($length >> 8) & 0xff) . chr(($length >> 16) & 0xff) . chr($sequence) . $payload);
}

$listen = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if ($listen === false) {
    fwrite(STDERR, "listen failed\n");
    exit(1);
}
$address = stream_socket_get_name($listen, false);
fwrite(STDOUT, substr((string) $address, strrpos((string) $address, ':') + 1) . "\n");
fflush(STDOUT);

$client = stream_socket_accept($listen, 10);
$backend = stream_socket_client(
    'tcp://' . getenv('PMR_BACKEND_HOST') . ':' . getenv('PMR_BACKEND_PORT'),
    $errno,
    $error,
    10,
);
if ($client === false || $backend === false) {
    fwrite(STDERR, "accept/backend failed\n");
    exit(2);
}

$handshake = readPacket($backend);
writePacket($client, $handshake['sequence'], $handshake['payload']);
$response = readPacket($client);
writePacket($backend, $response['sequence'], $response['payload']);

do {
    $auth = readPacket($backend);
    writePacket($client, $auth['sequence'], $auth['payload']);
    if (ord($auth['payload'][0]) === 0xfe && strlen($auth['payload']) > 1) {
        $authResponse = readPacket($client);
        writePacket($backend, $authResponse['sequence'], $authResponse['payload']);
    }
} while (ord($auth['payload'][0]) !== 0x00 && ord($auth['payload'][0]) !== 0xff);

$commands = [];
$command = readPacket($client);
if ($command !== null) {
    $commands[] = substr($command['payload'], 1);
    $message = (string) getenv('PMR_INJECTED_ERROR');
    writePacket($client, ($command['sequence'] + 1) & 0xff, "\xff" . pack('v', 1115) . '#42000' . $message);
}

stream_set_timeout($client, 1);
while (($later = readPacket($client)) !== null) {
    $commands[] = substr($later['payload'], 1);
}
file_put_contents((string) getenv('PMR_TRANSCRIPT_PATH'), json_encode($commands, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
fclose($backend);
fclose($client);
fclose($listen);
