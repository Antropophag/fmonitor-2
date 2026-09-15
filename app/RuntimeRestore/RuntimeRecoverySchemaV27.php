<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

/** Exact current-image recovery inventory for migration frontier v27. */
final class RuntimeRecoverySchemaV27
{
    public const VERSION = 27;
    public const DEFERRED = RuntimeRecoverySchemaV26::DEFERRED;

    public static function tables(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV26::tables(''), 'fm2_bitrix_order_document_links'];
        sort($tables, SORT_STRING);
        return array_map(static fn(string $table): string => $prefix.$table, $tables);
    }

    public static function autoIncrement(string $prefix): array
    {
        return RuntimeRecoverySchemaV26::autoIncrement($prefix);
    }
}
