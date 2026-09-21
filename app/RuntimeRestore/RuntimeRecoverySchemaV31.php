<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

/** Exact current-image recovery inventory; retained class name is a compatibility alias for frontier v32. */
final class RuntimeRecoverySchemaV31
{
    public const VERSION = 32;
    public const DEFERRED = RuntimeRecoverySchemaV30::DEFERRED;

    public static function tables(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV30::tables(''), 'fm2_equipment_fact_current', 'fm2_equipment_fact_history', 'fm2_equipment_fact_runs', 'fm2_equipment_fact_sync_metadata', 'fm2_equipment_fact_diagnostics', 'fm2_object_detail_edits', 'fm2_object_detail_edit_events', 'fm2_object_detail_edit_requests'];
        sort($tables, SORT_STRING);
        return array_map(static fn (string $table): string => $prefix . $table, $tables);
    }

    public static function autoIncrement(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV30::autoIncrement(''), 'fm2_equipment_fact_diagnostics', 'fm2_equipment_fact_history', 'fm2_object_detail_edit_events'];
        sort($tables, SORT_STRING);
        return array_map(static fn (string $table): string => $prefix . $table, $tables);
    }
}
