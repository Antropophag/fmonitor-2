<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

/** Exact current-image recovery inventory. Historical profiles remain immutable. */
final class RuntimeRecoverySchemaV24
{
    public const VERSION = 24;
    public const DEFERRED = RuntimeRecoverySchemaV23::DEFERRED;
    public const AUTO_INCREMENT = RuntimeRecoverySchemaV23::AUTO_INCREMENT;

    public static function tables(string $prefix): array
    {
        $tables = [
            ...RuntimeRecoverySchemaV23::TABLES,
            'fm2_otiz_settlement_locks',
            'fm2_otiz_settlement_operations',
        ];
        sort($tables, SORT_STRING);

        return array_map(static fn(string $suffix): string => $prefix.$suffix, $tables);
    }

    public static function autoIncrement(string $prefix): array
    {
        return array_map(static fn(string $suffix): string => $prefix.$suffix, self::AUTO_INCREMENT);
    }
}
