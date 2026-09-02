<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** One structured source for literal-v10 DDL and fingerprint manifests. */
final class InstallationCompletionDefinitionSchemaMigration
{
    public const ROOT = 'fm2_pilot_completion_facts';
    public const CORRECTIONS = 'fm2_pilot_completion_fact_corrections';

    public static function definitions(string $prefix, string $collation): array
    {
        $schemas = self::schemas($prefix);
        $result = [];
        foreach ($schemas as $name => $schema) {
            $result[$name] = [
                'ddl' => self::renderTable($prefix . $name, $schema, $collation),
                'manifest' => self::manifest($schema, $collation),
            ];
        }
        return $result;
    }

    public static function removeRedundantSupportingIndex(\mysqli $connection, string $table): void
    {
        $state = (int) $connection->query(
            'SELECT @@SESSION.FOREIGN_KEY_CHECKS value',
        )->fetch_assoc()['value'];
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        try {
            $connection->query(
                "ALTER TABLE `{$table}` DROP INDEX `fk_completion_correction_previous`",
            );
        } finally {
            $connection->query('SET FOREIGN_KEY_CHECKS=' . $state);
        }
    }

    private static function schemas(string $prefix): array
    {
        $column = static fn(string $name, string $type, bool $nullable = false,
            ?string $default = null, string $extra = ''): array =>
            compact('name', 'type', 'nullable', 'default', 'extra');
        $index = static fn(string $name, bool $unique, array $columns): array =>
            compact('name', 'unique', 'columns');
        return [
            self::ROOT => [
                'columns' => [
                    $column('id', 'BIGINT UNSIGNED', false, null, 'auto_increment'),
                    $column('installation_case_id', 'BIGINT UNSIGNED'),
                    $column('fact_type', "ENUM('pto_act','declaration')"),
                    $column('fact_date', 'DATE'),
                    $column('details', 'VARCHAR(500)', false, "''"),
                    $column('recorded_at', 'VARCHAR(40)'),
                    $column('recorded_by_user_id', 'BIGINT UNSIGNED'),
                ],
                'indexes' => [
                    $index('PRIMARY', true, ['id']),
                    $index('uq_case_fact', true, ['installation_case_id', 'fact_type']),
                    $index('installation_case_id', false, ['installation_case_id', 'id']),
                ],
                'foreignKeys' => [], 'checks' => [],
            ],
            self::CORRECTIONS => [
                'columns' => [
                    $column('id', 'BIGINT UNSIGNED', false, null, 'auto_increment'),
                    $column('root_fact_id', 'BIGINT UNSIGNED'),
                    $column('version_no', 'INT UNSIGNED'),
                    $column('previous_correction_id', 'BIGINT UNSIGNED', true),
                    $column('previous_version_no', 'INT UNSIGNED', true),
                    $column('fact_date', 'DATE'), $column('reason', 'VARCHAR(1000)'),
                    $column('recorded_at', 'VARCHAR(40)'),
                    $column('recorded_by_user_id', 'BIGINT UNSIGNED'),
                ],
                'indexes' => [
                    $index('PRIMARY', true, ['id']),
                    $index('uq_root_version', true, ['root_fact_id', 'version_no']),
                    $index('uq_previous_correction', true, ['previous_correction_id']),
                    $index('uq_correction_identity', true, ['id', 'root_fact_id', 'version_no']),
                    $index('root_history', false, ['root_fact_id', 'id']),
                ],
                'foreignKeys' => [
                    ['name'=>'fk_completion_correction_root','columns'=>['root_fact_id'],
                        'target'=>$prefix.self::ROOT,'targetColumns'=>['id']],
                    ['name'=>'fk_completion_correction_previous',
                        'columns'=>['previous_correction_id','root_fact_id','previous_version_no'],
                        'target'=>$prefix.self::CORRECTIONS,
                        'targetColumns'=>['id','root_fact_id','version_no']],
                ],
                'checks' => [
                    'version_no>=1',
                    'version_no=1 AND previous_correction_id IS NULL AND previous_version_no IS NULL '
                        .'OR version_no>1 AND previous_correction_id IS NOT NULL AND previous_version_no=version_no-1',
                    'char_length(trim(reason)) BETWEEN 1 AND 1000',
                ],
            ],
        ];
    }

    private static function renderTable(string $table, array $schema, string $collation): string
    {
        $parts = [];
        foreach ($schema['columns'] as $column) $parts[] = self::renderColumn($column);
        foreach ($schema['indexes'] as $index) $parts[] = self::renderIndex($index);
        foreach ($schema['foreignKeys'] as $foreignKey) {
            $parts[] = 'CONSTRAINT ' . $foreignKey['name'] . ' FOREIGN KEY('
                . implode(',', $foreignKey['columns']) . ') REFERENCES `'
                . $foreignKey['target'] . '`(' . implode(',', $foreignKey['targetColumns']) . ')';
        }
        foreach ($schema['checks'] as $check) $parts[] = 'CHECK(' . $check . ')';
        return 'CREATE TABLE `' . $table . '`(' . implode(',', $parts)
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`";
    }

    private static function renderColumn(array $column): string
    {
        $sql = $column['name'] . ' ' . $column['type']
            . ($column['nullable'] ? ' DEFAULT NULL' : ' NOT NULL');
        if ($column['default'] !== null) $sql .= ' DEFAULT ' . $column['default'];
        if ($column['extra'] === 'auto_increment') $sql .= ' AUTO_INCREMENT';
        return $sql;
    }

    private static function renderIndex(array $index): string
    {
        $head = $index['name'] === 'PRIMARY' ? 'PRIMARY KEY'
            : ($index['unique'] ? 'UNIQUE KEY ' : 'KEY ') . $index['name'];
        return $head . '(' . implode(',', $index['columns']) . ')';
    }

    private static function manifest(array $schema, string $collation): array
    {
        return MariaDbInstallationCompletionSchemaFingerprint::manifest($schema, $collation);
    }
}
