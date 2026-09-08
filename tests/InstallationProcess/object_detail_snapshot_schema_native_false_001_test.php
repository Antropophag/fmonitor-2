<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigrationVerification;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaObserver;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaPhase;

// OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4: events only follow real CREATE success.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$admin = new mysqli($host, getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local', '', $port);
$token = bin2hex(random_bytes(6));
$database = 'fm2_ods_false_' . $token;
$user = 'ods_false_' . $token;
$password = bin2hex(random_bytes(16));
$createdDb = $createdUser = false;
$limited = null;
try {
    $admin->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $createdDb = true;
    $admin->select_db($database);
    $admin->query("CREATE USER '{$user}'@'%' IDENTIFIED BY '{$password}'");
    $createdUser = true;
    $admin->query("GRANT SELECT ON `{$database}`.* TO '{$user}'@'%'");
    $admin->query("GRANT CREATE ON `{$database}`.`n_fm2_pilot_object_details` TO '{$user}'@'%'");
    $limited = new mysqli($host, $user, $password, $database, $port);
    $limited->set_charset('utf8mb4');
    assertSameValue($user.'@%', $limited->query('SELECT CURRENT_USER()')->fetch_column(), 'exact disposable principal');
    assertSameValue([['TABLE_NAME'=>'n_fm2_pilot_object_details','PRIVILEGE_TYPE'=>'CREATE']], $admin->query("SELECT TABLE_NAME,PRIVILEGE_TYPE FROM information_schema.TABLE_PRIVILEGES WHERE TABLE_SCHEMA='{$database}' ORDER BY TABLE_NAME,PRIVILEGE_TYPE")->fetch_all(MYSQLI_ASSOC), 'only details CREATE permitted');
    $observer = new class implements ObjectDetailSnapshotSchemaObserver {
        public array $events = [];
        public function observe(ObjectDetailSnapshotSchemaPhase $phase): void { $this->events[] = $phase->value; }
    };
    $failed = false;
    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        assertSameValue(false, $limited->query('CREATE TABLE denied_probe(id INT)'), 'fixture proves real native false for denied CREATE');
        ObjectDetailSnapshotSchemaMigrationVerification::apply($limited, 'n_', $observer);
    }
    catch (DatabaseUnavailable) { $failed = true; }
    finally { mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); }
    assertSameValue(true, $failed, 'native CREATE failure returns typed unavailable');
    assertSameValue(['n_fm2_pilot_object_details'], array_column($admin->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME')->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME'), 'real failed CREATE leaves only durable details');
    $lock = hash('sha256', "object-detail-schema-v1\0{$database}\0n_");
    assertSameValue(1, (int)$admin->query("SELECT IS_FREE_LOCK('{$lock}')")->fetch_column(), 'native failure releases lock');
    assertSameValue(['lock_acquired','details_created'], $observer->events, 'INTENDED_RED: failed CREATE must not emit quarantine_created');
    echo "PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 native-false CREATE has no false success event\n";
} finally {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try { if ($limited instanceof mysqli) $limited->close(); }
    finally {
        try { if ($createdUser) $admin->query("DROP USER '{$user}'@'%'"); }
        finally {
            try { if ($createdDb) $admin->query("DROP DATABASE `{$database}`"); }
            finally { $admin->close(); }
        }
    }
}
