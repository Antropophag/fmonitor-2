<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact metadata family; the engine is its only execution owner. */
final class AssignmentOrderIdentityRegistryDefinitionSchemaMigration
{
    public const REGISTRY='fm2_assignment_order_identities';
    public const RECEIPTS='fm2_assignment_order_id_receipts';

    public static function columns(string $base): array
    {
        return $base===self::REGISTRY ? [
            ['assignment_order_id','bigint unsigned','auto_increment',false],
            ['installation_case_id','bigint unsigned','',false],
            ['order_version','smallint unsigned','',false],
            ['source_kind','varchar(24)','',true],
            ['allocated_at_utc','datetime(6)','',false],
        ] : [
            ['singleton_id','tinyint unsigned','',false],
            ['format_version','smallint unsigned','',false],
            ['legacy_max_id','bigint unsigned','',false],
            ['legacy_next_id','bigint unsigned','',false],
            ['preserved_next_id','bigint unsigned','',false],
            ['legacy_row_count','bigint unsigned','',false],
            ['legacy_tuple_sha256','char(64)','',true],
            ['legacy_prepared_sha256','char(64)','',true],
        ];
    }

    public static function indexes(string $base): array
    {
        return $base===self::REGISTRY ? [
            'PRIMARY'=>[0,['assignment_order_id']],
            'fm2_aoir_uq_case_version'=>[0,['installation_case_id','order_version']],
            'fm2_aoir_uq_id_case'=>[0,['assignment_order_id','installation_case_id']],
            'fm2_aoir_ix_case_source'=>[1,['installation_case_id','source_kind','order_version']],
        ] : ['PRIMARY'=>[0,['singleton_id']]];
    }

    public static function checks(string $base): array
    {
        return $base===self::REGISTRY ? [
            'fm2_aoir_ck_case'=>'installation_case_id BETWEEN 1 AND 9223372036854775807',
            'fm2_aoir_ck_version'=>'order_version BETWEEN 1 AND 65535',
            'fm2_aoir_ck_source'=>"source_kind IN ('legacy_order','selection')",
        ] : [
            'fm2_aoir_receipt_ck_one'=>'singleton_id=1 AND format_version=1',
            'fm2_aoir_receipt_ck_bounds'=>'legacy_max_id BETWEEN 0 AND 9223372036854775807 AND legacy_next_id BETWEEN 1 AND 9223372036854775807 AND preserved_next_id BETWEEN 1 AND 9223372036854775807 AND legacy_row_count BETWEEN 0 AND 9223372036854775807',
            'fm2_aoir_receipt_ck_frontier'=>'preserved_next_id>=legacy_next_id AND preserved_next_id>legacy_max_id',
            'fm2_aoir_receipt_ck_tuple'=>"legacy_tuple_sha256 REGEXP '^[0-9a-f]{64}$'",
            'fm2_aoir_receipt_ck_prepared'=>"legacy_prepared_sha256 REGEXP '^[0-9a-f]{64}$'",
        ];
    }

    public static function definitions(string $prefix, string $collation): array
    {
        AssignmentOrderIdentityRegistryValues::prefix($prefix);
        $definitions=[];
        foreach ([self::REGISTRY,self::RECEIPTS] as $base) {
            $parts=[];
            foreach (self::columns($base) as [$name,$type,$extra,$ascii]) {
                $parts[]="`$name` $type".($ascii?' CHARACTER SET ascii COLLATE ascii_bin':'')." NOT NULL $extra";
            }
            foreach (self::indexes($base) as $name=>[$nonUnique,$columns]) {
                $key=$name==='PRIMARY'?'PRIMARY KEY':($nonUnique?'KEY':'UNIQUE KEY')." `{$prefix}{$name}`";
                $parts[]=$key.' (`'.implode('`,`',$columns).'`)';
            }
            foreach (self::checks($base) as $name=>$predicate) { $parts[]="CONSTRAINT `{$prefix}{$name}` CHECK ($predicate)"; }
            if ($base===self::REGISTRY) {
                $parts[]="CONSTRAINT `{$prefix}fm2_aoir_fk_case` FOREIGN KEY (installation_case_id) REFERENCES `{$prefix}fm2_installation_cases`(id) ON UPDATE RESTRICT ON DELETE RESTRICT";
            }
            $definitions[$base]="CREATE TABLE `{$prefix}{$base}` (".implode(',',$parts).") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `$collation`";
        }
        return $definitions;
    }
}
