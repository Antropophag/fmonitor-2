<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class RuntimeRecoverySchemaV38
{
    public const VERSION = 38;
    public const DEFERRED = RuntimeRecoverySchemaV37::DEFERRED;

    public static function tables(string $prefix): array
    {
        return RuntimeRecoverySchemaV37::tables($prefix);
    }

    public static function autoIncrement(string $prefix): array
    {
        return RuntimeRecoverySchemaV37::autoIncrement($prefix);
    }
}
