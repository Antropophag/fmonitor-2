<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderIdentityRegistryMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        return AssignmentOrderIdentityRegistryEngineSchemaMigration::apply(
            $connection, $tablePrefix, new NoOpAssignmentOrderIdentityRegistryObserver(),
        );
    }

    public static function isBackfillComplete(\mysqli $connection, string $tablePrefix = ''): bool
    {
        AssignmentOrderIdentityRegistryValues::prefix($tablePrefix);
        try {
            return MariaDbAssignmentOrderIdentityRegistryHistory::complete($connection, $tablePrefix);
        } catch (\Throwable) {
            return false;
        }
    }
}
