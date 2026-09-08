<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderSelectionSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        return AssignmentOrderSelectionEngineSchemaMigration::apply($connection, $tablePrefix, new NoOpAssignmentOrderSelectionSchemaObserver());
    }
    public static function isReady(\mysqli $connection, string $tablePrefix = ''): bool
    {
        AssignmentOrderSelectionSchemaValues::prefix($tablePrefix);
        try { AssignmentOrderSelectionSchemaMigrationVerification::snapshot($connection, $tablePrefix); return true; }
        catch (\Throwable) { return AssignmentOrderSelectionUnknownEmploymentSchemaMigration::isReady($connection,$tablePrefix); }
    }
}
