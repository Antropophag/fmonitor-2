<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV39
{
    public const VERSION=39;
    public const DEFERRED=RuntimeRecoverySchemaV38::DEFERRED;
    public static function tables(string$prefix):array{$tables=[...RuntimeRecoverySchemaV38::tables(''),'fm2_assignment_order_original_starts'];sort($tables,SORT_STRING);return array_map(static fn(string$t):string=>$prefix.$t,$tables);}
    public static function autoIncrement(string$prefix):array{return RuntimeRecoverySchemaV38::autoIncrement($prefix);}
}
