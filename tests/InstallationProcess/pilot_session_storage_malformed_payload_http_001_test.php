<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Tests\Support\FixedPilotSessionClock;
use FMonitor2\Tests\Support\LengthQueuedPilotSessionEntropy;
use FMonitor2\Tests\Support\NativePilotSessionFilesystem;
use FMonitor2\Tests\Support\RecordingPilotSessionObserver;

$repo = dirname(__DIR__, 2);
spl_autoload_register(static function (string $class) use ($repo): void {
    foreach (['FMonitor\\IdentityAccess\\' => $repo . '/app/IdentityAccess/'] as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) require_once $file;
            return;
        }
    }
});
require dirname(__DIR__) . '/Support/PilotSessionStoragePublicApiFixture.php';

// PILOT-SESSION-STORAGE-001 v10 §§3,6,8. The malformed bytes are an
// independently fixed committed payload; raw HTTP is the observable oracle.
$parent = sys_get_temp_dir() . '/fmonitor2-session-storage-tests';
$root = $parent . '/task-' . bin2hex(random_bytes(12));
if (!is_dir($parent) && !mkdir($parent, 0700)) throw new RuntimeException('SETUP_FAILURE: parent mkdir');
if (!mkdir($root, 0700)) throw new RuntimeException('SETUP_FAILURE: root mkdir');
$server = null;
$dbSentinelPid = null;
$dbMarker = $parent . '/db-dispatch-' . bin2hex(random_bytes(10));
$remove = static function (string $path) use (&$remove): void {
    if (is_file($path) || is_link($path)) { unlink($path); return; }
    if (!is_dir($path)) return;
    foreach (scandir($path) ?: [] as $entry) if ($entry !== '.' && $entry !== '..') $remove($path . '/' . $entry);
    rmdir($path);
};

try {
    $owner = (new FMonitor\IdentityAccess\PilotSessionStorageFactory())->create(
        new FMonitor\IdentityAccess\PilotSessionStorageConfig($root, 'http_v10'),
        new NativePilotSessionFilesystem(),
        new FixedPilotSessionClock(1_788_200_000, 1_000),
        new LengthQueuedPilotSessionEntropy([32 => [str_repeat("\x11", 32)], 16 => [str_repeat("\x22", 16)]]),
        new RecordingPilotSessionObserver(),
    );
    $created = $owner->start(null);
    $sessionId = (string) $created->currentSessionId();
    assertSameValue('OK', $created->status()->name, 'seed session starts');
    $csrf = str_repeat('c', 64);
    $payloadCase = getenv('FMONITOR_TEST_SESSION_PAYLOAD_CASE') ?: 'object';
    if (!in_array($payloadCase, ['object', 'reference'], true)) throw new RuntimeException('SETUP_FAILURE: payload case');
    $malformed = $payloadCase === 'reference'
        ? 'a:2:{s:9:"auth_csrf";s:64:"' . $csrf . '";s:1:"x";R:1;}'
        : 'a:2:{s:9:"auth_csrf";s:64:"' . $csrf . '";s:1:"x";O:8:"stdClass":0:{}}';
    $malformedMarker = $payloadCase === 'reference' ? 'R:1' : 'stdClass';
    assertSameValue('OK', $owner->writeCommit($sessionId, $malformed)->status()->name, 'malformed bytes materially committed');
    $owner->close();
    $committed = $root . '/sessions/http_v10/s-' . $sessionId . '.session';
    assertSameValue($malformed, file_get_contents($committed), 'precondition exact malformed bytes');

    if (!function_exists('pcntl_fork')) throw new RuntimeException('SETUP_FAILURE: pcntl required');
    $dbSocket = stream_socket_server('tcp://127.0.0.1:0', $dbErrorCode, $dbErrorMessage);
    if (!is_resource($dbSocket)) throw new RuntimeException("SETUP_FAILURE: DB sentinel $dbErrorMessage");
    $dbName = (string) stream_socket_get_name($dbSocket, false);
    $dbPort = (int) substr($dbName, strrpos($dbName, ':') + 1);
    $dbSentinelPid = pcntl_fork();
    if ($dbSentinelPid === -1) throw new RuntimeException('SETUP_FAILURE: DB sentinel fork');
    if ($dbSentinelPid === 0) {
        $connection = @stream_socket_accept($dbSocket, 5);
        if (is_resource($connection)) {
            file_put_contents($dbMarker, 'dispatch');
            fclose($connection);
        }
        fclose($dbSocket);
        exit(0);
    }
    fclose($dbSocket);

    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if (!is_resource($socket)) throw new RuntimeException("SETUP_FAILURE: port $errorMessage");
    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);
    $port = (int) substr($name, strrpos($name, ':') + 1);
    $env = array_replace($_ENV, [
        'FMONITOR_SESSION_STATE_ROOT' => $root,
        'FMONITOR_SESSION_INSTANCE' => 'http_v10',
        'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'http',
        'FMONITOR_DB_HOST' => '127.0.0.1',
        'FMONITOR_DB_PORT' => (string) $dbPort,
        'FMONITOR_DB_NAME' => 'must_not_dispatch',
        'FMONITOR_DB_USER' => 'must_not_dispatch',
        'FMONITOR_DB_PASSWORD' => 'must_not_dispatch',
        'FMONITOR_PROCESS_TABLE_PREFIX' => '',
    ]);
    $pipes = [];
    $server = proc_open(
        [PHP_BINARY, '-d', 'memory_limit=32M', '-S', "127.0.0.1:$port", $repo . '/tests/Support/pilot_session_storage_http_router.php'],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $repo,
        $env,
    );
    if (!is_resource($server)) throw new RuntimeException('SETUP_FAILURE: server');
    foreach ($pipes as $pipe) stream_set_blocking($pipe, false);
    $deadline = microtime(true) + 3;
    $ready = false;
    do {
        usleep(20_000);
        $probe = @stream_socket_client("tcp://127.0.0.1:$port", $probeCode, $probeMessage, .1);
        if (is_resource($probe)) { fclose($probe); $ready = true; break; }
    } while (microtime(true) < $deadline);
    if (!$ready || !proc_get_status($server)['running']) throw new RuntimeException('SETUP_FAILURE: readiness');

    $client = stream_socket_client("tcp://127.0.0.1:$port", $requestCode, $requestMessage, 2);
    if (!is_resource($client)) throw new RuntimeException("SETUP_FAILURE: request $requestMessage");
    stream_set_timeout($client, 3);
    $requestBody = 'csrfToken=' . $csrf . '&email=fixture%40invalid.test&password=fixed';
    fwrite($client, "POST /pilot/login HTTP/1.1\r\nHost: 127.0.0.1:$port\r\nCookie: fm2auth_$port=$sessionId\r\nContent-Type: application/x-www-form-urlencoded\r\nConnection: close\r\nContent-Length: " . strlen($requestBody) . "\r\n\r\n$requestBody");
    $raw = (string) stream_get_contents($client);
    fclose($client);
    [$rawHeaders, $body] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
    $lines = explode("\r\n", $rawHeaders);
    $statusLine = (string) array_shift($lines);
    $headers = [];
    foreach ($lines as $line) {
        $colon = strpos($line, ':');
        if ($colon !== false) $headers[strtolower(substr($line, 0, $colon))][] = ltrim(substr($line, $colon + 1));
    }
    assertSameValue(true, (bool) preg_match('#^HTTP/1\.1 503(?: |$)#D', $statusLine), 'INTENTIONAL_RED: malformed payload fails closed');
    assertSameValue("Service unavailable.\n", $body, 'exact unavailable body');
    $exactHeaders = [
        'content-type' => ['text/plain; charset=UTF-8'],
        'content-length' => ['21'],
        'retry-after' => ['60'],
        'cache-control' => ['no-store'],
        'x-content-type-options' => ['nosniff'],
        'referrer-policy' => ['no-referrer'],
        'x-frame-options' => ['DENY'],
        'content-security-policy' => ["default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'"],
        'permissions-policy' => ['camera=(), microphone=(), geolocation=()'],
        'cross-origin-opener-policy' => ['same-origin'],
    ];
    foreach ($exactHeaders as $name => $values) assertSameValue($values, $headers[$name] ?? [], "exact unavailable $name");
    foreach (['set-cookie', 'location', 'www-authenticate', 'access-control-allow-origin', 'server', 'x-powered-by'] as $name) assertSameValue([], $headers[$name] ?? [], "forbidden $name");
    $transportHeaders = ['date', 'connection', 'host'];
    assertSameValue([], array_values(array_diff(array_keys($headers), [...array_keys($exactHeaders), ...$transportHeaders])), 'no unspecified application headers');
    assertSameValue(false, str_contains($body, '<form'), 'login route body not dispatched');
    assertSameValue($malformed, file_get_contents($committed), 'malformed committed bytes unchanged');
    usleep(20_000);
    $stderr = (string) stream_get_contents($pipes[2]);
    assertSameValue(false, is_file($dbMarker), 'authentication DB boundary not reached');
    $sessionLogLines = preg_grep('/PILOT_SESSION_UNAVAILABLE/', preg_split('/\R/', trim($stderr)) ?: []);
    assertSameValue(
        1,
        count($sessionLogLines),
        'exactly one session-unavailable log',
    );
    assertSameValue(1, preg_match('/^PILOT_SESSION_UNAVAILABLE category=payload_invalid correlation_id=[0-9a-f]{12}$/D', (string) reset($sessionLogLines)), 'exact safe payload-invalid log');
    assertSameValue(false, str_contains($stderr, 'pilot_http_unexpected_failure'), 'no fallback dispatch/entrypoint failure');
    foreach ([$malformed, 'auth_csrf', $malformedMarker, $csrf, 'fixture@invalid.test', 'fixed', $sessionId, $root] as $secret) {
        assertSameValue(false, str_contains($raw, $secret), 'secret absent from HTTP');
        assertSameValue(false, str_contains($stderr, $secret), 'secret absent from log');
    }

    echo "PASS: PILOT-SESSION-STORAGE-001 v10 $payloadCase payload raw HTTP\n";
} finally {
    if (is_resource($server)) {
        if (proc_get_status($server)['running']) proc_terminate($server, 15);
        $deadline = microtime(true) + .3;
        while (proc_get_status($server)['running'] && microtime(true) < $deadline) usleep(10_000);
        if (proc_get_status($server)['running']) proc_terminate($server, 9);
        foreach ($pipes ?? [] as $pipe) { stream_get_contents($pipe); fclose($pipe); }
        proc_close($server);
    }
    if (is_int($dbSentinelPid) && $dbSentinelPid > 0) {
        $waited = pcntl_waitpid($dbSentinelPid, $dbStatus, WNOHANG);
        if ($waited === 0) {
            posix_kill($dbSentinelPid, SIGKILL);
            pcntl_waitpid($dbSentinelPid, $dbStatus);
        }
    }
    if (is_file($dbMarker)) unlink($dbMarker);
    $remove($root);
    if (is_dir($parent) && count(scandir($parent) ?: []) === 2) rmdir($parent);
}
