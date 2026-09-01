<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\DatabaseUnavailable;

/**
 * IDENTITY-ACCESS-SCHEMA-001 Gate 2 restart after Gate 5 rereview v2.
 *
 * Public seam: CanonicalMigrationApplication::run(). The test-owned callback
 * represents database/default-collation preflight; it must execute inside the
 * same redacted boundary as canonical migrations and before any migration.
 *
 * @return array{output:array<string,mixed>,migrationInvocations:int,mutationMarkers:list<string>}
 */
function iaRunPreflightScenario(mysqli $connection, Throwable $preflightFailure): array
{
    $migrationInvocations = 0;
    $mutationMarkers = [];

    try {
        $outcome = CanonicalMigrationApplication::run(
            connection: $connection,
            tablePrefix: 'preflight_',
            migrations: [
                6 => static function () use (&$migrationInvocations, &$mutationMarkers): array {
                    ++$migrationInvocations;
                    $mutationMarkers[] = 'migration-mutated-state';
                    return ['applied' => true];
                },
            ],
            reportFromVersion: 6,
            databasePreflight: static function () use ($preflightFailure): never {
                throw $preflightFailure;
            },
        );
        $output = [
            'exitCode' => $outcome['exitCode'],
            'stdout' => json_encode($outcome['result'], JSON_UNESCAPED_SLASHES) . "\n",
            'stderr' => '',
        ];
    } catch (Throwable $escaped) {
        $output = ['escapedThrowable' => $escaped::class];
    }

    return [
        'output' => $output,
        'migrationInvocations' => $migrationInvocations,
        'mutationMarkers' => $mutationMarkers,
    ];
}

$connection = new mysqli(
    getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
    getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
    getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
    null,
    (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
);

try {
    $actual = [
        'unexpected' => iaRunPreflightScenario($connection, new RuntimeException('sensitive metadata failure')),
        'unavailable' => iaRunPreflightScenario($connection, new DatabaseUnavailable('unsuitable database default')),
    ];
    assertSameValue(
        [
            'unexpected' => [
                'output' => [
                    'exitCode' => 70,
                    'stdout' => "{\"ok\":false,\"reason\":\"MIGRATION_FAILED\"}\n",
                    'stderr' => '',
                ],
                'migrationInvocations' => 0,
                'mutationMarkers' => [],
            ],
            'unavailable' => [
                'output' => [
                    'exitCode' => 69,
                    'stdout' => "{\"ok\":false,\"reason\":\"DATABASE_UNAVAILABLE\"}\n",
                    'stderr' => '',
                ],
                'migrationInvocations' => 0,
                'mutationMarkers' => [],
            ],
        ],
        $actual,
        'Preflight failures are exactly redacted and stop before every migration or mutation.',
    );

    echo "PASS: identity/access preflight remains inside canonical application boundary\n";
} finally {
    $connection->close();
}
