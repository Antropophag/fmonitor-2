<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
require_once __DIR__ . '/ObjectDetailSnapshotEngineSchemaMigration.php';
require_once __DIR__ . '/NoOpObjectDetailSnapshotSchemaObserver.php';

/** Production v12 entrypoint always binds an inert observer. */
final class ObjectDetailSnapshotSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        return ObjectDetailSnapshotEngineSchemaMigration::apply(
            $connection, $tablePrefix, new NoOpObjectDetailSnapshotSchemaObserver(),
        );
    }

    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        return ObjectDetailSnapshotEngineSchemaMigration::isCompleteCompatible($connection, $tablePrefix);
    }
}
