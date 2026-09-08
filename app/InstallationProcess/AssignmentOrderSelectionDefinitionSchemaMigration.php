<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class AssignmentOrderSelectionDefinitionSchemaMigration
{
    public static function tables(string $prefix): array
    {
        $tables = [AssignmentOrderSelectionSelectionsDefinitionSchemaMigration::table(),
            AssignmentOrderSelectionMembersDefinitionSchemaMigration::table(), AssignmentOrderSelectionRequestsDefinitionSchemaMigration::table(),
            AssignmentOrderSelectionEventsDefinitionSchemaMigration::table(), AssignmentOrderSelectionAuditsDefinitionSchemaMigration::table()];
        return json_decode(str_replace('@prefix', $prefix, AssignmentOrderSelectionSchemaValues::json($tables)), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function sql(array $table, string $collation): string
    {
        $definitions = [];
        foreach ($table['columns'] as $column) {
            $sql = self::identifier($column['name']) . ' ' . $column['type'];
            if ($column['charset'] !== null) { $sql .= ' CHARACTER SET ' . $column['charset'] . ' COLLATE ' . str_replace('@collation', $collation, $column['collation']); }
            $definitions[] = $sql . ($column['nullable'] ? ' NULL' : ' NOT NULL') . ' ' . $column['extra'];
        }
        foreach ($table['indexes'] as $key) {
            $head = $key['name'] === 'PRIMARY' ? 'PRIMARY KEY' : ($key['unique'] ? 'UNIQUE KEY ' : 'KEY ') . self::identifier($key['name']);
            $definitions[] = $head . ' (' . self::columns($key['columns']) . ')';
        }
        foreach ($table['foreignKeys'] as $key) {
            $definitions[] = 'CONSTRAINT ' . self::identifier($key['name']) . ' FOREIGN KEY (' . self::columns($key['columns']) . ') REFERENCES '
                . self::identifier($key['table']) . ' (' . self::columns($key['target']) . ') ON UPDATE ' . $key['update'] . ' ON DELETE ' . $key['delete'];
        }
        foreach ($table['checks'] as $check) { $definitions[] = 'CONSTRAINT ' . self::identifier($check['name']) . ' CHECK (' . $check['expression'] . ')'; }
        return 'CREATE TABLE ' . self::identifier($table['name']) . ' (' . implode(',', $definitions) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=' . $collation;
    }

    private static function identifier(string $name): string { return '`' . $name . '`'; }
    private static function columns(array $names): string { return implode(',', array_map(self::identifier(...), $names)); }
}
