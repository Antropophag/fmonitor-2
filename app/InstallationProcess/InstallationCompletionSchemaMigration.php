<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Canonical literal-v10 owner; definition and fingerprint mechanics stay internal. */
final class InstallationCompletionSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        [$definitions, $forms, $conflicting] = self::inspect($connection, $tablePrefix);
        if ($conflicting !== []) {
            return ['applied'=>false, 'schemaVersion'=>10, 'reason'=>'SCHEMA_MIGRATION_CONFLICT',
                'conflictingTables'=>$conflicting];
        }
        $root = InstallationCompletionDefinitionSchemaMigration::ROOT;
        $corrections = InstallationCompletionDefinitionSchemaMigration::CORRECTIONS;
        if ($forms[$root] === 'absent' && $forms[$corrections] === 'exact') {
            return ['applied'=>false, 'schemaVersion'=>10, 'reason'=>'SCHEMA_MIGRATION_CONFLICT',
                'conflictingTables'=>[$tablePrefix . $corrections]];
        }
        $created = [];
        foreach ([$root, $corrections] as $name) {
            if ($forms[$name] !== 'absent') continue;
            $connection->query($definitions[$name]['ddl']);
            if ($name === $corrections) {
                InstallationCompletionDefinitionSchemaMigration::removeRedundantSupportingIndex(
                    $connection, $tablePrefix . $corrections,
                );
            }
            $created[] = $tablePrefix . $name;
        }
        sort($created, SORT_STRING);
        return ['applied'=>$created !== [], 'schemaVersion'=>10, 'tablesCreated'=>$created];
    }

    public static function isCompleteCompatible(\mysqli $connection,string $tablePrefix=''):bool
    {
        try {
            [, $forms, $conflicting] = self::inspect($connection, $tablePrefix);
            return $conflicting === [] && !in_array('absent', $forms, true);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function inspect(\mysqli $connection,string $prefix):array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
        $definitions = InstallationCompletionDefinitionSchemaMigration::definitions($prefix, $collation);
        $forms = [];
        $conflicting = [];
        foreach ($definitions as $name => $definition) {
            $table = $prefix . $name;
            if (!MariaDbSchemaInspector::tableExists($connection, $table)) {
                $forms[$name] = 'absent';
            } elseif (MariaDbInstallationCompletionSchemaFingerprint::matches(
                $connection, $table, $definition['manifest'], $collation,
            )) {
                $forms[$name] = 'exact';
            } else {
                $forms[$name] = 'conflict';
                $conflicting[] = $table;
            }
        }
        sort($conflicting, SORT_STRING);
        return [$definitions, $forms, $conflicting];
    }
}
