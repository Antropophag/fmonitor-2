<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProcessCommandCapabilitiesSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        MariaDbSchemaInspector::validateTablePrefix($tablePrefix);
        $table = $tablePrefix . 'fm2_process_user_capabilities';
        $inspection = self::inspectSchema($connection, $table);
        if (($inspection['state'] ?? null) === 'v4') {
            return ['applied'=>false,'schemaVersion'=>4,'constraintsChanged'=>[]];
        }
        if (($inspection['state'] ?? null) !== 'v3') {
            return ['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];
        }

        $capabilityConstraint = (string) $inspection['capabilityConstraint'];
        if (preg_match('/^[A-Za-z0-9_$]{1,64}$/D', $capabilityConstraint) !== 1) {
            return ['applied'=>false,'schemaVersion'=>4,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];
        }
        $connection->query("ALTER TABLE `{$table}` DROP CONSTRAINT `{$capabilityConstraint}`, ADD CONSTRAINT `ck_fm2_process_user_capability` CHECK (capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer'))");

        return ['applied'=>true,'schemaVersion'=>4,'constraintsChanged'=>['ck_fm2_process_user_capability']];
    }

    /** @return array{state: string, capabilityConstraint: string}|null */
    private static function inspectSchema(\mysqli $connection, string $table): ?array
    {
        $properties = MariaDbSchemaInspector::tableProperties($connection, $table);
        if ($properties === null
            || $properties['ENGINE'] !== 'InnoDB'
            || !str_starts_with((string) $properties['TABLE_COLLATION'], 'utf8mb4_')) return null;

        $inspectedColumns = MariaDbSchemaInspector::columns($connection, $table);
        foreach ($inspectedColumns as $column) {
            if ($column['CHARACTER_SET_NAME'] === 'utf8mb4'
                && !str_starts_with((string) $column['COLLATION_NAME'], 'utf8mb4_')) return null;
        }
        $columns = array_map(static fn(array $column):array => [
            'COLUMN_NAME'=>$column['COLUMN_NAME'],'COLUMN_TYPE'=>$column['COLUMN_TYPE'],'IS_NULLABLE'=>$column['IS_NULLABLE'],
            'EXTRA'=>$column['EXTRA'],'CHARACTER_SET_NAME'=>$column['CHARACTER_SET_NAME'],
        ], $inspectedColumns);
        if ($columns !== [
            ['COLUMN_NAME'=>'user_id','COLUMN_TYPE'=>'bigint(20) unsigned','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>null],
            ['COLUMN_NAME'=>'capability','COLUMN_TYPE'=>'varchar(80)','IS_NULLABLE'=>'NO','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
            ['COLUMN_NAME'=>'position_snapshot','COLUMN_TYPE'=>'varchar(300)','IS_NULLABLE'=>'YES','EXTRA'=>'','CHARACTER_SET_NAME'=>'utf8mb4'],
        ]) return null;

        $indexes = MariaDbSchemaInspector::indexes($connection, $table);
        usort($indexes, static fn(array $a,array $b):int => $a['INDEX_NAME'] <=> $b['INDEX_NAME']);
        if ($indexes !== [
            ['INDEX_NAME'=>'PRIMARY','NON_UNIQUE'=>0,'COLUMNS'=>'user_id:FULL:A:NO,capability:FULL:A:NO'],
            ['INDEX_NAME'=>'capability','NON_UNIQUE'=>1,'COLUMNS'=>'capability:FULL:A:NO,user_id:FULL:A:NO'],
        ] && $indexes !== [
            ['INDEX_NAME'=>'PRIMARY','NON_UNIQUE'=>'0','COLUMNS'=>'user_id:FULL:A:NO,capability:FULL:A:NO'],
            ['INDEX_NAME'=>'capability','NON_UNIQUE'=>'1','COLUMNS'=>'capability:FULL:A:NO,user_id:FULL:A:NO'],
        ]) return null;
        if (MariaDbSchemaInspector::foreignKeys($connection, $table) !== []) return null;

        return ProcessCapabilityChecksClassifier::inspect($connection, $table);
    }
}
