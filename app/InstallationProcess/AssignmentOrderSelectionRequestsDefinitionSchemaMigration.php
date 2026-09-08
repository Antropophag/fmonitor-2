<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact physical definition from ASSIGNMENT-ORDER-SELECTION-SCHEMA-001. */
final class AssignmentOrderSelectionRequestsDefinitionSchemaMigration
{
    public static function table(): array
    {
        return [
            'name' => '@prefixfm2_assignment_order_selection_requests',
            'engine' => 'InnoDB',
            'collation' => '@collation',
            'columns' => [
                ['name' => 'request_id', 'type' => 'char(36)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'operation_fingerprint', 'type' => 'char(64)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'actor_user_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'control_engineer_user_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'expected_selection_revision', 'type' => 'int unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'installation_object_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'installer_tab_ids_json', 'type' => 'text', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'mode', 'type' => 'varchar(24)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'status', 'type' => 'varchar(16)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'reason_code', 'type' => 'varchar(64)', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'retryable', 'type' => 'tinyint', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'case_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'assignment_order_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'assignment_order_version', 'type' => 'smallint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'selection_revision', 'type' => 'int unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'composition_identity', 'type' => 'varchar(160)', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'composition_sha256', 'type' => 'char(64)', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'selection_date', 'type' => 'date', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'selected_at_utc', 'type' => 'datetime(6)', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'terminal_at_utc', 'type' => 'datetime(6)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
            ],
            'indexes' => [
                ['name' => 'PRIMARY', 'unique' => true, 'columns' => ['request_id']],
                ['name' => '@prefixfm2_aosr_ix_selection', 'unique' => false, 'columns' => ['assignment_order_id']],
            ],
            'foreignKeys' => [
                ['name' => '@prefixfm2_aosr_fk_selection', 'columns' => ['assignment_order_id'], 'schema' => '@database', 'table' => '@prefixfm2_assignment_order_selections', 'target' => ['assignment_order_id'], 'update' => 'RESTRICT', 'delete' => 'RESTRICT'],
            ],
            'checks' => [
                ['name' => '@prefixfm2_aosr_ck_uuid', 'expression' => 'request_id REGEXP \'^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$\''],
                ['name' => '@prefixfm2_aosr_ck_fingerprint', 'expression' => 'operation_fingerprint REGEXP \'^[0-9a-f]{64}$\''],
                ['name' => '@prefixfm2_aosr_ck_actor', 'expression' => 'actor_user_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aosr_ck_object', 'expression' => 'installation_object_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aosr_ck_engineer', 'expression' => 'control_engineer_user_id IS NULL OR control_engineer_user_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aosr_ck_expected', 'expression' => 'expected_selection_revision BETWEEN 0 AND 4294967295'],
                ['name' => '@prefixfm2_aosr_ck_members', 'expression' => 'OCTET_LENGTH(installer_tab_ids_json) BETWEEN 2 AND 10001'],
                ['name' => '@prefixfm2_aosr_ck_mode', 'expression' => 'mode IN (\'new_order\',\'replace_pending\')'],
                ['name' => '@prefixfm2_aosr_ck_status', 'expression' => '(status=\'selected\' AND reason_code IS NULL) OR (status=\'rejected\' AND reason_code IS NOT NULL AND reason_code IN (\'invalid_command\',\'authorization_denied\',\'object_not_found\',\'installer_required\',\'control_engineer_required\',\'installer_not_in_catalog\',\'installer_not_employed\',\'control_engineer_not_eligible\',\'object_has_pto_act\',\'object_completed\',\'no_changes\')) OR (status=\'conflict\' AND reason_code IS NOT NULL AND reason_code IN (\'request_id_conflict\',\'stale_selection\',\'pending_selection_exists\',\'selection_not_found\',\'original_already_accepted\'))'],
                ['name' => '@prefixfm2_aosr_ck_retry', 'expression' => 'retryable=0'],
                ['name' => '@prefixfm2_aosr_ck_success', 'expression' => '(status=\'selected\' AND case_id IS NOT NULL AND assignment_order_id IS NOT NULL AND assignment_order_version IS NOT NULL AND selection_revision IS NOT NULL AND composition_identity IS NOT NULL AND composition_sha256 IS NOT NULL AND selection_date IS NOT NULL AND selected_at_utc IS NOT NULL) OR (status IN (\'rejected\',\'conflict\') AND case_id IS NULL AND assignment_order_id IS NULL AND assignment_order_version IS NULL AND selection_revision IS NULL AND composition_identity IS NULL AND composition_sha256 IS NULL AND selection_date IS NULL AND selected_at_utc IS NULL)'],
            ],
        ];
    }
}
