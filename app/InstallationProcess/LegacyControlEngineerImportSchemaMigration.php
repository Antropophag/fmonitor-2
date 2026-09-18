<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class LegacyControlEngineerImportSchemaMigration
{
    public static function apply(\mysqli $db, string $prefix): array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);
        $table = $prefix . 'fm2_pilot_users';
        $row = $db->query("SHOW FULL COLUMNS FROM `{$table}` LIKE 'activation_state'")->fetch_assoc();
        if ($row === null) throw new \DomainException('SCHEMA_MIGRATION_CONFLICT');
        $current = "enum('invited','active','blocked')";
        $complete = "enum('pending_invitation','invited','active','blocked')";
        if ($row['Type'] === $complete) return ['applied'=>false,'schemaVersion'=>30,'tablesCreated'=>[]];
        if ($row['Type'] !== $current) throw new \DomainException('SCHEMA_MIGRATION_CONFLICT');
        $db->query("ALTER TABLE `{$table}` MODIFY activation_state ENUM('pending_invitation','invited','active','blocked') NOT NULL");
        return ['applied'=>true,'schemaVersion'=>30,'tablesCreated'=>[],'tablesUpgraded'=>[$table]];
    }
}
