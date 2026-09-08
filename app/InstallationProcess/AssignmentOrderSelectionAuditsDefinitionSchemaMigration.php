<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact physical definition from ASSIGNMENT-ORDER-SELECTION-SCHEMA-001. */
final class AssignmentOrderSelectionAuditsDefinitionSchemaMigration
{
    public static function table(): array
    {
        return [
            'name' => '@prefixfm2_assignment_order_selection_audits',
            'engine' => 'InnoDB',
            'collation' => '@collation',
            'columns' => [
                ['name' => 'audit_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => 'auto_increment', 'charset' => null, 'collation' => null],
                ['name' => 'request_id', 'type' => 'char(36)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'actor_user_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'installation_object_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'mode', 'type' => 'varchar(24)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'status', 'type' => 'varchar(16)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'reason_code', 'type' => 'varchar(64)', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'attempted_at_utc', 'type' => 'datetime(6)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
            ],
            'indexes' => [
                ['name' => 'PRIMARY', 'unique' => true, 'columns' => ['audit_id']],
                ['name' => '@prefixfm2_aosa_ix_request', 'unique' => false, 'columns' => ['request_id']],
            ],
            'foreignKeys' => [
            ],
            'checks' => [
                ['name' => '@prefixfm2_aosa_ck_uuid', 'expression' => 'request_id REGEXP \'^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$\''],
                ['name' => '@prefixfm2_aosa_ck_actor', 'expression' => 'actor_user_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aosa_ck_object', 'expression' => 'installation_object_id BETWEEN 1 AND 9223372036854775807'],
                ['name' => '@prefixfm2_aosa_ck_mode', 'expression' => 'mode IN (\'new_order\',\'replace_pending\')'],
                ['name' => '@prefixfm2_aosa_ck_status', 'expression' => '(status=\'selected\' AND reason_code IS NULL) OR (status=\'rejected\' AND reason_code IS NOT NULL AND reason_code IN (\'invalid_command\',\'authorization_denied\',\'object_not_found\',\'installer_required\',\'control_engineer_required\',\'installer_not_in_catalog\',\'installer_not_employed\',\'control_engineer_not_eligible\',\'object_has_pto_act\',\'object_completed\',\'no_changes\')) OR (status=\'conflict\' AND reason_code IS NOT NULL AND reason_code IN (\'request_id_conflict\',\'stale_selection\',\'pending_selection_exists\',\'selection_not_found\',\'original_already_accepted\'))'],
            ],
        ];
    }
}
