<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV30
{
    public const VERSION = 30;
    public const DEFERRED = RuntimeRecoverySchemaV29::DEFERRED;

    public static function tables(string $prefix): array
    {
        return RuntimeRecoverySchemaV29::tables($prefix);
    }

    public static function autoIncrement(string $prefix): array
    {
        return RuntimeRecoverySchemaV29::autoIncrement($prefix);
    }
}
