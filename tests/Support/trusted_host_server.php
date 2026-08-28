<?php
declare(strict_types=1);

$sentinel = getenv('FMONITOR_TEST_HANDLER_SENTINEL');
if (!is_string($sentinel) || $sentinel === '') exit(64);
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if ($server === false) exit(70);
echo substr((string) stream_socket_get_name($server, false), strrpos((string) stream_socket_get_name($server, false), ':') + 1), "\n";
flush();

for ($requestNumber = 0; $requestNumber < 4; ++$requestNumber) {
    $client = stream_socket_accept($server, 5);
    if ($client === false) exit(70);
    stream_set_timeout($client, 2);
    $raw = '';
    while (!str_contains($raw, "\r\n\r\n") && !feof($client)) $raw .= (string) fread($client, 4096);
    [$head] = explode("\r\n\r\n", $raw, 2);
    $hosts = [];
    foreach (array_slice(explode("\r\n", $head), 1) as $line) {
        if (!str_contains($line, ':')) continue;
        [$name, $value] = explode(':', $line, 2);
        if (strcasecmp($name, 'Host') === 0) $hosts[] = ltrim($value, ' ');
    }
    if (count($hosts) !== 1 || preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?(?::[1-9][0-9]{0,4})?$/D', $hosts[0] ?? '') !== 1) {
        $body = "Bad request.\n";
        fwrite($client, "HTTP/1.1 400 Bad Request\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n{$body}");
        fclose($client);
        continue;
    }
    $command = ['/usr/bin/env','-i','FMONITOR_TRUSTED_REQUEST_HOST='.$hosts[0],'FMONITOR_TEST_HANDLER_SENTINEL='.$sentinel,'FMONITOR_TEST_UNTRUSTED_HTTP_HOST=server-stripped.invalid',PHP_BINARY,__DIR__.'/production_http_router_probe.php'];
    $pipes = [];
    $handler = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__,2));
    if (!is_resource($handler)) exit(70);
    fclose($pipes[0]);
    $body = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($handler) !== 0 || $stderr !== '') exit(70);
    fwrite($client, "HTTP/1.1 200 OK\r\nContent-Length: ".strlen($body)."\r\nConnection: close\r\n\r\n{$body}");
    fclose($client);
}
fclose($server);
