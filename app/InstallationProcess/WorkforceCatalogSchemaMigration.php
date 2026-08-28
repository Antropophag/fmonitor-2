<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class WorkforceCatalogSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        MariaDbSchemaInspector::validateTablePrefix($tablePrefix);

        $table = $tablePrefix . 'fm2_workforce_catalog';
        if (MariaDbSchemaInspector::tableExists($connection, $table)) {
            if (!self::isCompatible($connection, $table)) {
                return [
                    'applied' => false,
                    'schemaVersion' => 2,
                    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
                    'conflictingTables' => [$table],
                ];
            }

            return ['applied' => false, 'schemaVersion' => 2, 'tablesCreated' => []];
        }

        $connection->query("CREATE TABLE `{$table}` (
            installer_tab_id BIGINT UNSIGNED NOT NULL,
            fio VARCHAR(300) NOT NULL,
            position VARCHAR(300) NOT NULL,
            employment_status VARCHAR(40) NOT NULL,
            employed_from DATE NULL,
            employed_to DATE NULL,
            workforce_source VARCHAR(80) NOT NULL,
            workforce_source_updated_at VARCHAR(40) NOT NULL,
            PRIMARY KEY (installer_tab_id),
            KEY (employment_status, employed_to),
            CHECK (employment_status IN ('employed', 'dismissed'))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return ['applied' => true, 'schemaVersion' => 2, 'tablesCreated' => [$table]];
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
        if ($columns !== self::columns()) {
            return false;
        }

        $indexes = array_map(static fn (array $index): array => [
            'TYPE' => $index['INDEX_NAME'] === 'PRIMARY' ? 'PRIMARY' : ((int) $index['NON_UNIQUE'] === 0 ? 'UNIQUE' : 'INDEX'),
            'COLUMNS' => $index['COLUMNS'],
        ], MariaDbSchemaInspector::indexes($connection, $table));
        usort($indexes, static fn (array $left, array $right): int => [$left['TYPE'], $left['COLUMNS']] <=> [$right['TYPE'], $right['COLUMNS']]);
        if ($indexes !== [
            ['TYPE' => 'INDEX', 'COLUMNS' => 'employment_status:FULL:A:NO,employed_to:FULL:A:NO'],
            ['TYPE' => 'PRIMARY', 'COLUMNS' => 'installer_tab_id:FULL:A:NO'],
        ]) {
            return false;
        }

        if (MariaDbSchemaInspector::checks($connection, $table) !== ["employment_statusin('employed','dismissed')"]) {
            return false;
        }

        return MariaDbSchemaInspector::foreignKeys($connection, $table) === [];
    }

    private static function columns(): array
    {
        return [
            ['COLUMN_NAME'=>'installer_tab_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
            ['COLUMN_NAME'=>'fio','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
            ['COLUMN_NAME'=>'position','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
            ['COLUMN_NAME'=>'employment_status','COLUMN_TYPE'=>'varchar(40)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
            ['COLUMN_NAME'=>'employed_from','COLUMN_TYPE'=>'date','IS_NULLABLE'=>'YES','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
            ['COLUMN_NAME'=>'employed_to','COLUMN_TYPE'=>'date','IS_NULLABLE'=>'YES','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
            ['COLUMN_NAME'=>'workforce_source','COLUMN_TYPE'=>'varchar(80)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
            ['COLUMN_NAME'=>'workforce_source_updated_at','COLUMN_TYPE'=>'varchar(40)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ];
    }
}
