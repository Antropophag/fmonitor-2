<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;
use FMonitor2\Runtime\RuntimeConfiguration;
use FMonitor2\Runtime\RuntimeStorage;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = getenv('FMONITOR_TEST_SOURCE_ROOT') ?: dirname(__DIR__, 2);
if (!is_dir($root)) throw new TestFailure('SETUP_FAILURE: source root');
$root = realpath($root);
if (!is_string($root)) throw new TestFailure('SETUP_FAILURE: canonical source root');
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$admin = new mysqli($host, $user, $password, '', $port);
$token = bin2hex(random_bytes(6));
$database = 't_yii2_ready_' . $token;
$prefix = 'yii2_';
$temporaryBase = realpath(sys_get_temp_dir());
if (!is_string($temporaryBase)) throw new TestFailure('SETUP_FAILURE: canonical temporary root');
$storage = $temporaryBase . '/fmonitor-yii2-readiness-' . $token;

/** @return array<string,string> */
function yii2CleanEnvironment(array $additional): array
{
    $environment = getenv();
    if (!is_array($environment)) throw new TestFailure('SETUP_FAILURE: process environment');
    foreach (array_keys($environment) as $name) if (str_starts_with((string) $name, 'FMONITOR_')) unset($environment[$name]);
    foreach ($additional as $name => $value) $environment[$name] = $value;
    return $environment;
}

/** @return array{process:resource,pipes:array,port:int} */
function yii2StartServer(string $root, array $environment): array
{
    $listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!is_resource($listener)) throw new TestFailure("SETUP_FAILURE: reserve HTTP port {$errno} {$error}");
    $address = stream_socket_get_name($listener, false);
    fclose($listener);
    if (!is_string($address) || preg_match('/:(\d+)$/D', $address, $match) !== 1) throw new TestFailure('SETUP_FAILURE: resolve HTTP port');
    $httpPort = (int) $match[1];
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=0', '-S', '127.0.0.1:' . $httpPort, $root . '/public/yii.php'], [
        0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes, $root, yii2CleanEnvironment($environment));
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: start Yii HTTP server');
    $deadline = microtime(true) + 5;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) {
            $diagnostic = stream_get_contents($pipes[2]);
            throw new TestFailure('SETUP_FAILURE: Yii HTTP server exited: ' . substr((string) $diagnostic, 0, 500));
        }
        $socket = @fsockopen('127.0.0.1', $httpPort, $ignoredCode, $ignoredMessage, 0.1);
        if (is_resource($socket)) { fclose($socket); return ['process' => $process, 'pipes' => $pipes, 'port' => $httpPort]; }
        usleep(20000);
    } while (microtime(true) < $deadline);
    throw new TestFailure('SETUP_FAILURE: Yii HTTP server did not listen');
}

function yii2StopServer(array $server): void
{
    proc_terminate($server['process']);
    $deadline = microtime(true) + 3;
    while (proc_get_status($server['process'])['running'] && microtime(true) < $deadline) usleep(20000);
    if (proc_get_status($server['process'])['running']) proc_terminate($server['process'], 9);
    foreach ($server['pipes'] as $pipe) if (is_resource($pipe)) fclose($pipe);
    proc_close($server['process']);
}

/** @return array{status:int,headers:list<string>,body:string} */
function yii2Http(array $server, string $path): array
{
    $context = stream_context_create(['http' => [
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5,
        'method' => 'GET', 'header' => "Host: fmonitor.example.test\r\nConnection: close",
    ]]);
    $body = file_get_contents('http://127.0.0.1:' . $server['port'] . $path, false, $context);
    $headers = $http_response_header ?? [];
    $status = preg_match('#^HTTP/\S+ (\d+)#D', $headers[0] ?? '', $match) === 1 ? (int) $match[1] : 0;
    return ['status' => $status, 'headers' => $headers, 'body' => is_string($body) ? $body : ''];
}

/** @return array{exit:int,stdout:string,stderr:string} */
function yii2Cli(string $root, array $environment): array
{
    $pipes = [];
    $process = proc_open([PHP_BINARY, $root . '/bin/yii', 'health/ready'], [
        0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes, $root, yii2CleanEnvironment($environment));
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: start Yii console readiness');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return ['exit' => proc_close($process), 'stdout' => (string) $stdout, 'stderr' => (string) $stderr];
}

function yii2SchemaSnapshot(mysqli $db): array
{
    $tables = $db->query("SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME")->fetch_all(MYSQLI_ASSOC);
    $rows = [];
    foreach ($tables as $table) {
        $name = (string) $table['TABLE_NAME'];
        if (preg_match('/^[A-Za-z0-9_]{1,64}$/D', $name) !== 1) throw new TestFailure('SETUP_FAILURE: schema table identifier');
        $contents = $db->query("SELECT * FROM `{$name}`")->fetch_all(MYSQLI_ASSOC);
        usort($contents, static fn(array $left, array $right): int => strcmp(json_encode($left, JSON_THROW_ON_ERROR), json_encode($right, JSON_THROW_ON_ERROR)));
        $rows[$name] = $contents;
    }
    return [
        'tables' => $tables,
        'columns' => $db->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,ORDINAL_POSITION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC),
        'rows' => $rows,
    ];
}

function yii2TreeSnapshot(string $root): array
{
    if (!file_exists($root) && !is_link($root)) return [];
    $paths = [$root];
    if (is_dir($root) && !is_link($root)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) $paths[] = $item->getPathname();
    }
    sort($paths, SORT_STRING);
    $snapshot = [];
    foreach ($paths as $path) {
        $relative = $path === $root ? '.' : substr($path, strlen($root) + 1);
        $snapshot[$relative] = [
            'type' => is_link($path) ? 'link' : (is_dir($path) ? 'dir' : 'file'),
            'mode' => fileperms($path) & 0777,
            'hash' => is_file($path) && !is_link($path) ? hash_file('sha256', $path) : null,
        ];
    }
    return $snapshot;
}

function yii2AssertSafeUnavailable(array $http, array $cli, string $secret, string $label): void
{
    assertSameValue(503, $http['status'], "{$label} HTTP readiness status");
    assertSameValue(['ok' => false, 'reason' => 'SERVICE_UNAVAILABLE'], json_decode($http['body'], true, 8, JSON_THROW_ON_ERROR), "{$label} HTTP safe body");
    assertSameValue(true, in_array('Cache-Control: no-store', $http['headers'], true), "{$label} HTTP no-store");
    assertSameValue(false, (bool) preg_grep('/^Set-Cookie:/i', $http['headers']), "{$label} HTTP creates no cookie");
    assertSameValue(false, str_contains(implode("\n", $http['headers']) . $http['body'], $secret), "{$label} HTTP secret redaction");
    assertSameValue([true, "{\"ok\":false,\"reason\":\"SERVICE_UNAVAILABLE\"}\n", ''], [$cli['exit'] !== 0, $cli['stdout'], $cli['stderr']], "{$label} console safe failure");
    assertSameValue(false, str_contains($cli['stdout'] . $cli['stderr'], $secret), "{$label} console secret redaction");
}

function yii2RemoveTree(string $path): void
{
    if (is_link($path) || is_file($path)) { unlink($path); return; }
    if (!is_dir($path)) return;
    foreach (scandir($path) ?: [] as $entry) if ($entry !== '.' && $entry !== '..') yii2RemoveTree($path . '/' . $entry);
    rmdir($path);
}

$db = null;
try {
    assertSameValue(true, is_file($root . '/public/yii.php'), 'INTENTIONAL_RED: YII2-RUNTIME-001 public web entrypoint is absent');
    assertSameValue(true, is_file($root . '/bin/yii'), 'INTENTIONAL_RED: YII2-RUNTIME-001 console entrypoint is absent');
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db = new mysqli($host, $user, $password, $database, $port);
    $migrated = CanonicalMigrationApplication::run($db, $prefix, ProductionPilotMigrationCatalogue::migrations());
    assertSameValue([0, true, 23], [$migrated['exitCode'], $migrated['result']['ok'] ?? null, $migrated['result']['schemaVersion'] ?? null], 'SETUP_FAILURE: fixture reaches canonical v23');
    $db->query('CREATE TABLE readiness_ambient(id INT NOT NULL PRIMARY KEY, marker VARCHAR(40) NOT NULL) ENGINE=InnoDB');
    $db->query("INSERT INTO readiness_ambient VALUES(1,'preserve yii readiness')");
    $environment = [
        'FMONITOR_DB_HOST' => $host, 'FMONITOR_DB_PORT' => (string) $port,
        'FMONITOR_DB_NAME' => $database, 'FMONITOR_DB_USER' => $user,
        'FMONITOR_DB_PASSWORD' => $password,
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix, 'FMONITOR_LEGACY_TABLE_PREFIX' => $prefix,
        'FMONITOR_SESSION_STATE_ROOT' => $storage . '/state', 'FMONITOR_SESSION_INSTANCE' => 'yii2-readiness',
        'FMONITOR_ARTIFACT_STORAGE_ROOT' => $storage . '/state/artifacts',
        'FMONITOR_ORIGINAL_DB_PASSWORD_FILE' => $storage . '/secret/database-password',
        'FMONITOR_ORIGINAL_SAFE_LOG_FILE' => $storage . '/state/log/original-safe.jsonl',
        'FMONITOR_TRUSTED_REQUEST_HOST' => 'fmonitor.example.test', 'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'https',
    ];
    RuntimeStorage::prepare(RuntimeConfiguration::fromEnvironment($environment));
    $beforeHealthySchema = yii2SchemaSnapshot($db);
    $beforeHealthyTree = yii2TreeSnapshot($storage);
    $server = yii2StartServer($root, $environment);
    try { $healthyHttp = yii2Http($server, '/health/ready'); } finally { yii2StopServer($server); }
    $healthyCli = yii2Cli($root, $environment);
    assertSameValue([200, ['ok' => true]], [$healthyHttp['status'], json_decode($healthyHttp['body'], true, 8, JSON_THROW_ON_ERROR)], 'prepared HTTP readiness is healthy');
    assertSameValue(true, in_array('Cache-Control: no-store', $healthyHttp['headers'], true), 'prepared HTTP readiness is no-store');
    assertSameValue(false, (bool) preg_grep('/^Set-Cookie:/i', $healthyHttp['headers']), 'prepared HTTP readiness creates no cookie');
    assertSameValue([0, "{\"ok\":true}\n", ''], [$healthyCli['exit'], $healthyCli['stdout'], $healthyCli['stderr']], 'prepared console readiness is healthy');
    assertSameValue($beforeHealthySchema, yii2SchemaSnapshot($db), 'healthy readiness performs no DB write');
    assertSameValue($beforeHealthyTree, yii2TreeSnapshot($storage), 'healthy readiness performs no private-state write');

    $databaseFailureEnvironment = array_replace($environment, ['FMONITOR_DB_PORT' => '1']);
    $beforeDatabaseFailure = yii2SchemaSnapshot($db);
    $beforeDatabaseTree = yii2TreeSnapshot($storage);
    $server = yii2StartServer($root, $databaseFailureEnvironment);
    try { $databaseHttp = yii2Http($server, '/health/ready'); } finally { yii2StopServer($server); }
    yii2AssertSafeUnavailable($databaseHttp, yii2Cli($root, $databaseFailureEnvironment), $password, 'database failure');
    assertSameValue($beforeDatabaseFailure, yii2SchemaSnapshot($db), 'database failure performs no DB write');
    assertSameValue($beforeDatabaseTree, yii2TreeSnapshot($storage), 'database failure performs no private-state write');

    $heldState = $storage . '/state-held';
    rename($environment['FMONITOR_SESSION_STATE_ROOT'], $heldState);
    $beforeStorageFailure = yii2SchemaSnapshot($db);
    $beforeStorageTree = yii2TreeSnapshot($storage);
    $server = yii2StartServer($root, $environment);
    try { $storageHttp = yii2Http($server, '/health/ready'); } finally { yii2StopServer($server); }
    yii2AssertSafeUnavailable($storageHttp, yii2Cli($root, $environment), $password, 'storage failure');
    assertSameValue(false, file_exists($environment['FMONITOR_SESSION_STATE_ROOT']), 'storage failure does not prepare missing path');
    assertSameValue($beforeStorageFailure, yii2SchemaSnapshot($db), 'storage failure performs no DB write');
    assertSameValue($beforeStorageTree, yii2TreeSnapshot($storage), 'storage failure performs no private-state write');
    rename($heldState, $environment['FMONITOR_SESSION_STATE_ROOT']);

    $db->query('DROP TABLE `' . $prefix . 'fm2_pilot_object_details`');
    $beforeSchemaFailure = yii2SchemaSnapshot($db);
    $beforeSchemaTree = yii2TreeSnapshot($storage);
    $server = yii2StartServer($root, $environment);
    try { $schemaHttp = yii2Http($server, '/health/ready'); } finally { yii2StopServer($server); }
    yii2AssertSafeUnavailable($schemaHttp, yii2Cli($root, $environment), $password, 'schema failure');
    assertSameValue($beforeSchemaFailure, yii2SchemaSnapshot($db), 'schema failure performs no DB repair/write');
    assertSameValue($beforeSchemaTree, yii2TreeSnapshot($storage), 'schema failure performs no private-state write');

    echo "PASS: YII2-RUNTIME-001 prepared readiness and safe infrastructure failures\n";
} finally {
    if ($db instanceof mysqli) $db->close();
    $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    $admin->close();
    yii2RemoveTree($storage);
}
