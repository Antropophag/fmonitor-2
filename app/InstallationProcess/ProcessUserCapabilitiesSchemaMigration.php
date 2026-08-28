<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProcessUserCapabilitiesSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        MariaDbSchemaInspector::validateTablePrefix($tablePrefix);
        $table = $tablePrefix . 'fm2_process_user_capabilities';
        if (MariaDbSchemaInspector::tableExists($connection, $table)) {
            if (!self::isCompatible($connection, $table)) {
                return [
                    'applied' => false,
                    'schemaVersion' => 3,
                    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
                    'conflictingTables' => [$table],
                ];
            }

            return ['applied' => false, 'schemaVersion' => 3, 'tablesCreated' => []];
        }

        $connection->query("CREATE TABLE `{$table}` (
            user_id BIGINT UNSIGNED NOT NULL,
            capability VARCHAR(80) NOT NULL,
            position_snapshot VARCHAR(300) NULL,
            PRIMARY KEY (user_id, capability),
            KEY (capability, user_id),
            CONSTRAINT ck_fm2_process_user_capability CHECK (capability IN ('assignment_order.prepare', 'construction_control_engineer')),
            CONSTRAINT ck_fm2_process_user_engineer_position CHECK (capability <> 'construction_control_engineer' OR ((position_snapshot IS NOT NULL AND TRIM(position_snapshot) <> '')))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        return ['applied' => true, 'schemaVersion' => 3, 'tablesCreated' => [$table]];
    }

    private static function isCompatible(\mysqli $connection, string $table): bool
    {
        $properties = MariaDbSchemaInspector::tableProperties($connection, $table);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB' || !str_starts_with((string) $properties['TABLE_COLLATION'], 'utf8mb4_')) {
            return false;
        }

        $columns = array_map(static fn (array $column): array => [
            'COLUMN_NAME' => $column['COLUMN_NAME'],
            'COLUMN_TYPE' => $column['COLUMN_TYPE'],
            'IS_NULLABLE' => $column['IS_NULLABLE'],
            'EXTRA' => $column['EXTRA'],
            'CHARACTER_SET_NAME' => $column['CHARACTER_SET_NAME'],
        ], MariaDbSchemaInspector::columns($connection, $table));
        if ($columns !== [
            ['COLUMN_NAME'=>'user_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
            ['COLUMN_NAME'=>'capability','COLUMN_TYPE'=>'varchar(80)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
            ['COLUMN_NAME'=>'position_snapshot','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'YES','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ]) {
            return false;
        }

        $indexes = array_map(static fn (array $index): array => [
            'TYPE' => $index['INDEX_NAME'] === 'PRIMARY' ? 'PRIMARY' : ((int) $index['NON_UNIQUE'] === 0 ? 'UNIQUE' : 'INDEX'),
            'COLUMNS' => $index['COLUMNS'],
        ], MariaDbSchemaInspector::indexes($connection, $table));
        usort($indexes, static fn (array $left, array $right): int => [$left['TYPE'], $left['COLUMNS']] <=> [$right['TYPE'], $right['COLUMNS']]);
        if ($indexes !== [
            ['TYPE'=>'INDEX','COLUMNS'=>'capability:FULL:A:NO,user_id:FULL:A:NO'],
            ['TYPE'=>'PRIMARY','COLUMNS'=>'user_id:FULL:A:NO,capability:FULL:A:NO'],
        ]) {
            return false;
        }

        if (ProcessCapabilityChecksClassifier::inspect($connection, $table) === null) {
            return false;
        }

        return MariaDbSchemaInspector::foreignKeys($connection, $table) === [];
    }
}
