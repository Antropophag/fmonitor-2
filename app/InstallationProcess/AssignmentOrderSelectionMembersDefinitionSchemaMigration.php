<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact physical definition from ASSIGNMENT-ORDER-SELECTION-SCHEMA-001. */
final class AssignmentOrderSelectionMembersDefinitionSchemaMigration
{
    public static function table(): array
    {
        return [
            'name' => '@prefixfm2_assignment_order_selection_members',
            'engine' => 'InnoDB',
            'collation' => '@collation',
            'columns' => [
                ['name' => 'assignment_order_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'installer_tab_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'fio_snapshot', 'type' => 'varchar(300)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'utf8mb4', 'collation' => '@collation'],
                ['name' => 'position_snapshot', 'type' => 'varchar(300)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'utf8mb4', 'collation' => '@collation'],
                ['name' => 'employment_status_snapshot', 'type' => 'varchar(40)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'employed_from_snapshot', 'type' => 'date', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'employed_to_snapshot', 'type' => 'date', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'workforce_source_snapshot', 'type' => 'varchar(80)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'utf8mb4', 'collation' => '@collation'],
                ['name' => 'workforce_source_updated_at_snapshot', 'type' => 'varchar(40)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
            ],
            'indexes' => [
                ['name' => 'PRIMARY', 'unique' => true, 'columns' => ['assignment_order_id', 'installer_tab_id']],
            ],
            'foreignKeys' => [
                ['name' => '@prefixfm2_aosm_fk_selection', 'columns' => ['assignment_order_id'], 'schema' => '@database', 'table' => '@prefixfm2_assignment_order_selections', 'target' => ['assignment_order_id'], 'update' => 'RESTRICT', 'delete' => 'RESTRICT'],
            ],
            'checks' => [
                ['name' => '@prefixfm2_aosm_ck_installer', 'expression' => 'installer_tab_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aosm_ck_fio', 'expression' => 'CHAR_LENGTH(TRIM(fio_snapshot)) BETWEEN 1 AND 300 AND OCTET_LENGTH(fio_snapshot)=OCTET_LENGTH(TRIM(fio_snapshot))'],
                ['name' => '@prefixfm2_aosm_ck_position', 'expression' => 'CHAR_LENGTH(TRIM(position_snapshot)) BETWEEN 1 AND 300 AND OCTET_LENGTH(position_snapshot)=OCTET_LENGTH(TRIM(position_snapshot))'],
                ['name' => '@prefixfm2_aosm_ck_employed', 'expression' => 'employment_status_snapshot=\'employed\''],
                ['name' => '@prefixfm2_aosm_ck_period', 'expression' => 'employed_to_snapshot IS NULL OR employed_to_snapshot>=employed_from_snapshot'],
                ['name' => '@prefixfm2_aosm_ck_source', 'expression' => 'CHAR_LENGTH(TRIM(workforce_source_snapshot)) BETWEEN 1 AND 80 AND OCTET_LENGTH(workforce_source_snapshot)=OCTET_LENGTH(TRIM(workforce_source_snapshot))'],
            ],
        ];
    }
}
