<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Exact literal-v11 classification-provenance table definition. */
final class ClassificationProvenanceDefinitionSchemaMigration
{
    public const TABLE = 'fm2_migration_classification_provenance';

    /** @return array{ddl:string,columns:list<array<string,mixed>>,indexes:list<array<string,mixed>>} */
    public static function definition(string $prefix, string $collation): array
    {
        $table = $prefix . self::TABLE;

        return [
            'ddl' => "CREATE TABLE `{$table}`("
                . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . 'output_kind VARCHAR(40) NOT NULL,'
                . 'legacy_object_id BIGINT UNSIGNED NOT NULL,'
                . 'output_id BIGINT UNSIGNED NOT NULL,'
                . 'source_cutoff_at DATETIME NOT NULL,'
                . 'classification_version VARCHAR(80) NOT NULL,'
                . 'category VARCHAR(40) NOT NULL,'
                . 'reason_codes_json TEXT NOT NULL,'
                . 'classification_sha256 CHAR(64) NOT NULL,'
                . 'created_at DATETIME NOT NULL,'
                . 'PRIMARY KEY(id),'
                . 'UNIQUE KEY uq_classification_output(output_kind,output_id),'
                . 'KEY ix_classification_legacy_object(legacy_object_id)'
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`",
            'columns' => [
                self::column('id', 'bigint(20) unsigned', null, null, 'auto_increment'),
                self::column('output_kind', 'varchar(40)', 'utf8mb4', $collation),
                self::column('legacy_object_id', 'bigint(20) unsigned'),
                self::column('output_id', 'bigint(20) unsigned'),
                self::column('source_cutoff_at', 'datetime'),
                self::column('classification_version', 'varchar(80)', 'utf8mb4', $collation),
                self::column('category', 'varchar(40)', 'utf8mb4', $collation),
                self::column('reason_codes_json', 'text', 'utf8mb4', $collation),
                self::column('classification_sha256', 'char(64)', 'utf8mb4', $collation),
                self::column('created_at', 'datetime'),
            ],
            'indexes' => [
                ['identity'=>'PRIMARY','nonUnique'=>0,'columns'=>['id']],
                ['identity'=>'SECONDARY','nonUnique'=>0,'columns'=>['output_kind','output_id']],
                ['identity'=>'SECONDARY','nonUnique'=>1,'columns'=>['legacy_object_id']],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private static function column(
        string $name,
        string $type,
        ?string $charset = null,
        ?string $collation = null,
        string $extra = '',
    ): array {
        return [
            'name'=>$name, 'type'=>$type, 'nullable'=>'NO', 'default'=>null,
            'extra'=>$extra, 'generated'=>'NEVER', 'generationExpression'=>null,
            'charset'=>$charset, 'collation'=>$collation,
        ];
    }
}
