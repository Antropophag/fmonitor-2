<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_runtime_lock_' . bin2hex(random_bytes(5));
$admin = new mysqli($host, $user, $password, '', $port);
$runner = null;
$holder = null;

try {
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $runner = new mysqli($host, $user, $password, $database, $port);
    $holder = new mysqli($host, $user, $password, $database, $port);
    $runner->set_charset('utf8mb4');
    $holder->set_charset('utf8mb4');

    $prefix = 'runtime_';
    $lockName = hash('sha256', $database . "\0" . $prefix . "\0canonical-migrations");
    $statement = $holder->prepare('SELECT GET_LOCK(?, 0) AS acquired');
    $statement->bind_param('s', $lockName);
    $statement->execute();
    assertSameValue('1', (string) $statement->get_result()->fetch_assoc()['acquired'], 'fixture owns the canonical catalogue lock');

    $preflightCalls = 0;
    $migrationCalls = 0;
    $started = hrtime(true);
    $contended = CanonicalMigrationApplication::run(
        connection: $runner,
        tablePrefix: $prefix,
        migrations: [1 => static function () use (&$migrationCalls): array {
            ++$migrationCalls;
            return ['applied' => true];
        }],
        databasePreflight: static function () use (&$preflightCalls): int {
            ++$preflightCalls;
            return 1;
        },
    );
    $elapsed = hrtime(true) - $started;

    assertSameValue(['exitCode' => 75, 'result' => ['ok' => false, 'reason' => 'MIGRATION_LOCK_UNAVAILABLE']], $contended, 'concurrent catalogue runner fails closed with a stable operational outcome');
    assertSameValue([0, 0], [$preflightCalls, $migrationCalls], 'contended runner performs neither preflight nor migration work');
    assertSameValue(true, $elapsed < 2_000_000_000, 'duplicate deployment runner fails immediately with scheduling allowance');

    $release = $holder->prepare('SELECT RELEASE_LOCK(?) AS released');
    $release->bind_param('s', $lockName);
    $release->execute();
    assertSameValue('1', (string) $release->get_result()->fetch_assoc()['released'], 'fixture releases catalogue lock');

    $success = CanonicalMigrationApplication::run(
        connection: $runner,
        tablePrefix: $prefix,
        migrations: [1 => static function () use (&$migrationCalls): array {
            ++$migrationCalls;
            return ['applied' => true];
        }],
        databasePreflight: static function () use (&$preflightCalls): int {
            ++$preflightCalls;
            return 1;
        },
    );
    assertSameValue(['exitCode' => 0, 'result' => ['ok' => true, 'schemaVersion' => 1, 'appliedVersions' => [1]]], $success, 'uncontended runner covers preflight and complete catalogue');
    assertSameValue([1, 1], [$preflightCalls, $migrationCalls], 'lock owner executes preflight before catalogue once');

    $probe = $holder->prepare('SELECT GET_LOCK(?, 0) AS acquired');
    $probe->bind_param('s', $lockName);
    $probe->execute();
    assertSameValue('1', (string) $probe->get_result()->fetch_assoc()['acquired'], 'successful migration always releases the catalogue lock');
    $holder->query("SELECT RELEASE_LOCK('{$lockName}')");

    $failed = CanonicalMigrationApplication::run(
        connection: $runner,
        tablePrefix: $prefix,
        migrations: [1 => static function (): array {
            throw new RuntimeException('synthetic migration failure');
        }],
    );
    assertSameValue(['exitCode' => 70, 'result' => ['ok' => false, 'reason' => 'MIGRATION_FAILED']], $failed, 'migration failure remains the canonical stable outcome');
    $failureProbe = $holder->prepare('SELECT GET_LOCK(?, 0) AS acquired');
    $failureProbe->bind_param('s', $lockName);
    $failureProbe->execute();
    assertSameValue('1', (string) $failureProbe->get_result()->fetch_assoc()['acquired'], 'failed migration always releases the catalogue lock');
    $holder->query("SELECT RELEASE_LOCK('{$lockName}')");

    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 canonical migration catalogue concurrency lock\n";
} finally {
    foreach ([$runner, $holder] as $connection) {
        if ($connection instanceof mysqli) {
            try {
                $connection->close();
            } catch (Throwable) {
            }
        }
    }
    try {
        $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    } finally {
        $admin->close();
    }
}
