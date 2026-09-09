<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Read-only startup prerequisite for the temporary pilot legacy-object adapter. */
final class MariaDbPilotLegacyObjectSchemaReadiness
{
    public static function assertReady(\mysqli $connection, string $prefix): void
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);
        if (!self::hasSupportedStorage($connection, $prefix)) throw new DatabaseUnavailable('Pilot legacy object storage is unavailable.');
        $table = $connection->real_escape_string($prefix . 'fm_maintable');
        $rows = $connection->query(
            "SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS "
            . "WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION",
        )->fetch_all(MYSQLI_ASSOC);
        $actual = array_map(static fn(array $row): string => implode('|', $row), $rows);
        $expected = self::expectedColumns();
        if ($actual !== $expected) throw new DatabaseUnavailable('Pilot legacy object schema is unavailable.');
    }

    /** Exact supported predecessor, validated before any additive ALTER. */
    public static function isKnownPredecessor(\mysqli $connection, string $prefix): bool
    {
        if (!self::hasSupportedStorage($connection, $prefix)) return false;
        $added = ['workdatestartadjusted', 'floors', 'weight', 'speed', 'pittype', 'pitmaterial', 'paired'];
        $expected = array_values(array_filter(self::expectedColumns(),
            static fn(string $column): bool => !in_array(explode('|', $column)[0], $added, true)));
        $columns = MariaDbSchemaInspector::columns($connection, $prefix . 'fm_maintable');
        $actual = array_map(static fn(array $column): string => implode('|', [
            $column['COLUMN_NAME'], $column['COLUMN_TYPE'], $column['IS_NULLABLE'],
        ]), $columns);
        return $actual === $expected;
    }

    private static function hasSupportedStorage(\mysqli $connection, string $prefix): bool
    {
        $table = $prefix . 'fm_maintable';
        $properties = MariaDbSchemaInspector::tableProperties($connection, $table);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB'
            || !str_starts_with((string) $properties['TABLE_COLLATION'], 'utf8mb4_')) return false;
        foreach (MariaDbSchemaInspector::columns($connection, $table) as $column) {
            if (str_starts_with(strtolower((string) $column['COLUMN_TYPE']), 'varchar(')
                && $column['CHARACTER_SET_NAME'] !== 'utf8mb4') return false;
        }
        $primary = null;
        foreach (MariaDbSchemaInspector::indexes($connection, $table) as $index) {
            if ($index['INDEX_NAME'] === 'PRIMARY') $primary = $index['COLUMNS'];
            elseif ((int) $index['NON_UNIQUE'] === 0) return false;
        }
        return $primary === 'id:FULL:A:NO';
    }

    private static function expectedColumns(): array
    {
        return [
            'id|bigint(20) unsigned|NO', 'ordadr_address|varchar(500)|YES',
            'entrance|varchar(80)|YES', 'regnumber|varchar(120)|YES',
            'workdatestart|varchar(40)|YES', 'workdatestartadjusted|varchar(40)|YES', 'workdateendadjusted|varchar(40)|YES',
            'plan_finish_date|varchar(40)|YES', 'workdatefinish|varchar(40)|YES',
            'ptoactdate|varchar(40)|YES', 'responsstroicontrol|varchar(80)|YES',
            'floors|varchar(40)|YES','weight|varchar(40)|YES','speed|varchar(40)|YES',
            'pittype|varchar(40)|YES','pitmaterial|varchar(40)|YES','paired|varchar(40)|YES',
        ];
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
