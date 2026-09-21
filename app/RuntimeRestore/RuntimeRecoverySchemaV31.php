<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV31
{
    public const VERSION = 31;
    public const DEFERRED = RuntimeRecoverySchemaV30::DEFERRED;

    public static function tables(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV30::tables(''), 'fm2_equipment_fact_current', 'fm2_equipment_fact_history', 'fm2_equipment_fact_runs', 'fm2_equipment_fact_sync_metadata', 'fm2_equipment_fact_diagnostics'];
        sort($tables, SORT_STRING);
        return array_map(static fn (string $table): string => $prefix . $table, $tables);
    }

    public static function autoIncrement(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV30::autoIncrement(''), 'fm2_equipment_fact_diagnostics', 'fm2_equipment_fact_history'];
        sort($tables, SORT_STRING);
        return array_map(static fn (string $table): string => $prefix . $table, $tables);
    }
}
