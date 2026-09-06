<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderSelectionSchemaMigrationVerification
{
    public static function apply(\mysqli $connection, string $tablePrefix, AssignmentOrderSelectionSchemaObserver $observer): array
    {
        return AssignmentOrderSelectionEngineSchemaMigration::apply($connection, $tablePrefix, $observer);
    }
    public static function snapshot(\mysqli $connection, string $tablePrefix): AssignmentOrderSelectionSchemaSnapshot
    {
        AssignmentOrderSelectionSchemaValues::prefix($tablePrefix);
        try {
            MariaDbAssignmentOrderSelectionSchemaSql::configuration($connection);
            $proof = MariaDbAssignmentOrderSelectionSchemaProof::read($connection, $tablePrefix);
            if ($proof === null || $proof['count'] !== 5) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
            return $proof['snapshot'];
        } catch (\Throwable) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
    }
}
