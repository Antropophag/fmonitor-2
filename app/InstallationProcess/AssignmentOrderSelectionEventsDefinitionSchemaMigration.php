<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact physical definition from ASSIGNMENT-ORDER-SELECTION-SCHEMA-001. */
final class AssignmentOrderSelectionEventsDefinitionSchemaMigration
{
    public static function table(): array
    {
        return [
            'name' => '@prefixfm2_assignment_order_selection_events',
            'engine' => 'InnoDB',
            'collation' => '@collation',
            'columns' => [
                ['name' => 'event_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => 'auto_increment', 'charset' => null, 'collation' => null],
                ['name' => 'event_type', 'type' => 'varchar(64)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'request_id', 'type' => 'char(36)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'installation_case_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'assignment_order_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'assignment_order_version', 'type' => 'smallint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'selection_revision', 'type' => 'int unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'previous_selection_order_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'replaces_selection_order_id', 'type' => 'bigint unsigned', 'nullable' => true, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'composition_sha256', 'type' => 'char(64)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => 'ascii', 'collation' => 'ascii_bin'],
                ['name' => 'occurred_at_utc', 'type' => 'datetime(6)', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
                ['name' => 'actor_user_id', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => null, 'extra' => '', 'charset' => null, 'collation' => null],
            ],
            'indexes' => [
                ['name' => 'PRIMARY', 'unique' => true, 'columns' => ['event_id']],
                ['name' => '@prefixfm2_aose_uq_request', 'unique' => true, 'columns' => ['request_id']],
                ['name' => '@prefixfm2_aose_uq_selection', 'unique' => true, 'columns' => ['assignment_order_id']],
            ],
            'foreignKeys' => [
                ['name' => '@prefixfm2_aose_fk_request', 'columns' => ['request_id'], 'schema' => '@database', 'table' => '@prefixfm2_assignment_order_selection_requests', 'target' => ['request_id'], 'update' => 'RESTRICT', 'delete' => 'RESTRICT'],
                ['name' => '@prefixfm2_aose_fk_selection', 'columns' => ['assignment_order_id'], 'schema' => '@database', 'table' => '@prefixfm2_assignment_order_selections', 'target' => ['assignment_order_id'], 'update' => 'RESTRICT', 'delete' => 'RESTRICT'],
            ],
            'checks' => [
                ['name' => '@prefixfm2_aose_ck_type', 'expression' => 'event_type=\'assignment_order_composition_selected\''],
            ],
        ];
    }
}
