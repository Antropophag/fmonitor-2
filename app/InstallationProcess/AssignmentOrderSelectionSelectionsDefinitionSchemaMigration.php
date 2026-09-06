<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact physical definition from ASSIGNMENT-ORDER-SELECTION-SCHEMA-001. */
final class AssignmentOrderSelectionSelectionsDefinitionSchemaMigration
{
    public static function table(): array
    {
        return [
            'name' => '@prefixfm2_assignment_order_selections',
            'engine' => 'InnoDB',
            'collation' => '@collation',
            'columns' => [
                ['name' => 'assignment_order_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'installation_case_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'order_version', 'type' => 'smallint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'selection_revision', 'type' => 'int unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'mode', 'type' => 'varchar(24)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'previous_selection_order_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'replaces_selection_order_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'composition_identity', 'type' => 'varchar(160)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'composition_sha256', 'type' => 'char(64)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'control_engineer_user_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'control_engineer_fio_snapshot', 'type' => 'varchar(300)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'utf8mb4', 'collation' => '@collation'],
                ['name' => 'control_engineer_position_snapshot', 'type' => 'varchar(300)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'utf8mb4', 'collation' => '@collation'],
                ['name' => 'selection_date', 'type' => 'date', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'selected_at_utc', 'type' => 'datetime(6)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'selected_by_user_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
            ],
            'indexes' => [
                ['name' => 'PRIMARY', 'unique' => true, 'columns' => ['assignment_order_id']],
                ['name' => '@prefixfm2_aos_uq_case_version', 'unique' => true, 'columns' => ['installation_case_id', 'order_version']],
                ['name' => '@prefixfm2_aos_uq_case_revision', 'unique' => true, 'columns' => ['installation_case_id', 'selection_revision']],
                ['name' => '@prefixfm2_aos_uq_composition', 'unique' => true, 'columns' => ['composition_identity']],
                ['name' => '@prefixfm2_aos_ix_registry', 'unique' => false, 'columns' => ['assignment_order_id', 'installation_case_id']],
                ['name' => '@prefixfm2_aos_ix_previous', 'unique' => false, 'columns' => ['previous_selection_order_id']],
                ['name' => '@prefixfm2_aos_ix_replaces', 'unique' => false, 'columns' => ['replaces_selection_order_id']],
            ],
            'foreignKeys' => [
                ['name' => '@prefixfm2_aos_fk_registry', 'columns' => ['assignment_order_id', 'installation_case_id'], 'schema' => '@database', 'table' => '@prefixfm2_assignment_order_identities', 'target' => ['assignment_order_id', 'installation_case_id'], 'update' => 'RESTRICT', 'delete' => 'RESTRICT'],
                ['name' => '@prefixfm2_aos_fk_previous', 'columns' => ['previous_selection_order_id'], 'schema' => '@database', 'table' => '@prefixfm2_assignment_order_selections', 'target' => ['assignment_order_id'], 'update' => 'RESTRICT', 'delete' => 'RESTRICT'],
                ['name' => '@prefixfm2_aos_fk_replaces', 'columns' => ['replaces_selection_order_id'], 'schema' => '@database', 'table' => '@prefixfm2_assignment_order_selections', 'target' => ['assignment_order_id'], 'update' => 'RESTRICT', 'delete' => 'RESTRICT'],
            ],
            'checks' => [
                ['name' => '@prefixfm2_aos_ck_id', 'expression' => 'assignment_order_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aos_ck_case', 'expression' => 'installation_case_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aos_ck_version', 'expression' => 'order_version BETWEEN 1 AND 65535'],
                ['name' => '@prefixfm2_aos_ck_revision', 'expression' => 'selection_revision BETWEEN 1 AND 4294967295'],
                ['name' => '@prefixfm2_aos_ck_mode', 'expression' => 'mode IN (\'new_order\',\'replace_pending\')'],
                ['name' => '@prefixfm2_aos_ck_replaces', 'expression' => '(mode=\'new_order\' AND replaces_selection_order_id IS NULL) OR (mode=\'replace_pending\' AND replaces_selection_order_id IS NOT NULL)'],
                ['name' => '@prefixfm2_aos_ck_hash', 'expression' => 'composition_sha256 REGEXP \'^[0-9a-f]{64}$\''],
                ['name' => '@prefixfm2_aos_ck_engineer', 'expression' => 'control_engineer_user_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aos_ck_actor', 'expression' => 'selected_by_user_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aos_ck_fio', 'expression' => 'CHAR_LENGTH(TRIM(control_engineer_fio_snapshot)) BETWEEN 1 AND 300 AND OCTET_LENGTH(control_engineer_fio_snapshot)=OCTET_LENGTH(TRIM(control_engineer_fio_snapshot))'],
                ['name' => '@prefixfm2_aos_ck_position', 'expression' => 'CHAR_LENGTH(TRIM(control_engineer_position_snapshot)) BETWEEN 1 AND 300 AND OCTET_LENGTH(control_engineer_position_snapshot)=OCTET_LENGTH(TRIM(control_engineer_position_snapshot))'],
            ],
        ];
    }
}
