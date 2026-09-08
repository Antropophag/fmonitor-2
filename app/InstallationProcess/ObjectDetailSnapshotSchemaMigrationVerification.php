<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
require_once __DIR__ . '/ObjectDetailSnapshotEngineSchemaMigration.php';

/** Verification-only composition; production exposes no observer selector. */
final class ObjectDetailSnapshotSchemaMigrationVerification
{
    public static function apply(
        \mysqli $connection,
        string $tablePrefix,
        ObjectDetailSnapshotSchemaObserver $observer,
    ): array {
        return ObjectDetailSnapshotEngineSchemaMigration::apply($connection, $tablePrefix, $observer);
    }
}
