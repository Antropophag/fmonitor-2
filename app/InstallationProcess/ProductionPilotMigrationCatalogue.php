<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Ordered native deployment catalogue, including the manual-pilot application successors. */
final class ProductionPilotMigrationCatalogue
{
    public static function migrations(): array
    {
        return [
            1=>ProductionProcessSchemaMigration::class,
            2=>WorkforceCatalogSchemaMigration::class,
            3=>ProcessUserCapabilitiesSchemaMigration::class,
            4=>ProcessCommandCapabilitiesSchemaMigration::class,
            5=>BitrixWorkforceHistorySchemaMigration::class,
            6=>IdentityAccessSchemaMigration::class,
            7=>ChecklistTemplateSchemaMigration::class,
            8=>static fn(\mysqli $db,string $prefix):array=>InspectionPhotoContentIndexSchemaMigration::isCompleteCompatible($db,$prefix)?['applied'=>false,'schemaVersion'=>8,'tablesCreated'=>[],'tablesUpgraded'=>[]]:InspectionEvidenceSchemaMigration::apply($db,$prefix),
            9=>InspectionPlanningSchemaMigration::class,
            10=>InstallationCompletionSchemaMigration::class,
            11=>static fn(\mysqli $db,string $prefix):array=>ClassificationProvenanceSchemaMigration::apply($db,$prefix,static function():void{}),
            12=>ObjectDetailSnapshotSchemaMigration::class,
            13=>OriginalAttemptAuditSchemaMigration::class,
            14=>AssignmentOrderIdentityRegistryMigration::class,
            15=>AssignmentOrderSelectionSchemaMigration::class,
            16=>AssignmentOrderApplicationSchemaMigration::class,
            17=>InstallationCompletionDetailsSchemaMigration::class,
            18=>AssignmentOrderSelectionUnknownEmploymentSchemaMigration::class,
            19=>InspectionPhotoContentIndexSchemaMigration::class,
            20=>OtizPublicationSchemaMigration::class,
            21=>OtizEvidenceSchemaMigration::class,
            22=>PilotLegacyObjectSchemaMigration::class,
        ];
    }
}
