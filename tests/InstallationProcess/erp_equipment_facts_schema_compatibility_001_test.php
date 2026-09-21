<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/EquipmentFactsFixture.php';

use FMonitor2\InstallationProcess\EquipmentFactsSchemaMigration;

function expectEquipmentSchemaConflict(EquipmentFactsFixture $fixture, string $label): void
{
    try {
        EquipmentFactsSchemaMigration::apply($fixture->db, $fixture->p);
        throw new TestFailure('incompatible schema accepted: ' . $label);
    } catch (RuntimeException $error) {
        assertSameValue('SCHEMA_MIGRATION_CONFLICT', $error->getMessage(), $label);
    }
}

$fixture = new EquipmentFactsFixture('schema_compatibility');
try {
    EquipmentFactsSchemaMigration::apply($fixture->db, $fixture->p);

    $fixture->db->query("ALTER TABLE `{$fixture->p}fm2_equipment_fact_current` ADD CONSTRAINT `fk_unexpected_equipment_object` FOREIGN KEY (`object_id`) REFERENCES `{$fixture->p}fm_maintable` (`id`)");
    expectEquipmentSchemaConflict($fixture, 'unexpected foreign key rejected');
    $fixture->db->query("ALTER TABLE `{$fixture->p}fm2_equipment_fact_current` DROP FOREIGN KEY `fk_unexpected_equipment_object`");

    $fixture->db->query("CREATE TRIGGER `{$fixture->p}ef_unexpected_diagnostic_insert` BEFORE INSERT ON `{$fixture->p}fm2_equipment_fact_diagnostics` FOR EACH ROW SET NEW.reason=NEW.reason");
    expectEquipmentSchemaConflict($fixture, 'unexpected non-history trigger rejected');
    $fixture->db->query("DROP TRIGGER `{$fixture->p}ef_unexpected_diagnostic_insert`");

    echo "PASS: ERP-EQUIPMENT-FACTS-001 exact schema compatibility\n";
} finally {
    $fixture->close();
}
