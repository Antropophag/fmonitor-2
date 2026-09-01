<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Public v6 migration seam: preflight the whole family, then recover missing members. */
final class IdentityAccessSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        [$definitions, $collation, $missing, $conflicting] = self::inspect($connection, $tablePrefix);
        if ($conflicting !== []) {
            return [
                'applied' => false,
                'schemaVersion' => 6,
                'reason' => 'SCHEMA_MIGRATION_CONFLICT',
                'conflictingTables' => $conflicting,
                'missingTables' => $missing,
                'tablesCreated' => [],
            ];
        }

        $created = [];
        foreach (IdentityAccessDefinitionSchemaMigration::tables() as $logicalName) {
            $table = $tablePrefix . $logicalName;
            if (in_array($table, $missing, true)) {
                $connection->query($definitions[$logicalName]['ddl']);
                $created[] = $table;
            }
        }
        return ['applied' => $created !== [], 'schemaVersion' => 6, 'tablesCreated' => $created];
    }

    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        [, , $missing, $conflicting] = self::inspect($connection, $tablePrefix);
        return $missing === [] && $conflicting === [];
    }

    /** Explicit destructive rebuild primitive; callers own authorization and credentials. */
    public static function rebuild(\mysqli $connection, string $tablePrefix = ''): array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($tablePrefix);
        foreach (array_reverse(IdentityAccessDefinitionSchemaMigration::tables()) as $logicalName) {
            $connection->query("DROP TABLE IF EXISTS `{$tablePrefix}{$logicalName}`");
        }
        return self::apply($connection, $tablePrefix);
    }

    private static function inspect(\mysqli $connection, string $tablePrefix): array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($tablePrefix);
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
        $definitions = IdentityAccessDefinitionSchemaMigration::definitions($tablePrefix, $collation);
        $missing = [];
        $conflicting = [];
        foreach (IdentityAccessDefinitionSchemaMigration::tables() as $logicalName) {
            $table = $tablePrefix . $logicalName;
            if (!MariaDbSchemaInspector::tableExists($connection, $table)) {
                $missing[] = $table;
            } elseif (!IdentityAccessDefinitionSchemaMigration::matches($connection, $table, $definitions[$logicalName]['manifest'], $collation)) {
                $conflicting[] = $table;
            }
        }
        return [$definitions, $collation, $missing, $conflicting];
    }
}
