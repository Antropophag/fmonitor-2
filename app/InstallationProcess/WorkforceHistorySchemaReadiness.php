<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class WorkforceHistorySchemaReadiness
{
    private const LOGICAL_TABLES = [
        'fm2_workforce_catalog',
        'fm2_workforce_observations',
        'fm2_workforce_sync_runs',
        'fm2_workforce_sync_metadata',
    ];

    public static function assertReady(\mysqli $connection, string $tablePrefix = ''): void
    {
        self::validatePrefix($tablePrefix);
        foreach (self::LOGICAL_TABLES as $logicalName) {
            if (!self::isCompatible($connection, $logicalName, $tablePrefix)) {
                throw new \RuntimeException('Workforce history schema is unavailable.');
            }
        }
    }

    public static function isCatalogCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        self::validatePrefix($tablePrefix);

        return self::isCompatible($connection, 'fm2_workforce_catalog', $tablePrefix);
    }

    private static function isCompatible(\mysqli $connection, string $logicalName, string $tablePrefix): bool
    {
        return BitrixWorkforceHistorySchemaMigration::classify(
            $connection,
            $logicalName,
            $tablePrefix . $logicalName,
            $tablePrefix,
        ) === 'v5';
    }

    private static function validatePrefix(string $tablePrefix): void
    {
        if (preg_match('/^[A-Za-z0-9_]{0,37}$/D', $tablePrefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }
}
