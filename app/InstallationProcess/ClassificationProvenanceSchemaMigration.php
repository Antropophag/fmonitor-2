<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Canonical literal-v11 owner for classification-provenance storage. */
final class ClassificationProvenanceSchemaMigration
{
    public static function apply(
        \mysqli $connection,
        string $tablePrefix,
        callable $beforeCreate,
    ): array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($tablePrefix);
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
        $definition = ClassificationProvenanceDefinitionSchemaMigration::definition(
            $tablePrefix,
            $collation,
        );
        $table = $tablePrefix . ClassificationProvenanceDefinitionSchemaMigration::TABLE;

        if (MariaDbSchemaInspector::tableExists($connection, $table)) {
            if (!MariaDbClassificationProvenanceSchemaFingerprint::matches(
                $connection,
                $table,
                $definition,
                $collation,
            )) {
                return [
                    'applied'=>false, 'schemaVersion'=>11,
                    'reason'=>'SCHEMA_MIGRATION_CONFLICT', 'conflictingTables'=>[$table],
                ];
            }

            return ['applied'=>false, 'schemaVersion'=>11, 'tablesCreated'=>[]];
        }

        $beforeCreate();
        $connection->query($definition['ddl']);

        return ['applied'=>true, 'schemaVersion'=>11, 'tablesCreated'=>[$table]];
    }
}
