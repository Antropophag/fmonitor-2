<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Public literal-v7 owner of the checklist-template schema family. */
final class ChecklistTemplateSchemaMigration
{
    private const TABLES = [
        'fm2_checklist_template_snapshots',
        'fm2_checklist_template_associations',
    ];

    public static function apply(\mysqli $connection, string $tablePrefix = ''): array
    {
        [$definitions, $missing, $conflicting] = self::inspect($connection, $tablePrefix);
        if ($conflicting !== []) {
            return [
                'applied' => false,
                'schemaVersion' => 7,
                'reason' => 'SCHEMA_MIGRATION_CONFLICT',
                'conflictingTables' => $conflicting,
            ];
        }

        $created = [];
        foreach (self::TABLES as $logicalName) {
            $table = $tablePrefix . $logicalName;
            if (in_array($table, $missing, true)) {
                $connection->query($definitions[$logicalName]['ddl']);
                $created[] = $table;
            }
        }

        return ['applied' => $created !== [], 'schemaVersion' => 7, 'tablesCreated' => $created];
    }

    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool
    {
        [, $missing, $conflicting] = self::inspect($connection, $tablePrefix);
        return $missing === [] && $conflicting === [];
    }

    private static function inspect(\mysqli $connection, string $tablePrefix): array
    {
        self::assertPrefix($tablePrefix);
        $collation = IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);
        $definitions = self::definitions($tablePrefix, $collation);
        $missing = [];
        $conflicting = [];
        foreach (self::TABLES as $logicalName) {
            $table = $tablePrefix . $logicalName;
            if (!MariaDbSchemaInspector::tableExists($connection, $table)) {
                $missing[] = $table;
            } elseif (!self::matches($connection, $table, $definitions[$logicalName]['manifest'], $collation)) {
                $conflicting[] = $table;
            }
        }
        return [$definitions, $missing, $conflicting];
    }

    private static function assertPrefix(string $tablePrefix): void
    {
        if (strlen($tablePrefix) > 25 || preg_match('/^[A-Za-z0-9_]*$/D', $tablePrefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    private static function definitions(string $prefix, string $collation): array
    {
        $tail = " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`";
        $snapshots = $prefix . self::TABLES[0];
        $associations = $prefix . self::TABLES[1];
        $columns = static fn (array $rows): array => array_map(
            static fn (array $row): string => implode('|', $row),
            $rows,
        );
        $c = static fn (string $name, string $type, bool $character = false, string $extra = ''): array => [
            $name, $type, 'NO', 'NULL', $extra, 'NEVER', 'NULL',
            $character ? 'utf8mb4' : 'NULL', $character ? $collation : 'NULL',
        ];

        return [
            self::TABLES[0] => [
                'ddl' => "CREATE TABLE `{$snapshots}`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,snapshot_version VARCHAR(80) NOT NULL,captured_at DATETIME NOT NULL,valid_from DATETIME NOT NULL,validity_scope VARCHAR(120) NOT NULL,source_label VARCHAR(160) NOT NULL,content_sha256 CHAR(64) NOT NULL,payload_json LONGTEXT NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_hash(content_sha256),UNIQUE KEY uq_valid_from(valid_from)){$tail}",
                'manifest' => [
                    'columns' => $columns([$c('id', 'bigint(20) unsigned', false, 'auto_increment'), $c('snapshot_version', 'varchar(80)', true), $c('captured_at', 'datetime'), $c('valid_from', 'datetime'), $c('validity_scope', 'varchar(120)', true), $c('source_label', 'varchar(160)', true), $c('content_sha256', 'char(64)', true), $c('payload_json', 'longtext', true), $c('created_at', 'datetime')]),
                    'indexes' => ['PRIMARY|0|BTREE|id', 'uq_hash|0|BTREE|content_sha256', 'uq_valid_from|0|BTREE|valid_from'],
                ],
            ],
            self::TABLES[1] => [
                'ddl' => "CREATE TABLE `{$associations}`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,association_version VARCHAR(80) NOT NULL,subject_kind VARCHAR(40) NOT NULL,subject_id VARCHAR(160) NOT NULL,effective_at DATETIME NOT NULL,template_snapshot_id BIGINT UNSIGNED NOT NULL,template_snapshot_version VARCHAR(80) NOT NULL,template_content_sha256 CHAR(64) NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_subject(subject_kind,subject_id),KEY snapshot_id(template_snapshot_id)){$tail}",
                'manifest' => [
                    'columns' => $columns([$c('id', 'bigint(20) unsigned', false, 'auto_increment'), $c('association_version', 'varchar(80)', true), $c('subject_kind', 'varchar(40)', true), $c('subject_id', 'varchar(160)', true), $c('effective_at', 'datetime'), $c('template_snapshot_id', 'bigint(20) unsigned'), $c('template_snapshot_version', 'varchar(80)', true), $c('template_content_sha256', 'char(64)', true), $c('created_at', 'datetime')]),
                    'indexes' => ['PRIMARY|0|BTREE|id', 'snapshot_id|1|BTREE|template_snapshot_id', 'uq_subject|0|BTREE|subject_kind,subject_id'],
                ],
            ],
        ];
    }

    private static function matches(\mysqli $connection, string $table, array $manifest, string $collation): bool
    {
        $properties = MariaDbSchemaInspector::tableProperties($connection, $table);
        if ($properties === null || $properties['ENGINE'] !== 'InnoDB' || $properties['TABLE_COLLATION'] !== $collation) {
            return false;
        }
        $escaped = $connection->real_escape_string($table);
        $columns = $connection->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,IS_GENERATED,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC);
        $signatures = array_map(static fn (array $row): string => implode('|', [
            $row['COLUMN_NAME'], $row['COLUMN_TYPE'], $row['IS_NULLABLE'],
            $row['COLUMN_DEFAULT'] === null ? 'NULL' : (string) $row['COLUMN_DEFAULT'],
            $row['EXTRA'], $row['IS_GENERATED'],
            $row['GENERATION_EXPRESSION'] === null ? 'NULL' : (string) $row['GENERATION_EXPRESSION'],
            $row['CHARACTER_SET_NAME'] ?? 'NULL', $row['COLLATION_NAME'] ?? 'NULL',
        ]), $columns);
        if ($signatures !== $manifest['columns']) {
            return false;
        }
        $indexes = $connection->query("SELECT INDEX_NAME,NON_UNIQUE,INDEX_TYPE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' GROUP BY INDEX_NAME,NON_UNIQUE,INDEX_TYPE")->fetch_all(MYSQLI_ASSOC);
        $indexSignatures = array_map(static fn (array $row): string => implode('|', $row), $indexes);
        sort($indexSignatures, SORT_STRING);
        $expectedIndexes = $manifest['indexes'];
        sort($expectedIndexes, SORT_STRING);
        if ($indexSignatures !== $expectedIndexes) {
            return false;
        }
        $constraints = $connection->query("SELECT CONSTRAINT_NAME,CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}' AND CONSTRAINT_TYPE IN ('FOREIGN KEY','CHECK')")->fetch_all(MYSQLI_ASSOC);
        return $constraints === [];
    }
}
