<?php

declare(strict_types=1);

namespace FMonitor2\Runtime;

use FMonitor2\InstallationProcess as Schema;

/** Read-only checks of the schema families used by the composite HTTP application. */
final class MariaDbRuntimeReadiness
{
    public static function assertReady(RuntimeConfiguration $config): void
    {
        $connection = self::connect($config);
        try {
            self::assertSchema($connection, $config);
        } finally {
            $connection->close();
        }
    }

    public static function assertAvailable(RuntimeConfiguration $config): void
    {
        self::connect($config)->close();
    }

    private static function connect(RuntimeConfiguration $config): \mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = mysqli_init();
        try {
            $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
            $connection->options(MYSQLI_OPT_READ_TIMEOUT, 3);
            @$connection->real_connect(
                $config->value('FMONITOR_DB_HOST'), $config->value('FMONITOR_DB_USER'),
                $config->value('FMONITOR_DB_PASSWORD'), $config->value('FMONITOR_DB_NAME'),
                (int) $config->value('FMONITOR_DB_PORT'),
            );
            $connection->set_charset('utf8mb4');
        } catch (\Throwable) {
            throw new \RuntimeException('DATABASE_UNAVAILABLE');
        }
        return $connection;
    }

    private static function assertSchema(\mysqli $connection, RuntimeConfiguration $config): void
    {
        try {
            $prefix = $config->value('FMONITOR_PROCESS_TABLE_PREFIX');
            Schema\MariaDbPilotLegacyObjectSchemaReadiness::assertReady($connection, $prefix);
            Schema\WorkforceHistorySchemaReadiness::assertReady($connection, $prefix);
            foreach ([
                Schema\IdentityAccessSchemaMigration::class,
                Schema\ChecklistTemplateSchemaMigration::class,
                Schema\InspectionPhotoContentIndexSchemaMigration::class,
                Schema\InspectionPlanningSchemaMigration::class,
                Schema\InstallationCompletionSchemaMigration::class,
                Schema\InstallationCompletionDetailsSchemaMigration::class,
                Schema\ObjectDetailSnapshotSchemaMigration::class,
                Schema\OtizPublicationSchemaMigration::class,
                Schema\OtizEvidenceSchemaMigration::class,
            ] as $schema) {
                if (!$schema::isCompleteCompatible($connection, $prefix)) throw new \RuntimeException();
            }
            if (!Schema\MariaDbProductionProcessSchemaReadiness::isCompleteCompatible($connection, $prefix)
                || !Schema\MariaDbOriginalAttemptAuditSchemaFingerprint::fullV3($connection, $prefix)
                || !Schema\MariaDbAssignmentOrderIdentityRegistrySourceShape::compatible($connection, $prefix)
                || !Schema\AssignmentOrderSelectionSchemaMigration::isReady($connection, $prefix)
                || !Schema\AssignmentOrderApplicationSchemaMigration::isReady($connection, $prefix)
                || !Schema\AssignmentOrderSelectionUnknownEmploymentSchemaMigration::isReady($connection, $prefix)) throw new \RuntimeException();
            $collation = Schema\MariaDbAssignmentOrderIdentityRegistryCatalog::collation($connection);
            if ($collation === null) throw new \RuntimeException();
            foreach ([Schema\AssignmentOrderIdentityRegistryDefinitionSchemaMigration::REGISTRY,
                Schema\AssignmentOrderIdentityRegistryDefinitionSchemaMigration::RECEIPTS] as $table) {
                if (!Schema\MariaDbAssignmentOrderIdentityRegistryCatalog::compatible($connection, $prefix, $table, $collation)) throw new \RuntimeException();
            }
        } catch (\Throwable) {
            throw new \RuntimeException('SCHEMA_NOT_READY');
        }
    }
}
