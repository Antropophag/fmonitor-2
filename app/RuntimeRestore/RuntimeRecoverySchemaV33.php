<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV33
{
    public const VERSION=33;
    public const DEFERRED=RuntimeRecoverySchemaV32::DEFERRED;
    public static function tables(string $prefix):array
    {
        $tables=[...RuntimeRecoverySchemaV32::tables(''),'fm2_runtime_readiness_marker'];sort($tables,SORT_STRING);return array_map(static fn(string$table):string=>$prefix.$table,$tables);
    }
    public static function autoIncrement(string $prefix):array{return RuntimeRecoverySchemaV32::autoIncrement($prefix);}
}
