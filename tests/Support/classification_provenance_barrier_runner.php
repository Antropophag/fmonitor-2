<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\ClassificationProvenanceSchemaMigration;

if ($argc !== 5) {
    fwrite(STDERR, "invalid verifier arguments\n");
    exit(2);
}

[, $databaseName, $arrivalPath, $releasePath, $token] = $argv;
$connection = null;

try {
    $connection = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        $databaseName,
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $connection->set_charset('utf8mb4');

    $beforeCreate = static function () use ($arrivalPath, $releasePath, $token): void {
        if (file_put_contents($arrivalPath, $token, LOCK_EX) !== strlen($token)) {
            throw new RuntimeException('Verifier arrival could not be published.');
        }

        $deadline = microtime(true) + 8;
        while (!is_file($releasePath)) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Verifier release timeout.');
            }
            usleep(20000);
        }
    };

    // The third argument is the approved verifier-only composition seam. The
    // production CLI never receives this callback or an activation switch.
    $result = ClassificationProvenanceSchemaMigration::apply($connection, '', $beforeCreate);
    if (($result['reason'] ?? null) === 'SCHEMA_MIGRATION_CONFLICT') {
        echo "{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":11}\n";
        exit(2);
    }
    echo json_encode([
        'ok' => true,
        'schemaVersion' => 11,
        'appliedVersions' => ($result['applied'] ?? false) ? [11] : [],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
} catch (Throwable) {
    echo "{\"ok\":false,\"reason\":\"MIGRATION_FAILED\"}\n";
    exit(70);
} finally {
    if ($connection instanceof mysqli) {
        $connection->close();
    }
}
