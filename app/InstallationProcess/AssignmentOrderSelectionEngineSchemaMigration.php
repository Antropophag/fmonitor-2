<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Sole disabled selection DDL lifecycle owner. No canonical/runtime caller. */
final class AssignmentOrderSelectionEngineSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix, AssignmentOrderSelectionSchemaObserver $observer): array
    {
        AssignmentOrderSelectionSchemaValues::prefix($tablePrefix);
        $database = MariaDbAssignmentOrderSelectionSchemaSql::configuration($connection);
        $name = 'fm2_aoss_' . substr(hash('sha256', $database . "\0" . $tablePrefix), 0, 48);
        $acquired = false; $failed = false; $result = null;
        try {
            $lock = MariaDbAssignmentOrderSelectionSchemaSql::one($connection, 'SELECT GET_LOCK(?,5) n', [$name]);
            if ((string)$lock['n'] !== '1') { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
            $acquired = true; $observer->observe(AssignmentOrderSelectionSchemaPhase::LOCK_ACQUIRED);
            $plan = MariaDbAssignmentOrderSelectionSchemaProof::read($connection, $tablePrefix);
            if ($plan === null) { $result = ['applied' => false, 'reason' => 'SCHEMA_MIGRATION_CONFLICT']; }
            else {
                $phases = [AssignmentOrderSelectionSchemaPhase::SELECTIONS_CREATED,AssignmentOrderSelectionSchemaPhase::MEMBERS_CREATED,
                    AssignmentOrderSelectionSchemaPhase::REQUESTS_CREATED,AssignmentOrderSelectionSchemaPhase::EVENTS_CREATED,AssignmentOrderSelectionSchemaPhase::AUDITS_CREATED];
                $tables = AssignmentOrderSelectionDefinitionSchemaMigration::tables($tablePrefix);
                for ($i = $plan['count']; $i < 5; $i++) {
                    if ($connection->query(AssignmentOrderSelectionDefinitionSchemaMigration::sql($tables[$i], $plan['collation'])) !== true) {
                        throw AssignmentOrderSelectionSchemaValues::unavailable();
                    }
                    $observer->observe($phases[$i]);
                }
                $proof = MariaDbAssignmentOrderSelectionSchemaProof::read($connection, $tablePrefix);
                if ($proof === null || $proof['count'] !== 5) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
                $observer->observe(AssignmentOrderSelectionSchemaPhase::FAMILY_VERIFIED);
                $result = ['applied' => $plan['count'] < 5];
            }
        } catch (\Throwable) { $failed = true; }
        finally {
            if ($acquired) {
                try {
                    $release = MariaDbAssignmentOrderSelectionSchemaSql::one($connection, 'SELECT RELEASE_LOCK(?) n', [$name]);
                    if ((string)$release['n'] !== '1') { $failed = true; }
                } catch (\Throwable) { $failed = true; }
            }
        }
        if ($failed || $result === null) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
        return $result;
    }
}
