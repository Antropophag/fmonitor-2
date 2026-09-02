<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

require_once __DIR__ . '/DatabaseUnavailable.php';
require_once __DIR__ . '/IdentityAccessDefinitionSchemaMigration.php';
require_once __DIR__ . '/MariaDbSchemaInspector.php';
require_once __DIR__ . '/InspectionPlanningDefinitionSchemaMigration.php';
require_once __DIR__ . '/MariaDbInspectionPlanningSchemaFingerprint.php';

/** Public literal-v9 orchestration seam for inspection-planning schema. */
final class InspectionPlanningSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        [$definitions, $missing, $conflicting] = self::inspect($connection, $tablePrefix);
        if ($conflicting !== []) {
            return ['applied'=>false, 'schemaVersion'=>9, 'reason'=>'SCHEMA_MIGRATION_CONFLICT',
                'conflictingTables'=>$conflicting];
        }
        $created = [];
        foreach ($definitions as $name => $definition) {
            $table = $tablePrefix . $name;
            if (!in_array($table, $missing, true)) continue;
            $connection->query($definition['ddl']);
            $created[] = $table;
        }
        sort($created, SORT_STRING);
        return ['applied'=>$created !== [], 'schemaVersion'=>9, 'tablesCreated'=>$created];
    }

    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        try {
            [, $missing, $conflicting] = self::inspect($connection, $tablePrefix);
            return $missing === [] && $conflicting === [];
        } catch (\Throwable) {
            return false;
        }
    }

    private static function inspect(\mysqli $connection, string $prefix): array
    {
        self::assertPrefix($prefix);
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
        $definitions = InspectionPlanningDefinitionSchemaMigration::definitions($prefix, $collation);
        $missing = [];
        $conflicting = [];
        foreach ($definitions as $name => $definition) {
            $table = $prefix . $name;
            if (!MariaDbSchemaInspector::tableExists($connection, $table)) {
                $missing[] = $table;
            } elseif (!MariaDbInspectionPlanningSchemaFingerprint::matches(
                $connection, $table, $definition['manifest'], $collation,
            )) {
                $conflicting[] = $table;
            }
        }
        sort($conflicting, SORT_STRING);
        return [$definitions, $missing, $conflicting];
    }

    private static function assertPrefix(string $prefix): void
    {
        if (strlen($prefix) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }
}
