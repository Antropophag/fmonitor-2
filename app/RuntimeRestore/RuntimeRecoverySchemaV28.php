<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

/** Exact current-image recovery inventory for migration frontier v28. */
final class RuntimeRecoverySchemaV28
{
    public const VERSION = 28;
    public const DEFERRED = RuntimeRecoverySchemaV27::DEFERRED;
    public static function tables(string $prefix): array
    {
        $tables=[...RuntimeRecoverySchemaV27::tables(''),'fm2_control_engineer_assignments'];sort($tables,SORT_STRING);
        return array_map(static fn(string$table):string=>$prefix.$table,$tables);
    }
    public static function autoIncrement(string $prefix): array
    {
        $tables=[...RuntimeRecoverySchemaV27::autoIncrement(''),'fm2_control_engineer_assignments'];sort($tables,SORT_STRING);
        return array_map(static fn(string$table):string=>$prefix.$table,$tables);
    }
}
