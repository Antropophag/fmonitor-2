<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration as Registry;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigrationVerification as RegistryView;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigration as Selection;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigrationVerification as SelectionView;
use FMonitor2\Tests\Support\IdentityRegistryTestDatabase;

// ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 v0.2, Gate1 63759ba4e889c0a3940ea15f60e4f747f48ce26c39165fe9dc67984682fe5bb1.
// First clean/repeat public-seam tranche; the remaining mandatory matrix is separate.
$fixture = null; $admin = null; $decoy = null; $decoyCreated = false; $decoyBefore = null; $errors = [];
try {
    $fixture = new IdentityRegistryTestDatabase();
    $db = $fixture->connection;
    $db->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $admin = $fixture->connect();
    $decoy = $fixture->name . '_decoy';
    $admin->query("CREATE DATABASE `$decoy`"); $decoyCreated = true;
    $admin->query("CREATE TABLE `$decoy`.marker (id INT PRIMARY KEY, value VARCHAR(30))");
    $admin->query("INSERT INTO `$decoy`.marker VALUES(1,'external-owned-marker')");
    $decoyBefore = $admin->query("SELECT * FROM `$decoy`.marker")->fetch_all(MYSQLI_NUM);

    assertSameValue(['applied' => true], Registry::apply($db), 'real approved registry setup');
    assertSameValue(true, Registry::isBackfillComplete($db), 'registry prerequisite proven before target action');
    $registry = RegistryView::snapshot($db, '');
    assertSameValue([], $registry->identities, 'empty source has no invented identity');
    assertSameValue(['0','81','81','0',
        'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'],
        [$registry->receipt->legacyMaxId, $registry->receipt->legacyNextId,
            $registry->receipt->preservedNextId, $registry->receipt->legacyRowCount,
            $registry->receipt->tupleSha256, $registry->receipt->preparedSha256], 'literal empty receipt');
    $before = $fixture->allState();
    echo "SELECTION_SCHEMA_REGISTRY_SETUP_OK\n";

    assertSameValue(true, is_callable([Selection::class, 'apply']),
        'RED_ASSERTION: public assignment-order selection schema migration is missing');
    assertSameValue(false, Selection::isReady($db), 'absent selection family is not ready');
    assertSameValue(['applied' => true], Selection::apply($db), 'clean family creation');
    assertSameValue(true, Selection::isReady($db), 'complete empty family ready');
    $snapshot = SelectionView::snapshot($db, '');
    assertSameValue('514247fd114cecd991da76aa4aa5b86c2e33164cce3fd085f64bbd38903b2bcb',
        $snapshot->schemaSha256, 'independent empty-prefix family fingerprint');
    $expected = [
        ['fm2_assignment_order_selections','3d82bb6b567c380a142205c7c6ec1bb76a7d1ebd2ff3da33f6ef2ba3c7bbdc6c',null],
        ['fm2_assignment_order_selection_members','27c7abcf2091b421fbaee25118b608cc8ee67a48b85bc0c43b27a4c287bc9309',null],
        ['fm2_assignment_order_selection_requests','54e72dfee77802b7337c18d8dc02b9e0bad21cbdf16e6f336d1c37fbc6b3bcaa',null],
        ['fm2_assignment_order_selection_events','af0934ead1010273d76c339372b5b826a4e7a31f7049a7beb674c6c905ad88eb','1'],
        ['fm2_assignment_order_selection_audits','800005376cb7c1cd062c5a500bbb65e0fa5ccee8f446a5130bfa036ec7ef16dd','1'],
    ];
    assertSameValue($expected, array_map(static fn ($t) => [$t->tableName,$t->shapeSha256,$t->nextId],
        $snapshot->tables), 'exact creation-order shapes and initial counters');
    foreach ($snapshot->tables as $table) {
        assertSameValue(['0','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'],
            [$table->rowCount,$table->rowsSha256], 'no domain rows created');
    }
    $after = $fixture->allState();
    assertSameValue($before, array_intersect_key($after, $before), 'registry/source/decoy rows and DDL/counters preserved');
    $added = array_values(array_diff(array_keys($after), array_keys($before)));
    $names = array_column($expected, 0); sort($names); sort($added);
    assertSameValue($names, $added, 'exactly five tables created');
    assertSameValue(['applied' => false], Selection::apply($db), 'complete repeat is no-op');
    assertSameValue(serialize($snapshot), serialize(SelectionView::snapshot($db, '')), 'repeat snapshot unchanged');
    assertSameValue($after, $fixture->allState(), 'repeat preserves all catalog/rows/counters');
    assertSameValue(serialize($registry), serialize(RegistryView::snapshot($db, '')), 'registry snapshot unchanged');
    assertSameValue('0', (string)$db->query('SELECT @@in_transaction n')->fetch_assoc()['n'], 'no leaked transaction');
    echo "ASSIGNMENT_ORDER_SELECTION_SCHEMA_001_OK\n";
} catch (Throwable $error) {
    $errors[] = $error->getMessage();
} finally {
    // Inner fixture owns its exact database only. Every cleanup is attempted even after RED.
    if ($fixture !== null) {
        try { $fixture->close(); } catch (Throwable $error) { $errors[] = $error->getMessage(); }
    }
    if ($admin !== null && $decoyCreated) {
        try {
            assertSameValue($decoyBefore, $admin->query("SELECT * FROM `$decoy`.marker")->fetch_all(MYSQLI_NUM),
                'external decoy survives target invocation and inner fixture cleanup');
            $q = $admin->prepare('SELECT COUNT(*) n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
            $ownedName = $fixture->name; $q->bind_param('s', $ownedName); $q->execute();
            assertSameValue('0', (string)$q->get_result()->fetch_assoc()['n'], 'exact owned schema removed');
            $q->close();
            echo "SELECTION_SCHEMA_OWNED_CLEANUP_AND_EXTERNAL_DECOY_OK\n";
        } catch (Throwable $error) { $errors[] = $error->getMessage(); }
        try { $admin->query("DROP DATABASE `$decoy`"); } catch (Throwable $error) { $errors[] = $error->getMessage(); }
    }
    if ($admin !== null) {
        try { $admin->close(); } catch (Throwable $error) { $errors[] = $error->getMessage(); }
    }
}
foreach ($errors as $message) { fwrite(STDERR, $message . "\n"); }
exit($errors === [] ? 0 : 1);
