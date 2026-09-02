<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Read-only startup prerequisite for the temporary pilot legacy-object adapter. */
final class MariaDbPilotLegacyObjectSchemaReadiness
{
    public static function assertReady(\mysqli $connection, string $prefix): void
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);
        $table = $connection->real_escape_string($prefix . 'fm_maintable');
        $rows = $connection->query(
            "SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS "
            . "WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION",
        )->fetch_all(MYSQLI_ASSOC);
        $actual = array_map(static fn(array $row): string => implode('|', $row), $rows);
        $expected = [
            'id|bigint(20) unsigned|NO', 'ordadr_address|varchar(500)|YES',
            'entrance|varchar(80)|YES', 'regnumber|varchar(120)|YES',
            'workdatestart|varchar(40)|YES', 'workdateendadjusted|varchar(40)|YES',
            'plan_finish_date|varchar(40)|YES', 'workdatefinish|varchar(40)|YES',
            'ptoactdate|varchar(40)|YES', 'responsstroicontrol|varchar(80)|YES',
        ];
        if ($actual !== $expected) throw new DatabaseUnavailable('Pilot legacy object schema is unavailable.');
    }

    public static function assertGenerationSentinelReady(\mysqli $connection, string $prefix): void
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);
        $table = $connection->real_escape_string($prefix . 'fm2_pilot_generation_sentinel');
        $columns = $connection->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $actual = array_map(static fn(array $row): string => implode('|', $row), $columns);
        if ($actual !== ['singleton_id|tinyint(3) unsigned|NO','generation|int(10) unsigned|NO','fingerprint|char(8)|NO','manifest_nonce|char(64)|NO']) {
            throw new DatabaseUnavailable('Pilot generation sentinel schema is unavailable.');
        }
        $primary = (int) $connection->query("SELECT COUNT(*) n FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND INDEX_NAME='PRIMARY' AND NON_UNIQUE=0 AND SEQ_IN_INDEX=1 AND COLUMN_NAME='singleton_id'")->fetch_assoc()['n'];
        if ($primary !== 1) throw new DatabaseUnavailable('Pilot generation sentinel key is unavailable.');
    }

    public static function assertOtizReady(\mysqli $connection, string $prefix): void
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);
        $tables = ['fm2_pilot_otiz_snapshots','fm2_pilot_otiz_snapshot_objects','fm2_pilot_otiz_snapshot_allocations','fm2_pilot_otiz_snapshot_issues','fm2_pilot_otiz_snapshot_evidence','fm2_pilot_otiz_payment_closures','fm2_pilot_otiz_events'];
        foreach ($tables as $name) if (!MariaDbSchemaInspector::tableExists($connection, $prefix . $name)) {
            throw new DatabaseUnavailable('Pilot OTIZ schema is unavailable.');
        }
        $table = $connection->real_escape_string($prefix . 'fm2_pilot_otiz_payment_closures');
        $index = (int) $connection->query("SELECT COUNT(*) n FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND INDEX_NAME='unique_reversal' AND NON_UNIQUE=0 AND SEQ_IN_INDEX=1 AND COLUMN_NAME='reverses_payment_closure_id'")->fetch_assoc()['n'];
        if ($index !== 1) throw new DatabaseUnavailable('Pilot OTIZ payment schema is unavailable.');
    }
}
