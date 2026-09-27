<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV37
{
    public const VERSION = 37;
    public const DEFERRED = RuntimeRecoverySchemaV36::DEFERRED;

    public static function tables(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV36::tables(''), 'fm2_otiz_admission_events', 'fm2_otiz_entitlement_frontiers'];
        sort($tables, SORT_STRING);
        return array_map(static fn(string $table): string => $prefix.$table, $tables);
    }

    public static function autoIncrement(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV36::autoIncrement(''), 'fm2_otiz_admission_events', 'fm2_otiz_entitlement_frontiers'];
        sort($tables, SORT_STRING);
        return array_map(static fn(string $table): string => $prefix.$table, $tables);
    }
}
