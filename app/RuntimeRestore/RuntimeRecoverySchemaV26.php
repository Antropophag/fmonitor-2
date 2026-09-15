<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

/** Exact current-image recovery inventory for migration frontier v26. */
final class RuntimeRecoverySchemaV26
{
    public const VERSION = 26;
    public const DEFERRED = RuntimeRecoverySchemaV25::DEFERRED;

    public static function tables(string $prefix): array
    {
        $tables = [...RuntimeRecoverySchemaV25::tables(''), 'fm2_bitrix_order_document_links'];
        sort($tables, SORT_STRING);
        return array_map(static fn(string $table): string => $prefix.$table, $tables);
    }

    public static function autoIncrement(string $prefix): array
    {
        return RuntimeRecoverySchemaV25::autoIncrement($prefix);
    }
}
