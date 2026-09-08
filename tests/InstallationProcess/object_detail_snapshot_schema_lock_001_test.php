<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigration;

// OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4: real independently held lock,
// no schema mutation on timeout, release and ordinary retry.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect = static function (?string $database = null): mysqli {
    $db = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        $database, (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $db->set_charset('utf8mb4');
    return $db;
};
$admin = $connect();
$database = 'fm2_ods_lock_' . bin2hex(random_bytes(6));
$created = false;
$holder = $worker = null;
$held = false;
$prefix = 'held_';
$lock = hash('sha256', "object-detail-schema-v1\0{$database}\0{$prefix}");
$failures = [];
try {
    $admin->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $holder = $connect($database);
    $worker = $connect($database);
    $holder->query('CREATE TABLE decoy(id INT PRIMARY KEY, payload VARBINARY(20) NOT NULL) ENGINE=InnoDB');
    $holder->query("INSERT INTO decoy VALUES(1,X'006465636F79')");
    $inventory = static fn(mysqli $db): array => $db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC);
    $before = $inventory($holder);
    assertSameValue([['TABLE_NAME'=>'decoy']], $before, 'isolated namespace baseline');
    assertSameValue(true, $holder->thread_id !== $worker->thread_id, 'independent real connections');
    assertSameValue(1, (int)$holder->query("SELECT GET_LOCK('{$lock}',0)")->fetch_column(), 'fixture acquires exact specified lock');
    $held = true;
    assertSameValue($holder->thread_id, (int)$worker->query("SELECT IS_USED_LOCK('{$lock}')")->fetch_column(), 'worker independently observes holder identity');
    echo "PREREQUISITE PASS: real distinct connection holds the exact migration lock\n";

    $unavailable = false;
    try { ObjectDetailSnapshotSchemaMigration::apply($worker, $prefix); }
    catch (DatabaseUnavailable) { $unavailable = true; }
    if (!$unavailable) $failures[] = 'held migration lock must return DatabaseUnavailable';
    if ($inventory($holder) !== $before) $failures[] = 'held migration lock must prevent all family creation';
    assertSameValue($holder->thread_id, (int)$holder->query("SELECT IS_USED_LOCK('{$lock}')")->fetch_column(), 'failed caller cannot release another connection lock');
    assertSameValue([['id'=>'1','payload'=>"\0decoy"]], $holder->query('SELECT * FROM decoy ORDER BY id')->fetch_all(MYSQLI_ASSOC), 'decoy unchanged');

    assertSameValue(1, (int)$holder->query("SELECT RELEASE_LOCK('{$lock}')")->fetch_column(), 'release owned fixture lock');
    $held = false;
    // Avoid disguising the already observed product mismatches as retry setup.
    if ($failures !== []) throw new TestFailure('INTENDED_RED: ' . implode('; ', $failures));
    assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>['held_fm2_pilot_object_detail_quarantine','held_fm2_pilot_object_details']], ObjectDetailSnapshotSchemaMigration::apply($worker, $prefix), 'ordinary retry after release creates the family');
    assertSameValue(1, (int)$holder->query("SELECT GET_LOCK('{$lock}',0)")->fetch_column(), 'successful migration released its lock');
    $held = true;
    echo "PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real held-lock rejection and retry\n";
} finally {
    try {
        if ($held && $holder instanceof mysqli) $holder->query("SELECT RELEASE_LOCK('{$lock}')");
    } finally {
        try { if ($worker instanceof mysqli) $worker->close(); }
        finally {
            try { if ($holder instanceof mysqli) $holder->close(); }
            finally {
                try { if ($created) $admin->query("DROP DATABASE `{$database}`"); }
                finally { $admin->close(); }
            }
        }
    }
}
