<?php
declare(strict_types=1);
// FMONITOR_TEST_DB: isolated migration-18 public-seam regression.
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\AssignmentOrderSelectionUnknownEmploymentSchemaMigration as Migration18;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;

function aouescQuote(string $identifier): string
{
    if (preg_match('/^[A-Za-z0-9_]+$/D', $identifier) !== 1) { throw new TestFailure('unsafe identifier'); }
    return '`' . $identifier . '`';
}

$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$admin = new mysqli($host, $user, $password, '', $port);
$databases = [];

try {
    foreach (['utf8mb4_general_ci', 'utf8mb4_bin'] as $collation) {
        $database = 't_m18_coll_' . bin2hex(random_bytes(5));
        $databases[] = $database;
        $admin->query('CREATE DATABASE ' . aouescQuote($database) . ' CHARACTER SET utf8mb4 COLLATE ' . $collation);
        $db = new mysqli($host, $user, $password, $database, $port);
        $prefix = 'm18_';
        try {
            foreach (ProductionPilotMigrationCatalogue::migrations() as $version => $migration) {
                if ($version >= 18) { break; }
                is_string($migration) ? $migration::apply($db, $prefix) : $migration($db, $prefix);
            }
            assertSameValue(false, Migration18::isReady($db, $prefix), "$collation predecessor is not ready");
            assertSameValue(['applied' => true], Migration18::apply($db, $prefix), "$collation migration 18 applies");
            assertSameValue(true, Migration18::isReady($db, $prefix), "$collation migration 18 is ready");
            assertSameValue(['applied' => false], Migration18::apply($db, $prefix), "$collation migration 18 repeat is a no-op");
        } finally {
            $db->close();
        }
    }
    echo "ASSIGNMENT_ORDER_UNKNOWN_EMPLOYMENT_SCHEMA_COLLATION_001_OK\n";
} finally {
    foreach ($databases as $database) { $admin->query('DROP DATABASE IF EXISTS ' . aouescQuote($database)); }
    $admin->close();
}
