<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;
use FMonitor2\Runtime\RuntimeConfiguration;
use FMonitor2\Runtime\RuntimeStorage;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = dirname(__DIR__, 2);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$admin = new mysqli($host, $user, $password, '', $port);
$temporaryRoot = realpath(sys_get_temp_dir());
if (!is_string($temporaryRoot)) throw new TestFailure('SETUP_FAILURE: canonical temporary root');
$databases = [];
$storageRoots = [];

/** @return array{exit:int,stdout:string,stderr:string} */
function processReadinessCli(string $root, array $environment): array
{
    $base = getenv();
    if (!is_array($base)) throw new TestFailure('SETUP_FAILURE: process environment');
    $pipes = [];
    $process = proc_open([PHP_BINARY, $root . '/bin/fmonitor2-runtime-check.php'], [
        0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes, $root, array_replace($base, $environment));
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: readiness CLI start');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function processReadinessSnapshot(mysqli $db): array
{
    $tables = $db->query("SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME")->fetch_all(MYSQLI_ASSOC);
    $rows = [];
    foreach ($tables as $table) {
        $name = (string) $table['TABLE_NAME'];
        if (preg_match('/^[A-Za-z0-9_]{1,64}$/D', $name) !== 1) throw new TestFailure('SETUP_FAILURE: schema table identifier');
        $rows[$name] = $db->query("SELECT * FROM `{$name}`")->fetch_all(MYSQLI_ASSOC);
    }
    return [
        'tables' => $tables,
        'columns' => $db->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,ORDINAL_POSITION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC),
        'checks' => $db->query("SELECT TABLE_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() ORDER BY TABLE_NAME,CONSTRAINT_NAME")->fetch_all(MYSQLI_ASSOC),
        'rows' => $rows,
    ];
}

function processReadinessRemove(string $path): void
{
    if (is_link($path) || is_file($path)) { unlink($path); return; }
    if (!is_dir($path)) return;
    foreach (scandir($path) ?: [] as $entry) if ($entry !== '.' && $entry !== '..') processReadinessRemove($path . '/' . $entry);
    rmdir($path);
}

try {
    foreach (['missing process tasks', 'malformed process event type'] as $label) {
        $token = bin2hex(random_bytes(5));
        $database = 't_process_ready_' . $token;
        $databases[] = $database;
        $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db = new mysqli($host, $user, $password, $database, $port);
        $prefix = 'process_';
        $migrated = CanonicalMigrationApplication::run($db, $prefix, ProductionPilotMigrationCatalogue::migrations());
        assertSameValue([0, true, 22], [$migrated['exitCode'], $migrated['result']['ok'] ?? null, $migrated['result']['schemaVersion'] ?? null], "{$label} fixture reaches v22");
        $db->query('CREATE TABLE readiness_ambient(id INT NOT NULL PRIMARY KEY, marker VARCHAR(40) NOT NULL) ENGINE=InnoDB');
        $db->query("INSERT INTO readiness_ambient VALUES(1,'preserve process readiness')");
        $storage = $temporaryRoot . '/fmonitor-process-readiness-' . $token;
        $storageRoots[] = $storage;
        $environment = [
            'FMONITOR_DB_HOST' => $host, 'FMONITOR_DB_PORT' => (string) $port,
            'FMONITOR_DB_NAME' => $database, 'FMONITOR_DB_USER' => $user,
            'FMONITOR_DB_PASSWORD' => $password,
            'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix, 'FMONITOR_LEGACY_TABLE_PREFIX' => $prefix,
            'FMONITOR_SESSION_STATE_ROOT' => $storage . '/state', 'FMONITOR_SESSION_INSTANCE' => 'production',
            'FMONITOR_ARTIFACT_STORAGE_ROOT' => $storage . '/state/artifacts',
            'FMONITOR_ORIGINAL_DB_PASSWORD_FILE' => $storage . '/secret/database-password',
            'FMONITOR_ORIGINAL_SAFE_LOG_FILE' => $storage . '/state/log/original-safe.jsonl',
            'FMONITOR_TRUSTED_REQUEST_HOST' => 'fmonitor.example.test', 'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'https',
        ];
        RuntimeStorage::prepare(RuntimeConfiguration::fromEnvironment($environment));
        assertSameValue([0, "{\"ok\":true}\n", ''], array_values(processReadinessCli($root, $environment)), "healthy {$label} control is ready");
        if ($label === 'missing process tasks') {
            $db->query("DROP TABLE `{$prefix}fm2_process_tasks`");
        } else {
            $db->query("ALTER TABLE `{$prefix}fm2_process_events` MODIFY event_type VARCHAR(79) NOT NULL");
        }
        $before = processReadinessSnapshot($db);
        $result = processReadinessCli($root, $environment);
        assertSameValue(
            [70, "{\"ok\":false,\"reason\":\"SCHEMA_NOT_READY\"}\n", ''],
            [$result['exit'], $result['stdout'], $result['stderr']],
            "INTENTIONAL_RED: public readiness rejects {$label}",
        );
        assertSameValue($before, processReadinessSnapshot($db), "{$label} readiness performs no schema/data repair");
        $db->close();
    }
    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 readiness covers all canonical process tables\n";
} finally {
    foreach ($databases as $database) $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    $admin->close();
    foreach ($storageRoots as $storage) processReadinessRemove($storage);
}
