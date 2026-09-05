<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigration;

// OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4: real second-CREATE denial/retry.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$admin = new mysqli($host, getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
    getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local', '', $port);
$token = bin2hex(random_bytes(6));
$database = 'fm2_ods_denial_' . $token;
$user = 'ods_denial_' . $token;
$password = bin2hex(random_bytes(16));
$createdDatabase = $createdUser = false;
$limited = null;
$prefix = 'denial_';
$details = $prefix . 'fm2_pilot_object_details';
$quarantine = $prefix . 'fm2_pilot_object_detail_quarantine';
try {
    $admin->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $createdDatabase = true;
    $admin->select_db($database);
    $admin->query("CREATE USER '{$user}'@'%' IDENTIFIED BY '{$password}'");
    $createdUser = true;
    $admin->query("GRANT SELECT ON `{$database}`.* TO '{$user}'@'%'");
    $admin->query("GRANT CREATE ON `{$database}`.`{$details}` TO '{$user}'@'%'");
    $limited = new mysqli($host, $user, $password, $database, $port);
    $limited->set_charset('utf8mb4');
    assertSameValue($user . '@%', $limited->query('SELECT CURRENT_USER()')->fetch_column(), 'exact isolated principal');
    assertSameValue(true, $limited->thread_id !== $admin->thread_id, 'independent administrator observation');
    $grants = $admin->query("SELECT TABLE_NAME,PRIVILEGE_TYPE FROM information_schema.TABLE_PRIVILEGES WHERE TABLE_SCHEMA='{$database}' ORDER BY TABLE_NAME,PRIVILEGE_TYPE")->fetch_all(MYSQLI_ASSOC);
    assertSameValue([['TABLE_NAME'=>$details,'PRIVILEGE_TYPE'=>'CREATE']], $grants, 'only the first exact table has a CREATE grant');
    echo "PREREQUISITE PASS: isolated principal can CREATE only the exact details table\n";
    assertSameValue(true, class_exists(ObjectDetailSnapshotSchemaMigration::class), 'INTENDED_RED: public v12 migration owner is absent');

    $failed = false;
    try { ObjectDetailSnapshotSchemaMigration::apply($limited, $prefix); }
    catch (DatabaseUnavailable) { $failed = true; }
    assertSameValue(true, $failed, 'real second-CREATE denial is typed unavailable');
    $tables = static fn(): array => array_column($admin->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    assertSameValue([$details], $tables(), 'first CREATE is durable, second table absent');
    assertSameValue([], $admin->query("SELECT * FROM `{$details}`")->fetch_all(MYSQLI_ASSOC), 'denied migration did not seed data');
    assertSameValue(false, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($limited, $prefix), 'partial family is not complete');
    $lock = hash('sha256', "object-detail-schema-v1\0{$database}\0{$prefix}");
    assertSameValue(1, (int)$admin->query("SELECT IS_FREE_LOCK('{$lock}')")->fetch_column(), 'DDL failure released the migration lock while caller remains connected');

    $admin->query("INSERT INTO `{$details}` VALUES(7001,'fixture-v1',REPEAT('a',64),'{\"fixture\":true}','2026-09-05T09:00:00Z')");
    $before = $admin->query("SELECT * FROM `{$details}` ORDER BY object_id")->fetch_all(MYSQLI_ASSOC);
    $admin->query("GRANT CREATE ON `{$database}`.`{$quarantine}` TO '{$user}'@'%'");
    assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>[$quarantine]], ObjectDetailSnapshotSchemaMigration::apply($limited, $prefix), 'ordinary retry creates only the missing member');
    assertSameValue([$quarantine,$details], $tables(), 'retry completes the exact family');
    assertSameValue($before, $admin->query("SELECT * FROM `{$details}` ORDER BY object_id")->fetch_all(MYSQLI_ASSOC), 'retry preserves every existing sentinel byte');
    assertSameValue([], $admin->query("SELECT * FROM `{$quarantine}`")->fetch_all(MYSQLI_ASSOC), 'new quarantine table is data-free');
    assertSameValue(true, ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($limited, $prefix), 'complete schema after privilege correction');
    assertSameValue(['applied'=>false,'schemaVersion'=>12,'tablesCreated'=>[]], ObjectDetailSnapshotSchemaMigration::apply($limited, $prefix), 'complete repeat is a no-op');
    assertSameValue(1, (int)$admin->query("SELECT IS_FREE_LOCK('{$lock}')")->fetch_column(), 'retry and repeat release their locks');
    echo "PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real DDL-denial partial recovery\n";
} finally {
    try { if ($limited instanceof mysqli) $limited->close(); }
    finally {
        try { if ($createdUser) $admin->query("DROP USER '{$user}'@'%'"); }
        finally {
            try { if ($createdDatabase) $admin->query("DROP DATABASE `{$database}`"); }
            finally { $admin->close(); }
        }
    }
}
