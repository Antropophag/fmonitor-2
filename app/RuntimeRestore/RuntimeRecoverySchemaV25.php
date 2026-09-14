<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;
final class RuntimeRecoverySchemaV25
{
    public const VERSION = 25;
    public const DEFERRED = RuntimeRecoverySchemaV24::DEFERRED;
    public static function tables(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV24::tables(""), "fm2_feedback", "fm2_feedback_results"];
        sort($tables, SORT_STRING);
        return array_map(fn($x) => $prefix . $x, $tables);
    }
    public static function autoIncrement(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV24::autoIncrement(""), "fm2_feedback", "fm2_feedback_results"];
        sort($tables, SORT_STRING);
        return array_map(fn($x) => $prefix . $x, $tables);
    }
}
