<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV32
{
    public const VERSION = 32;
    public const DEFERRED = RuntimeRecoverySchemaV31::DEFERRED;

    public static function tables(string $prefix):array
    {
        $tables=[...RuntimeRecoverySchemaV31::tables(''),'fm2_object_detail_edits','fm2_object_detail_edit_events','fm2_object_detail_edit_requests'];
        sort($tables,SORT_STRING);
        return array_map(static fn(string $table):string=>$prefix.$table,$tables);
    }

    public static function autoIncrement(string $prefix):array
    {
        $tables=[...RuntimeRecoverySchemaV31::autoIncrement(''),'fm2_object_detail_edit_events'];
        sort($tables,SORT_STRING);
        return array_map(static fn(string $table):string=>$prefix.$table,$tables);
    }
}
