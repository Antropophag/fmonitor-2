<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigrationVerification as Verification;
use FMonitor2\Tests\Support\SelectionSchemaAssertions as Check;
use FMonitor2\Tests\Support\SelectionSchemaTestDatabase as Fixture;

// ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 v0.2 §§3–7. No production constants as oracle.
foreach (['', str_repeat('p', 25)] as $prefix) {
    Check::run('native normative example control prefix' . strlen($prefix), static function (Fixture $f): void {
        $f->create(); $f->populate();
        foreach ($f->manifest as $i => $table) {
            $columns = array_column($table['columns'], 'name');
            $primary = array_values(array_filter($table['indexes'], static fn ($key) => $key['name'] === 'PRIMARY'))[0]['columns'];
            $rows = $f->db->query('SELECT * FROM `' . $f->name($i) . '` ORDER BY `' . implode('`,`', $primary) . '`')->fetch_all(MYSQLI_ASSOC);
            $bytes = '';
            foreach ($rows as $row) {
                $cells = array_map(static fn ($key) => $row[$key] === null ? null : (string)$row[$key], $columns);
                $bytes .= json_encode($cells, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
            }
            $base = str_replace('@prefix', '', $table['name']);
            assertSameValue([$f->example['expected'][$base]['rowCount'], $f->example['expected'][$base]['rowsSha256']],
                [(string)count($rows), hash('sha256', $bytes)], 'independent normative example survives native SQL storage');
        }
    }, $prefix);
    Check::run('populated preservation prefix' . strlen($prefix), static function (Fixture $f): void {
        $f->create(); $f->populate(); $before = $f->state(); Check::missing();
        assertSameValue(['applied' => false], Migration::apply($f->db, $f->prefix), 'populated complete repeat');
        assertSameValue(true, Migration::isReady($f->db, $f->prefix), 'both modes coherent');
        $snapshot = Verification::snapshot($f->db, $f->prefix);
        assertSameValue($f->prefix === '' ? '514247fd114cecd991da76aa4aa5b86c2e33164cce3fd085f64bbd38903b2bcb'
            : '36b6198ef1a3f704786c411cdc629b8b3c586037f19eab1b5cdfafc88645836f', $snapshot->schemaSha256, 'literal prefix family hash');
        $expected = []; foreach ($f->example['expected'] as $base => $values) { $expected[$f->prefix . $base] = $values; }
        $actual = []; foreach ($snapshot->tables as $table) {
            $actual[$table->tableName] = ['rowCount' => $table->rowCount, 'rowsSha256' => $table->rowsSha256, 'nextId' => $table->nextId];
        }
        assertSameValue($expected, $actual, 'exact populated rows and preserved counter gaps');
        assertSameValue($before, $f->state(), 'all native tables and counters byte-preserved');
    }, $prefix);
}

for ($count = 0; $count < 5; $count++) {
    Check::run('empty leading prefix ' . $count, static function (Fixture $f) use ($count): void {
        $f->create($count); $before = $f->state(); Check::missing();
        assertSameValue(false, Migration::isReady($f->db), 'partial absent family not ready');
        assertSameValue(['applied' => true], Migration::apply($f->db), 'only missing suffix created');
        assertSameValue(true, Migration::isReady($f->db), 'recovered complete family');
        assertSameValue($before, array_intersect_key($f->state(), $before), 'existing leading prefix and registry unchanged');
        assertSameValue('514247fd114cecd991da76aa4aa5b86c2e33164cce3fd085f64bbd38903b2bcb', Verification::snapshot($f->db, '')->schemaSha256, 'exact recovered family');
    });
}
Check::run('gap', static function (Fixture $f): void { $f->create(1); $f->createTable(2); Check::conflict($f); });
Check::run('nonempty partial', static function (Fixture $f): void { $f->create(1); $f->populate(1); Check::conflict($f); });
Check::run('registry selection without family', static function (Fixture $f): void { $f->populate(0); Check::conflict($f); });
foreach (['missing' => 'DROP TABLE fm2_assignment_order_id_receipts',
    'incomplete' => 'DELETE FROM fm2_assignment_order_id_receipts',
    'incompatible' => 'ALTER TABLE fm2_assignment_order_id_receipts ADD COLUMN extra INT NULL'] as $label => $sql) {
    Check::run('registry ' . $label, static function (Fixture $f) use ($sql): void { $f->db->query($sql); Check::conflict($f); });
}
foreach ([
    'column' => 'ALTER TABLE fm2_assignment_order_selections ADD COLUMN extra INT NULL',
    'index' => 'ALTER TABLE fm2_assignment_order_selections ADD INDEX extra(mode)',
    'check literal' => "ALTER TABLE fm2_assignment_order_selection_events DROP CONSTRAINT fm2_aose_ck_type, ADD CONSTRAINT fm2_aose_ck_type CHECK(event_type='ASSIGNMENT_ORDER_COMPOSITION_SELECTED')",
    'check grouping' => "ALTER TABLE fm2_assignment_order_selections DROP CONSTRAINT fm2_aos_ck_replaces, ADD CONSTRAINT fm2_aos_ck_replaces CHECK(mode='new_order' AND (replaces_selection_order_id IS NULL OR mode='replace_pending') AND replaces_selection_order_id IS NOT NULL)",
    'FK action' => 'ALTER TABLE fm2_assignment_order_selection_members DROP FOREIGN KEY fm2_aosm_fk_selection, ADD CONSTRAINT fm2_aosm_fk_selection FOREIGN KEY(assignment_order_id) REFERENCES fm2_assignment_order_selections(assignment_order_id) ON DELETE CASCADE ON UPDATE RESTRICT',
] as $label => $sql) {
    Check::run('metadata drift ' . $label, static function (Fixture $f) use ($sql): void { $f->create(); $f->db->query($sql); Check::conflict($f); });
}
foreach ([
    'composition' => "UPDATE fm2_assignment_order_selections SET composition_sha256=REPEAT('a',64) WHERE assignment_order_id=81",
    'request' => "UPDATE fm2_assignment_order_selection_requests SET operation_fingerprint=REPEAT('a',64) WHERE request_id='00000000-0000-4000-8000-000000000001'",
    'event' => 'UPDATE fm2_assignment_order_selection_events SET actor_user_id=19 WHERE event_id=1',
    'audit' => 'UPDATE fm2_assignment_order_selection_audits SET actor_user_id=19 WHERE audit_id=1',
    'registry echo' => "UPDATE fm2_assignment_order_identities SET allocated_at_utc='2026-09-02 07:00:01.000000' WHERE assignment_order_id=81",
    'member trim' => "UPDATE fm2_assignment_order_selection_members SET fio_snapshot=CONCAT(CHAR(9),'bad') WHERE assignment_order_id=81",
    'selected seconds' => "UPDATE fm2_assignment_order_selections SET selected_at_utc='2026-09-02 07:00:00.123456' WHERE assignment_order_id=81",
    'revision chain' => 'UPDATE fm2_assignment_order_selections SET selection_revision=3 WHERE assignment_order_id=82',
    'missing backing audit' => 'DELETE FROM fm2_assignment_order_selection_audits WHERE audit_id=1',
    'extra terminal backing' => "INSERT INTO fm2_assignment_order_selection_audits(request_id,actor_user_id,installation_object_id,mode,status,reason_code,attempted_at_utc) SELECT request_id,actor_user_id,installation_object_id,mode,status,reason_code,attempted_at_utc FROM fm2_assignment_order_selection_audits WHERE audit_id=1",
] as $label => $sql) {
    Check::run('populated corrupt ' . $label, static function (Fixture $f) use ($sql): void {
        $f->create(); $f->populate(); $f->db->query($sql); Check::conflict($f);
    });
}
foreach ([3, 4] as $index) {
    foreach (['9223372036854775807', '9223372036854775808', '9223372036854775809'] as $next) {
        Check::run('counter ' . $index . ' next ' . $next, static function (Fixture $f) use ($index, $next): void {
            $f->create(); $f->db->query('ALTER TABLE `' . $f->name($index) . '` AUTO_INCREMENT=' . $next);
            if ($next === '9223372036854775809') { Check::conflict($f); return; }
            $before = $f->state(); Check::missing();
            assertSameValue(['applied' => false], Migration::apply($f->db), 'valid/exhausted counter accepted without repair');
            assertSameValue($next, Verification::snapshot($f->db, '')->tables[$index]->nextId, 'lossless sentinel');
            assertSameValue($before, $f->state(), 'counter not reset');
        });
    }
    Check::run('last valid generated row ' . $index, static function (Fixture $f) use ($index): void {
        $f->create(); $f->populate(); $id = $index === 3 ? 'event_id' : 'audit_id';
        $f->db->query('UPDATE `' . $f->name($index) . '` SET ' . $id . '=9223372036854775807 WHERE ' . $id . '=1');
        $before = $f->state(); Check::missing();
        assertSameValue(['applied' => false], Migration::apply($f->db), 'last valid row retained');
        assertSameValue('9223372036854775808', Verification::snapshot($f->db, '')->tables[$index]->nextId, 'exhausted following last valid row');
        assertSameValue($before, $f->state(), 'no renumbering of history');
    });
}
Check::finish();
