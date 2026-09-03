<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Tests\Support\FixedPilotSessionClock;
use FMonitor2\Tests\Support\LengthQueuedPilotSessionEntropy;
use FMonitor2\Tests\Support\NativePilotSessionFilesystem;
use FMonitor2\Tests\Support\RecordingPilotSessionObserver;

$repo = dirname(__DIR__, 2);
spl_autoload_register(static function (string $class) use ($repo): void {
    $prefix = 'FMonitor\\IdentityAccess\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = $repo . '/app/IdentityAccess/' . substr($class, strlen($prefix)) . '.php';
    if (is_file($file)) require_once $file;
});
require dirname(__DIR__) . '/Support/PilotSessionStoragePublicApiFixture.php';

// PILOT-SESSION-STORAGE-001 v10 §§3,7,8. A real committed whole-array payload
// is reused through raw HTTP; the fixed CSRF and material bytes are independent
// public/material oracles, not values calculated by production code.
$parent = sys_get_temp_dir() . '/fmonitor2-session-storage-tests';
$root = $parent . '/task-' . bin2hex(random_bytes(12));
if (!is_dir($parent) && !mkdir($parent, 0700)) throw new RuntimeException('SETUP_FAILURE: parent mkdir');
if (!mkdir($root, 0700)) throw new RuntimeException('SETUP_FAILURE: root mkdir');
$server = null;
$remove = static function (string $path) use (&$remove): void {
    if (is_file($path) || is_link($path)) { unlink($path); return; }
    if (!is_dir($path)) return;
    foreach (scandir($path) ?: [] as $entry) if ($entry !== '.' && $entry !== '..') $remove($path . '/' . $entry);
    rmdir($path);
};

try {
    $csrf = str_repeat('c', 64);
    $payload = 'a:1:{s:9:"auth_csrf";s:64:"' . $csrf . '";}';
    $owner = (new FMonitor\IdentityAccess\PilotSessionStorageFactory())->create(
        new FMonitor\IdentityAccess\PilotSessionStorageConfig($root, 'accepted_v10'),
        new NativePilotSessionFilesystem(),
        new FixedPilotSessionClock(1_788_200_000, 1_000),
        new LengthQueuedPilotSessionEntropy([32 => [str_repeat("\x11", 32)], 16 => [str_repeat("\x22", 16)]]),
        new RecordingPilotSessionObserver(),
    );
    $created = $owner->start(null);
    $sessionId = (string) $created->currentSessionId();
    assertSameValue('OK', $owner->writeCommit($sessionId, $payload)->status()->name, 'accepted payload committed by real owner');
    $owner->close();
    $committed = $root . '/sessions/accepted_v10/s-' . $sessionId . '.session';

    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if (!is_resource($socket)) throw new RuntimeException("SETUP_FAILURE: port $errorMessage");
    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);
    $port = (int) substr($name, strrpos($name, ':') + 1);
    $pipes = [];
    $server = proc_open(
        [PHP_BINARY, '-S', "127.0.0.1:$port", $repo . '/tests/Support/pilot_session_storage_http_router.php'],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $repo,
        array_replace($_ENV, [
            'FMONITOR_SESSION_STATE_ROOT' => $root,
            'FMONITOR_SESSION_INSTANCE' => 'accepted_v10',
            'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'http',
        ]),
    );
    if (!is_resource($server)) throw new RuntimeException('SETUP_FAILURE: server');
    foreach ($pipes as $pipe) stream_set_blocking($pipe, false);
    $ready = false;
    $deadline = microtime(true) + 3;
    do {
        usleep(20_000);
        $probe = @stream_socket_client("tcp://127.0.0.1:$port", $probeCode, $probeMessage, .1);
        if (is_resource($probe)) { fclose($probe); $ready = true; break; }
    } while (microtime(true) < $deadline);
    if (!$ready || !proc_get_status($server)['running']) throw new RuntimeException('SETUP_FAILURE: readiness');

    $client = stream_socket_client("tcp://127.0.0.1:$port", $requestCode, $requestMessage, 2);
    if (!is_resource($client)) throw new RuntimeException("SETUP_FAILURE: request $requestMessage");
    fwrite($client, "GET /pilot/login HTTP/1.1\r\nHost: 127.0.0.1:$port\r\nCookie: fm2auth_$port=$sessionId\r\nConnection: close\r\nContent-Length: 0\r\n\r\n");
    $raw = (string) stream_get_contents($client);
    fclose($client);
    [$headers, $body] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
    assertSameValue(true, str_starts_with($headers, 'HTTP/1.1 200 '), 'existing accepted session returns login page');
    assertSameValue(true, str_contains($body, 'name="csrfToken" value="' . $csrf . '"'), 'INTENTIONAL_RED: owner payload restores existing CSRF');
    assertSameValue(false, (bool) preg_match('/^Set-Cookie:/mi', $headers), 'existing session does not issue replacement cookie');
    assertSameValue($payload, file_get_contents($committed), 'unchanged session is not reseeded or re-encoded');

    echo "PASS: PILOT-SESSION-STORAGE-001 v10 accepted payload raw HTTP\n";
} finally {
    if (is_resource($server)) {
        if (proc_get_status($server)['running']) proc_terminate($server, 15);
        $deadline = microtime(true) + .3;
        while (proc_get_status($server)['running'] && microtime(true) < $deadline) usleep(10_000);
        if (proc_get_status($server)['running']) proc_terminate($server, 9);
        foreach ($pipes ?? [] as $pipe) { stream_get_contents($pipe); fclose($pipe); }
        proc_close($server);
    }
    $remove($root);
    if (is_dir($parent) && count(scandir($parent) ?: []) === 2) rmdir($parent);
}
