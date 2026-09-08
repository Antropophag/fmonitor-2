<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigrationVerification as Verification;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;

// ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 v0.1, approved Gate1 hash
// 31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f.
// Observations: public migration/verification API and standard MariaDB catalog.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
    getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
    getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
    null, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
$db->set_charset('utf8mb4');
$name = 't_aoir_' . bin2hex(random_bytes(6));
$db->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->select_db($name);
$status = 0;

/** Literal historical source, not a target command implementation. */
function aoirSourceOrder(mysqli $db, string $prefix, int $id, int $case, int $version, string $at): void
{
    $sql = "INSERT INTO `{$prefix}fm2_assignment_orders` (id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id) VALUES (?,?,?,'initial','prepared','2026-08-27',73,'Инженер теста','Инженер','individual','Адрес теста','2','77-000123','2026-10-05','2026-12-20',?,18)";
    $statement = $db->prepare($sql);
    $statement->bind_param('iiis', $id, $case, $version, $at);
    $statement->execute();
    $statement->close();
}

function aoirSourceSnapshot(mysqli $db, string $prefix): array
{
    $result = [];
    foreach (['fm2_installation_cases', 'fm2_assignment_orders', 'fm2_order_installers',
        'fm2_order_artifacts', 'fm2_process_tasks', 'fm2_process_events'] as $base) {
        $table = $prefix . $base;
        $rows = $db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);
        usort($rows, static fn (array $a, array $b): int => strcmp(json_encode($a), json_encode($b)));
        $statement = $db->prepare('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $statement->bind_param('s', $table);
        $statement->execute();
        $result[$base] = [$rows, $statement->get_result()->fetch_assoc(), $db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1]];
        $statement->close();
    }
    return $result;
}

try {
    // Setup occurs before the missing-seam assertion, proving real DB fixtures work.
    ProductionProcessSchemaMigration::apply($db);
    $db->query("INSERT INTO fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,4512,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1),(4513,4513,'needs_assignment_order','2026-08-20T09:00:00+03:00','2026-08-20T09:00:00+03:00',1)");
    aoirSourceOrder($db, '', 7, 4513, 3, '2026-08-28T10:15:00Z');
    aoirSourceOrder($db, '', 2, 4512, 1, '2026-08-27T12:30:00+03:00');
    $db->query('ALTER TABLE fm2_assignment_orders AUTO_INCREMENT=81');
    $db->query("CREATE TABLE unrelated_marker (id INT PRIMARY KEY, value VARCHAR(40) NOT NULL)");
    $db->query("INSERT INTO unrelated_marker VALUES (1,'preserve-foreign-prefix')");
    $before = aoirSourceSnapshot($db, '');
    assertSameValue(true, class_exists(Migration::class) && is_callable([Migration::class, 'apply']),
        'RED_ASSERTION: public assignment-order identity registry migration is missing');
    assertSameValue(false, Migration::isBackfillComplete($db), 'absent registry is not complete');
    assertSameValue(['applied' => true], Migration::apply($db), 'new metadata family/backfill applied');
    $snapshot = Verification::snapshot($db, '');
    assertSameValue([
        ['2', '4512', 1, 'legacy_order', '2026-08-27T09:30:00.000000Z'],
        ['7', '4513', 3, 'legacy_order', '2026-08-28T10:15:00.000000Z'],
    ], array_map(static fn ($row): array => [$row->id, $row->caseId, $row->version,
        $row->sourceKind, $row->allocatedAtUtc], $snapshot->identities), 'fixed historical identities/UTC instants');
    assertSameValue('81', $snapshot->nextId, 'preserve original AUTO_INCREMENT gaps');
    $receipt = $snapshot->receipt;
    assertSameValue([1, '7', '81', '81', '2',
        '8159e7f3e55b317c01056ec6c7172e2c9bca8a798c0bc38408b6b6df1d71be86',
        'a5506e2a71f414d4667cc95d1446155f69d9156ecf87e51bf9d7ec485997be53'],
        [$receipt->formatVersion, $receipt->legacyMaxId, $receipt->legacyNextId,
            $receipt->preservedNextId, $receipt->legacyRowCount, $receipt->tupleSha256,
            $receipt->preparedSha256], 'independently fixed immutable receipt');
    assertSameValue($before, aoirSourceSnapshot($db, ''), 'all source rows/catalog/counters preserved');
    assertSameValue(true, Migration::isBackfillComplete($db), 'public backfill completeness');
    assertSameValue(['applied' => false], Migration::apply($db), 'complete repeat is a no-op');
    assertSameValue(serialize($snapshot), serialize(Verification::snapshot($db, '')), 'repeat preserves full metadata snapshot');
    assertSameValue($before, aoirSourceSnapshot($db, ''), 'repeat preserves source');

    // Simulated already-compatible future writer state; this does not implement it.
    aoirSourceOrder($db, '', 81, 4512, 2, '2026-09-05T10:00:00Z');
    $db->query("INSERT INTO fm2_assignment_order_identities (assignment_order_id,installation_case_id,order_version,source_kind,allocated_at_utc) VALUES (81,4512,2,'legacy_order','2026-09-05 10:00:00.000000')");
    $late = Verification::snapshot($db, '');
    $lateSource = aoirSourceSnapshot($db, '');
    assertSameValue(true, Migration::isBackfillComplete($db), 'later identities do not invalidate frozen subset');
    assertSameValue(['applied' => false], Migration::apply($db), 'later legitimate row is preserved on repeat');
    assertSameValue(serialize($receipt), serialize(Verification::snapshot($db, '')->receipt), 'receipt never rewritten for later rows');
    assertSameValue(serialize($late), serialize(Verification::snapshot($db, '')), 'later metadata stays unchanged');
    assertSameValue($lateSource, aoirSourceSnapshot($db, ''), 'later source/counter preserved');
    assertSameValue([['id' => '1', 'value' => 'preserve-foreign-prefix']],
        $db->query('SELECT * FROM unrelated_marker')->fetch_all(MYSQLI_ASSOC), 'unrelated marker preserved');
    assertSameValue('0', (string) $db->query('SELECT @@in_transaction value')->fetch_assoc()['value'], 'engine leaves no caller transaction');
    echo "ASSIGNMENT_ORDER_IDENTITY_REGISTRY_001_OK\n";
} catch (TestFailure $failure) {
    fwrite(STDERR, $failure->getMessage() . "\n");
    $status = 1;
} finally {
    $db->query("DROP DATABASE `$name`");
    $statement = $db->prepare('SELECT COUNT(*) n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
    $statement->bind_param('s', $name);
    $statement->execute();
    assertSameValue('0', (string) $statement->get_result()->fetch_assoc()['n'], 'owned fixture database removed');
    $statement->close();
    $db->close();
}
exit($status);
