<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';
require dirname(__DIR__) . '/Yii2/Yii2AuthFixture.php';

use FMonitor2\Tests\Yii2\Yii2AuthFixture;

function yafStart(string $root, array $environment, string $frontController, ?int $port = null, ?string $probe = null): array
{
    $processEnvironment = getenv();
    if (!is_array($processEnvironment)) throw new TestFailure('SETUP_FAILURE environment');
    foreach (array_keys($processEnvironment) as $key) if (str_starts_with((string) $key, 'FMONITOR_')) unset($processEnvironment[$key]);
    $environment = array_replace($processEnvironment, $environment);
    if ($port === null) {
        $listener = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
        if (!is_resource($listener)) throw new TestFailure('SETUP_FAILURE port');
        preg_match('/:(\d+)$/D', (string) stream_socket_get_name($listener, false), $match);
        $port = (int) $match[1]; fclose($listener);
    }
    $command = [PHP_BINARY, '-d', 'display_errors=0'];
    if ($probe !== null) array_push($command, '-d', 'auto_prepend_file=' . $probe);
    array_push($command, '-S', '127.0.0.1:' . $port, $root . '/' . $frontController);
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']], $pipes, $root, $environment);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE server');
    $deadline = microtime(true) + 5;
    do {
        $socket = @fsockopen('127.0.0.1', $port, $code, $message, .1);
        if (is_resource($socket)) { fclose($socket); return ['process' => $process, 'port' => $port]; }
        usleep(20000);
    } while (microtime(true) < $deadline);
    throw new TestFailure('SETUP_FAILURE listen');
}

function yafStop(?array &$server): void
{
    if ($server === null) return;
    proc_terminate($server['process']); proc_close($server['process']); $server = null;
}

function yafRequest(array $server, string $method, string $path, array $form, array &$cookies, ?string $case = null): array
{
    $body = $form === [] ? '' : http_build_query($form);
    $headers = ['Host: fmonitor.example.test', 'Connection: close'];
    if ($body !== '') $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    if ($cookies !== []) $headers[] = 'Cookie: ' . implode('; ', array_map(static fn ($key, $value): string => $key . '=' . $value, array_keys($cookies), $cookies));
    if ($case !== null) $headers[] = 'X-FMonitor-Test-Case: ' . $case;
    $context = stream_context_create(['http' => ['ignore_errors' => true, 'follow_location' => 0, 'method' => $method, 'header' => implode("\r\n", $headers), 'content' => $body]]);
    $responseBody = file_get_contents('http://127.0.0.1:' . $server['port'] . $path, false, $context);
    $rawHeaders = $http_response_header ?? []; $responseHeaders = [];
    foreach (array_slice($rawHeaders, 1) as $line) {
        $at = strpos($line, ':');
        if ($at !== false) $responseHeaders[strtolower(substr($line, 0, $at))][] = trim(substr($line, $at + 1));
    }
    foreach ($responseHeaders['set-cookie'] ?? [] as $cookie) if (preg_match('/^([^=;]+)=([^;]*)/D', $cookie, $match) === 1) $cookies[$match[1]] = $match[2];
    preg_match('#^HTTP/\S+ (\d+)#D', $rawHeaders[0] ?? '', $match);
    return ['status' => (int) ($match[1] ?? 0), 'body' => (string) $responseBody];
}

function yafCsrf(string $body): string
{
    if (preg_match('/name="_csrf" value="([^"]+)"/', $body, $match) !== 1) throw new TestFailure('SETUP_FAILURE csrf');
    return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$root = dirname(__DIR__, 2); $fixture = new Yii2AuthFixture($root); $server = null; $log = tempnam(sys_get_temp_dir(), 'yaf-');
try {
    $fixture->db->query("INSERT INTO `{$fixture->prefix}fm2_pilot_role_permissions`(role_id,permission) VALUES(9201,'objects.read')");
    $environment = $fixture->environment();
    $persistentSessionPath = $environment['FMONITOR_YII_SESSION_PATH'];
    $cookies = [];
    $server = yafStart($root, $environment, 'public/yii.php');
    $email = yafRequest($server, 'GET', '/pilot/login', [], $cookies);
    assertSameValue(200, $email['status'], 'accepted Yii login form');
    $password = yafRequest($server, 'POST', '/pilot/login', ['_csrf' => yafCsrf($email['body']), 'email' => $fixture->email], $cookies);
    $login = yafRequest($server, 'POST', '/pilot/login', ['_csrf' => yafCsrf($password['body']), 'email' => $fixture->email, 'password' => $fixture->password], $cookies);
    assertSameValue(303, $login['status'], 'authenticated through accepted Yii front controller');
    $port = $server['port']; yafStop($server);

    $privateRoot = (realpath(sys_get_temp_dir()) ?: throw new TestFailure('SETUP_FAILURE temp')) . '/yaf-private-' . bin2hex(random_bytes(4));
    $environment = array_replace($environment, [
        'FMONITOR_LEGACY_TABLE_PREFIX' => $fixture->prefix,
        'FMONITOR_SESSION_STATE_ROOT' => $privateRoot . '/state',
        'FMONITOR_SESSION_INSTANCE' => 'authenticated_cutover',
        'FMONITOR_ARTIFACT_STORAGE_ROOT' => $privateRoot . '/artifacts',
        'FMONITOR_ORIGINAL_DB_PASSWORD_FILE' => $privateRoot . '/secret/database-password',
        'FMONITOR_ORIGINAL_SAFE_LOG_FILE' => $privateRoot . '/log/original-safe.jsonl',
    ]);
    assertSameValue($persistentSessionPath, $environment['FMONITOR_YII_SESSION_PATH'], 'restart uses exact authenticated Yii session path');
    FMonitor2\Runtime\RuntimeStorage::prepare(FMonitor2\Runtime\RuntimeConfiguration::fromEnvironment($environment));
    $environment['FMONITOR_TEST_INCLUDED_FILES_LOG'] = $log;
    $server = yafStart($root, $environment, 'public/runtime.php', $port, $root . '/tests/Support/included_files_probe.php');
    $cases = [
        ['authenticated-roles', '/pilot/admin/roles', 200],
        ['authenticated-users', '/pilot/admin/users', 200],
        ['authenticated-objects', '/pilot/objects', 200],
        ['authenticated-otiz-denied', '/pilot/otiz', 403],
    ];
    $failures = [];
    foreach ($cases as [$case, $path, $expectedStatus]) {
        $response = yafRequest($server, 'GET', $path, [], $cookies, $case);
        if ($response['status'] !== $expectedStatus) $failures[] = $case . ' expected=' . $expectedStatus . ' status=' . $response['status'];
    }
    yafStop($server); usleep(50000);

    $rows = array_values(array_filter(explode("\n", trim((string) file_get_contents($log))))); $actual = []; $legacy = [];
    foreach ($rows as $row) {
        $entry = json_decode($row, true, flags: JSON_THROW_ON_ERROR);
        $actual[] = [$entry['case'], $entry['method'], $entry['path']];
        foreach ($entry['files'] as $file) if (str_contains($file, '/rapid-pilot/')) $legacy[] = [$entry['case'], $file];
    }
    $expected = array_map(static fn (array $case): array => [$case[0], 'GET', $case[1]], $cases);
    if ($actual !== $expected) $failures[] = 'exact request identities expected=' . json_encode($expected) . ' actual=' . json_encode($actual);
    if ($legacy !== []) $failures[] = 'rapid files=' . json_encode($legacy);
    assertSameValue([], $failures, 'INTENDED_RED: authenticated production include frontier after restart');
} finally {
    yafStop($server); $fixture->close(); if (is_file($log)) unlink($log);
}
