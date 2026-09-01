<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Public literal-v8 owner of the inspection-evidence schema family. */
final class InspectionEvidenceSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        [$definitions, $forms, $conflicting] = self::inspect($connection, $tablePrefix);
        if ($conflicting !== []) {
            return ['applied'=>false, 'schemaVersion'=>8, 'reason'=>'SCHEMA_MIGRATION_CONFLICT', 'conflictingTables'=>$conflicting];
        }

        $created = [];
        $upgraded = [];
        foreach (InspectionEvidenceDefinitionSchemaMigration::tables() as $logicalName) {
            $table = $tablePrefix . $logicalName;
            if ($forms[$logicalName] === 'absent') {
                $connection->query($definitions[$logicalName]['ddl']);
                $created[] = $table;
            } elseif ($forms[$logicalName] === 'predecessor') {
                foreach ($definitions[$logicalName]['upgrade'] as $ddl) {
                    $connection->query($ddl);
                }
                $upgraded[] = $table;
            }
        }
        sort($created, SORT_STRING);
        sort($upgraded, SORT_STRING);

        return [
            'applied' => $created !== [] || $upgraded !== [],
            'schemaVersion' => 8,
            'tablesCreated' => $created,
            'tablesUpgraded' => $upgraded,
        ];
    }

    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        try {
            [, $forms, $conflicting] = self::inspect($connection, $tablePrefix);
            return $conflicting === []
                && !in_array('absent', $forms, true)
                && !in_array('predecessor', $forms, true);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function inspect(\mysqli $connection, string $tablePrefix): array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($tablePrefix);
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
        $definitions = InspectionEvidenceDefinitionSchemaMigration::definitions($tablePrefix, $collation);
        $forms = [];
        $conflicting = [];

        foreach (InspectionEvidenceDefinitionSchemaMigration::tables() as $logicalName) {
            $table = $tablePrefix . $logicalName;
            if (!MariaDbSchemaInspector::tableExists($connection, $table)) {
                $forms[$logicalName] = 'absent';
                continue;
            }
            if (MariaDbExactSchemaFingerprint::matches($connection, $table, $definitions[$logicalName]['final'], $collation)) {
                $forms[$logicalName] = 'final';
                continue;
            }
            if (isset($definitions[$logicalName]['predecessor'])
                && MariaDbExactSchemaFingerprint::matches($connection, $table, $definitions[$logicalName]['predecessor'], $collation)) {
                $forms[$logicalName] = 'predecessor';
                continue;
            }
            $conflicting[] = $table;
        }
        sort($conflicting, SORT_STRING);

        return [$definitions, $forms, $conflicting];
    }
}
