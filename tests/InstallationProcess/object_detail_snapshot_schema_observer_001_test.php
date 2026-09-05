<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigration;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigrationVerification;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaObserver;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaPhase;

// OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4, observer events and interruption.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connect = static function (?string $name = null): mysqli {
    $db = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        $name, (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $db->set_charset('utf8mb4');
    return $db;
};
$admin = $connect();
$database = 'fm2_ods_observer_' . bin2hex(random_bytes(6));
$created = false;
$reader = null;
try {
    $admin->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $reader = $connect($database);
    assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>['control_fm2_pilot_object_detail_quarantine','control_fm2_pilot_object_details']], ObjectDetailSnapshotSchemaMigration::apply($reader, 'control_'), 'real production family prerequisite');
    echo "PREREQUISITE PASS: real canonical family and independent observation connection\n";
    assertSameValue(true, class_exists(ObjectDetailSnapshotSchemaMigrationVerification::class)
        && interface_exists(ObjectDetailSnapshotSchemaObserver::class)
        && enum_exists(ObjectDetailSnapshotSchemaPhase::class),
        'INTENDED_RED: approved observer verification API is missing');

    $tables = static function (string $prefix) use ($reader): array {
        return array_column($reader->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('{$prefix}fm2_pilot_object_details','{$prefix}fm2_pilot_object_detail_quarantine') ORDER BY BINARY TABLE_NAME")->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    };
    $observer = static fn(Closure $callback): ObjectDetailSnapshotSchemaObserver => new class($callback) implements ObjectDetailSnapshotSchemaObserver {
        public function __construct(private Closure $callback) {}
        public function observe(ObjectDetailSnapshotSchemaPhase $phase): void { ($this->callback)($phase); }
    };

    foreach (['normal', 'first', 'closed'] as $scenario) {
        $prefix = $scenario . '_';
        $worker = $connect($database);
        $workerClosed = false;
        $workerId = $worker->thread_id;
        assertSameValue(true, $workerId !== $reader->thread_id, 'observer uses an independent read connection');
        $lock = hash('sha256', "object-detail-schema-v1\0{$database}\0{$prefix}");
        $events = [];
        $probeFailure = null;
        $callback = function (ObjectDetailSnapshotSchemaPhase $phase) use (&$events, &$probeFailure, &$workerClosed, $worker, $workerId, $reader, $lock, $tables, $prefix, $scenario): void {
            $events[] = $phase->value;
            // Preserve assertion failures separately: production must translate
            // adapter throwables, which must not disguise an invalid test probe.
            try {
                assertSameValue($workerId, (int)$reader->query("SELECT IS_USED_LOCK('{$lock}')")->fetch_column(), 'lock remains held at every emitted phase');
                $expected = match ($phase) {
                    ObjectDetailSnapshotSchemaPhase::LOCK_ACQUIRED => [],
                    ObjectDetailSnapshotSchemaPhase::DETAILS_CREATED => [$prefix.'fm2_pilot_object_details'],
                    ObjectDetailSnapshotSchemaPhase::QUARANTINE_CREATED => [$prefix.'fm2_pilot_object_detail_quarantine', $prefix.'fm2_pilot_object_details'],
                };
                assertSameValue($expected, $tables($prefix), 'phase observes exactly the real durable CREATE boundary');
            } catch (Throwable $error) { $probeFailure = $error; throw $error; }
            if ($scenario === 'first' && $phase === ObjectDetailSnapshotSchemaPhase::DETAILS_CREATED) throw new RuntimeException('test observer interruption');
            if ($scenario === 'closed' && $phase === ObjectDetailSnapshotSchemaPhase::QUARANTINE_CREATED) {
                $worker->close();
                $workerClosed = true;
            }
        };
        try {
            $unavailable = false;
            $result = null;
            try { $result = ObjectDetailSnapshotSchemaMigrationVerification::apply($worker, $prefix, $observer($callback)); }
            catch (DatabaseUnavailable) { $unavailable = true; }
            if ($probeFailure !== null) throw $probeFailure;
            $expectedEvents = $scenario === 'first' ? ['lock_acquired','details_created'] : ['lock_acquired','details_created','quarantine_created'];
            assertSameValue($expectedEvents, $events, 'exact ordered phase transcript');
            assertSameValue($scenario !== 'normal', $unavailable, 'interruption must not report successful migration');
            if ($scenario === 'normal') assertSameValue(['applied'=>true,'schemaVersion'=>12,'tablesCreated'=>[$prefix.'fm2_pilot_object_detail_quarantine',$prefix.'fm2_pilot_object_details']], $result, 'normal observed result');
            $expectedTables = $scenario === 'first' ? [$prefix.'fm2_pilot_object_details'] : [$prefix.'fm2_pilot_object_detail_quarantine',$prefix.'fm2_pilot_object_details'];
            assertSameValue($expectedTables, $tables($prefix), 'durable schema remains after outcome');
            $deadline = hrtime(true) + 2_000_000_000;
            do {
                $free = (int)$reader->query("SELECT IS_FREE_LOCK('{$lock}')")->fetch_column();
                if ($free === 1) break;
                usleep(1000);
            } while (hrtime(true) < $deadline);
            assertSameValue(1, $free, 'normal/error/closed-connection outcome releases the lock');
            if ($scenario === 'first') $reader->query("INSERT INTO first_fm2_pilot_object_details VALUES(7001,'fixture-v1',REPEAT('a',64),'{\"fixture\":true}','2026-09-05T09:00:00Z')");
            $beforeRows = $reader->query("SELECT * FROM `{$prefix}fm2_pilot_object_details` ORDER BY object_id")->fetch_all(MYSQLI_ASSOC);
            $fresh = $connect($database);
            try {
                assertSameValue(['applied'=>$scenario === 'first','schemaVersion'=>12,'tablesCreated'=>$scenario === 'first' ? ['first_fm2_pilot_object_detail_quarantine'] : []], ObjectDetailSnapshotSchemaMigration::apply($fresh, $prefix), 'ordinary fresh-connection retry resolves durable state');
            } finally { $fresh->close(); }
            assertSameValue($beforeRows, $reader->query("SELECT * FROM `{$prefix}fm2_pilot_object_details` ORDER BY object_id")->fetch_all(MYSQLI_ASSOC), 'retry preserves exact existing rows');
        } finally { if (!$workerClosed) $worker->close(); }
    }
    echo "PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 observer phases and interruption recovery\n";
} finally {
    try { if ($reader instanceof mysqli) $reader->close(); }
    finally {
        try { if ($created) $admin->query("DROP DATABASE `{$database}`"); }
        finally { $admin->close(); }
    }
}
