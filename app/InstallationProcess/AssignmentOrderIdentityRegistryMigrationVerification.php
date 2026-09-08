<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderIdentityRegistryMigrationVerification
{
    public static function apply(\mysqli $connection, string $tablePrefix, AssignmentOrderIdentityRegistryObserver $observer): array
    {
        return AssignmentOrderIdentityRegistryEngineSchemaMigration::apply($connection, $tablePrefix, $observer);
    }

    public static function snapshot(\mysqli $connection, string $tablePrefix): AssignmentOrderIdentityRegistrySnapshot
    {
        AssignmentOrderIdentityRegistryValues::prefix($tablePrefix);
        try {
            return MariaDbAssignmentOrderIdentityRegistryHistory::snapshot($connection, $tablePrefix);
        } catch (\Throwable) {
            throw AssignmentOrderIdentityRegistryValues::unavailable();
        }
    }
}
