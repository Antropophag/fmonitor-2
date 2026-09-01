<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

/** Literal table catalogue and shared metadata builders for inspection evidence v8. */
final class InspectionEvidenceDefinitionSchemaMigration
{
    private const TABLES = [
        'fm2_checklist_revisions',
        'fm2_checklist_operations',
        'fm2_checklist_operation_installers',
        'fm2_checklist_photos',
    ];

    public static function tables(): array
    {
        return self::TABLES;
    }

    public static function definitions(string $prefix, string $collation): array
    {
        return [
            self::TABLES[0] => self::revisions($prefix, $collation),
            self::TABLES[1] => InspectionEvidenceOperationDefinitionSchemaMigration::operations($prefix, $collation),
            self::TABLES[2] => InspectionEvidenceOperationDefinitionSchemaMigration::installers($prefix, $collation),
            self::TABLES[3] => self::photos($prefix, $collation),
        ];
    }

    public static function column(
        string $name,
        string $type,
        string $nullable,
        ?string $default = null,
        string $extra = '',
        bool $character = false,
        ?string $collation = null,
    ): array {
        return [
            'name'=>$name, 'type'=>$type, 'nullable'=>$nullable, 'default'=>$default,
            'extra'=>$extra, 'generated'=>'NEVER', 'generationExpression'=>null,
            'charset'=>$character ? 'utf8mb4' : null,
            'collation'=>$character ? $collation : null,
        ];
    }

    public static function index(string $name, int $nonUnique, int $sequence, string $column): array
    {
        return [
            'name'=>$name, 'nonUnique'=>$nonUnique, 'sequence'=>$sequence, 'column'=>$column,
            'subPart'=>null, 'collation'=>'A', 'type'=>'BTREE', 'ignored'=>'NO',
        ];
    }

    public static function table(string $prefix, string $name): string
    {
        return '`' . $prefix . $name . '`';
    }

    public static function character(string $collation): string
    {
        return " CHARACTER SET utf8mb4 COLLATE `{$collation}`";
    }

    public static function tail(string $collation): string
    {
        return " ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE `{$collation}`";
    }

    private static function revisions(string $prefix, string $collation): array
    {
        $table = self::table($prefix, self::TABLES[0]);
        $character = self::character($collation);
        return [
            'ddl' => "CREATE TABLE {$table}("
                . 'installation_case_id BIGINT UNSIGNED NOT NULL,'
                . 'revision_no BIGINT UNSIGNED NOT NULL DEFAULT 0,'
                . "updated_at VARCHAR(40){$character} NOT NULL,"
                . 'PRIMARY KEY(installation_case_id))' . self::tail($collation),
            'final' => ['columns'=>[
                self::column('installation_case_id','bigint(20) unsigned','NO'),
                self::column('revision_no','bigint(20) unsigned','NO','0'),
                self::column('updated_at','varchar(40)','NO',null,'',true,$collation),
            ], 'indexes'=>[self::index('PRIMARY',0,1,'installation_case_id')]],
        ];
    }

    private static function photos(string $prefix, string $collation): array
    {
        $table = self::table($prefix, self::TABLES[3]);
        $char = self::character($collation);
        $columns = [
            self::column('id','bigint(20) unsigned','NO',null,'auto_increment'),
            self::column('installation_case_id','bigint(20) unsigned','NO'),
            self::column('section_id','tinyint(3) unsigned','NO'),
            self::column('upload_operation_id','char(36)','NO',null,'',true,$collation),
            self::column('sha256','char(64)','NO',null,'',true,$collation),
            self::column('mime_type','varchar(40)','NO',null,'',true,$collation),
            self::column('byte_size','int(10) unsigned','NO'),
            self::column('original_name','varchar(255)','NO',null,'',true,$collation),
            self::column('storage_name','varchar(255)','NO',null,'',true,$collation),
            self::column('actor_user_id','bigint(20) unsigned','NO'),
            self::column('device_time','varchar(40)','NO',null,'',true,$collation),
            self::column('server_received_at','varchar(40)','NO',null,'',true,$collation),
            self::column('revoked_at','varchar(40)','YES',null,'',true,$collation),
        ];
        $indexes = [
            self::index('PRIMARY',0,1,'id'),
            self::index('installation_case_id',0,1,'installation_case_id'),
            self::index('installation_case_id',0,2,'section_id'),
            self::index('installation_case_id',0,3,'sha256'),
            self::index('installation_case_id_2',1,1,'installation_case_id'),
            self::index('installation_case_id_2',1,2,'section_id'),
            self::index('upload_operation_id',0,1,'upload_operation_id'),
        ];
        $ddl = "CREATE TABLE {$table}(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,"
            . 'installation_case_id BIGINT UNSIGNED NOT NULL,section_id TINYINT UNSIGNED NOT NULL,'
            . "upload_operation_id CHAR(36){$char} NOT NULL,sha256 CHAR(64){$char} NOT NULL,"
            . "mime_type VARCHAR(40){$char} NOT NULL,byte_size INT UNSIGNED NOT NULL,"
            . "original_name VARCHAR(255){$char} NOT NULL,storage_name VARCHAR(255){$char} NOT NULL,"
            . "actor_user_id BIGINT UNSIGNED NOT NULL,device_time VARCHAR(40){$char} NOT NULL,"
            . "server_received_at VARCHAR(40){$char} NOT NULL,revoked_at VARCHAR(40){$char} NULL,"
            . 'PRIMARY KEY(id),UNIQUE KEY upload_operation_id(upload_operation_id),'
            . 'UNIQUE KEY installation_case_id(installation_case_id,section_id,sha256),'
            . 'KEY installation_case_id_2(installation_case_id,section_id))' . self::tail($collation);
        return ['ddl'=>$ddl, 'final'=>['columns'=>$columns, 'indexes'=>$indexes]];
    }
}
