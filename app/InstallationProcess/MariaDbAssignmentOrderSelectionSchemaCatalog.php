<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbAssignmentOrderSelectionSchemaCatalog
{
    public static function shape(\mysqli $db, array $expected, string $collation): ?array
    {
        $table = $expected['name'];
        $properties = MariaDbAssignmentOrderSelectionSchemaSql::rows($db, 'SELECT ENGINE,TABLE_COLLATION,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table]);
        if ($properties === []) { return null; }
        AssignmentOrderSelectionSchemaValues::require($properties === [['ENGINE' => 'InnoDB','TABLE_COLLATION' => $collation,'TABLE_TYPE' => 'BASE TABLE']]);
        $columns = MariaDbAssignmentOrderSelectionSchemaSql::rows($db, 'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME,GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$table]);
        $actual = [];
        foreach ($columns as $row) {
            AssignmentOrderSelectionSchemaValues::require($row['GENERATION_EXPRESSION'] === null || $row['GENERATION_EXPRESSION'] === '');
            $actual[] = ['name' => $row['COLUMN_NAME'], 'type' => preg_replace('/^(tinyint|smallint|int|bigint)\([0-9]+\)/', '$1', strtolower($row['COLUMN_TYPE'])),
                'nullable' => $row['IS_NULLABLE'] === 'YES', 'default' => $row['COLUMN_DEFAULT'] === 'NULL' ? null : $row['COLUMN_DEFAULT'],
                'extra' => $row['EXTRA'], 'charset' => $row['CHARACTER_SET_NAME'],
                'collation' => $row['CHARACTER_SET_NAME'] === 'utf8mb4' && $row['COLLATION_NAME'] === $collation ? '@collation' : $row['COLLATION_NAME']];
        }
        AssignmentOrderSelectionSchemaValues::require($actual === $expected['columns']);
        self::indexes($db, $table, $expected['indexes']);
        self::foreignKeys($db, $table, $expected['foreignKeys']);
        $actual = [];
        foreach (MariaDbAssignmentOrderSelectionSchemaSql::rows($db, 'SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table]) as $row) {
            $actual[$row['CONSTRAINT_NAME']] = AssignmentOrderSelectionSchemaPredicate::ast($row['CHECK_CLAUSE']);
        }
        $checks = []; foreach ($expected['checks'] as &$check) { $check['expression'] = AssignmentOrderSelectionSchemaPredicate::ast($check['expression']); $checks[$check['name']] = $check['expression']; } unset($check);
        ksort($actual, SORT_STRING); ksort($checks, SORT_STRING);
        AssignmentOrderSelectionSchemaValues::require($actual === $checks);
        AssignmentOrderSelectionSchemaValues::require((string)MariaDbAssignmentOrderSelectionSchemaSql::one($db, 'SELECT COUNT(*) n FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE=?', [$table])['n'] === '0');
        foreach (['indexes','foreignKeys','checks'] as $key) { usort($expected[$key], static fn ($a, $b) => strcmp($a['name'], $b['name'])); }
        return $expected;
    }

    private static function indexes(\mysqli $db, string $table, array $expected): void
    {
        $actual = [];
        $rows = MariaDbAssignmentOrderSelectionSchemaSql::rows($db, 'SELECT INDEX_NAME,NON_UNIQUE,COLUMN_NAME,SUB_PART,COLLATION,IGNORED,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX', [$table]);
        foreach ($rows as $row) {
            AssignmentOrderSelectionSchemaValues::require($row['SUB_PART'] === null && $row['COLLATION'] === 'A' && $row['IGNORED'] === 'NO' && $row['INDEX_TYPE'] === 'BTREE');
            $name = $row['INDEX_NAME'];
            $actual[$name] ??= ['name' => $name,'unique' => (string)$row['NON_UNIQUE'] === '0','columns' => []];
            $actual[$name]['columns'][] = $row['COLUMN_NAME'];
        }
        usort($expected, static fn ($a, $b) => strcmp($a['name'], $b['name']));
        AssignmentOrderSelectionSchemaValues::require(array_values($actual) === $expected);
    }

    private static function foreignKeys(\mysqli $db, string $table, array $expected): void
    {
        $rows = MariaDbAssignmentOrderSelectionSchemaSql::rows($db, 'SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_SCHEMA,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY BINARY k.CONSTRAINT_NAME,k.ORDINAL_POSITION', [$table]);
        $visible = MariaDbAssignmentOrderSelectionSchemaSql::one($db, 'SELECT COUNT(*) n FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND REFERENCED_TABLE_NAME IS NOT NULL', [$table]);
        if ((string)count($rows) !== (string)$visible['n']) { throw AssignmentOrderSelectionSchemaValues::unavailable(); }
        $database = MariaDbAssignmentOrderSelectionSchemaSql::one($db, 'SELECT DATABASE() n')['n']; $actual = [];
        foreach ($rows as $row) {
            $name = $row['CONSTRAINT_NAME'];
            AssignmentOrderSelectionSchemaValues::require($row['REFERENCED_TABLE_SCHEMA'] === $database);
            $actual[$name] ??= ['name' => $name,'columns' => [],'schema' => '@database','table' => $row['REFERENCED_TABLE_NAME'],'target' => [],'update' => $row['UPDATE_RULE'],'delete' => $row['DELETE_RULE']];
            $actual[$name]['columns'][] = $row['COLUMN_NAME']; $actual[$name]['target'][] = $row['REFERENCED_COLUMN_NAME'];
        }
        usort($expected, static fn ($a, $b) => strcmp($a['name'], $b['name']));
        AssignmentOrderSelectionSchemaValues::require(array_values($actual) === $expected);
    }
}
