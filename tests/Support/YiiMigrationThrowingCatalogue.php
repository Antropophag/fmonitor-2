<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class ProductionPilotMigrationCatalogue
{
    public static function migrations(): array
    {
        throw new \RuntimeException('PRIVATE_THROWABLE_SQL_SELECT_CANARY');
    }
}
