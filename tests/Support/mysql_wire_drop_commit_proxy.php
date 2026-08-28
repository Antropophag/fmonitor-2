<?php
declare(strict_types=1);

$listen = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if ($listen === false) { exit(1); }
$address = (string) stream_socket_get_name($listen, false);
fwrite(STDOUT, substr($address, strrpos($address, ':') + 1) . "\n");
fflush(STDOUT);

function relayConnection($client, bool $dropCommit): bool
{
    $backend = stream_socket_client('tcp://' . getenv('PMR_BACKEND_HOST') . ':' . getenv('PMR_BACKEND_PORT'), $errno, $error, 10);
    if ($backend === false) { return false; }
    stream_set_blocking($client, false);
    stream_set_blocking($backend, false);
    $clientBuffer = '';
    $deadline = microtime(true) + 20;
    while (microtime(true) < $deadline) {
        $read = [$client, $backend]; $write = $except = [];
        if (stream_select($read, $write, $except, 1) === false) { break; }
        foreach ($read as $stream) {
            $chunk = fread($stream, 65536);
            if ($chunk === '' || $chunk === false) {
                if (feof($stream)) { fclose($backend); return true; }
                continue;
            }
            if ($stream === $backend) {
                fwrite($client, $chunk);
                continue;
            }
            $clientBuffer .= $chunk;
            while (strlen($clientBuffer) >= 4) {
                $length = ord($clientBuffer[0]) | (ord($clientBuffer[1]) << 8) | (ord($clientBuffer[2]) << 16);
                if (strlen($clientBuffer) < $length + 4) { break; }
                $packet = substr($clientBuffer, 0, $length + 4);
                $clientBuffer = substr($clientBuffer, $length + 4);
                $payload = substr($packet, 4);
                if ($dropCommit && $payload !== '' && ord($payload[0]) === 0x03 && strtoupper(trim(substr($payload, 1))) === 'COMMIT') {
                    $forward = getenv('PMR_FORWARD_COMMIT') === '1';
                    if ($forward) {
                        fwrite($backend, $packet);
                        stream_set_blocking($backend, true);
                        stream_set_timeout($backend, 5);
                        fread($backend, 65536);
                    }
                    file_put_contents((string) getenv('PMR_TRANSCRIPT_PATH'), $forward ? "COMMIT_FORWARDED_RESPONSE_DROPPED\n" : "COMMIT_DROPPED\n", FILE_APPEND | LOCK_EX);
                    fclose($backend);
                    fclose($client);
                    return true;
                }
                fwrite($backend, $packet);
            }
        }
    }
    fclose($backend);
    fclose($client);
    return false;
}

$first = stream_socket_accept($listen, 10);
if ($first === false || !relayConnection($first, true)) { fclose($listen); exit(2); }
$mutateTable = (string) getenv('PMR_MUTATE_TABLE');
if ($mutateTable !== '') {
    if (preg_match('/^[A-Za-z0-9_]{1,96}$/D', $mutateTable) !== 1) { fclose($listen); exit(4); }
    $mutation = new mysqli(
        (string) getenv('PMR_BACKEND_HOST'),
        (string) getenv('PMR_MUTATE_USER'),
        (string) getenv('PMR_MUTATE_PASSWORD'),
        (string) getenv('PMR_MUTATE_DATABASE'),
        (int) getenv('PMR_BACKEND_PORT'),
    );
    $mutation->set_charset('utf8mb4');
    $mutation->query("UPDATE `{$mutateTable}` SET created_at='2000-01-01T00:00:00+00:00',updated_at='2000-01-01T00:00:00+00:00'");
    $mutation->close();
}
$second = stream_socket_accept($listen, 10);
if ($second === false || !relayConnection($second, false)) { fclose($listen); exit(3); }
fclose($listen);
