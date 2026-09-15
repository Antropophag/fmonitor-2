<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class BitrixOrderDocumentLinksSchemaMigration
{
    public static function apply(\mysqli $db,string $prefix=''): array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);$legacy=$prefix.'fm_maintable';$links=$prefix.'fm2_bitrix_order_document_links';$changed=[];
        if (!MariaDbSchemaInspector::tableExists($db,$legacy)) throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT');
        $column=array_values(array_filter(MariaDbSchemaInspector::columns($db,$legacy),static fn(array $row):bool=>$row['COLUMN_NAME']==='zavnumber'));
        if ($column===[]) {$db->query("ALTER TABLE `{$legacy}` ADD zavnumber VARCHAR(120) NULL COLLATE utf8mb4_bin AFTER entrance");$changed[]='zavnumber';}
        else { $row=$column[0]; if(strtolower((string)$row['COLUMN_TYPE'])!=='varchar(120)'||$row['IS_NULLABLE']!=='YES'||$row['COLLATION_NAME']!=='utf8mb4_bin')throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT'); }
        if (!MariaDbSchemaInspector::tableExists($db,$links)) {
            $db->query("CREATE TABLE `{$links}`(source_folder_id BIGINT UNSIGNED NOT NULL,source_folder_name VARCHAR(255) NOT NULL,order_number VARCHAR(120) COLLATE utf8mb4_bin NOT NULL,url VARCHAR(2048) NOT NULL,UNIQUE KEY uq_folder_order(source_folder_id,order_number),KEY ix_order(order_number)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");$changed[]=$links;
        } elseif (!self::linksReady($db,$links)) throw new \RuntimeException('SCHEMA_MIGRATION_CONFLICT');
        return ['applied'=>$changed!==[],'schemaVersion'=>27,'changed'=>$changed];
    }
    private static function linksReady(\mysqli$db,string$table):bool
    {
        $columns=array_map(static fn(array$row):array=>[$row['COLUMN_NAME'],strtolower((string)$row['COLUMN_TYPE']),$row['IS_NULLABLE'],$row['COLLATION_NAME']],MariaDbSchemaInspector::columns($db,$table));
        $expected=[['source_folder_id','bigint(20) unsigned','NO',null],['source_folder_name','varchar(255)','NO','utf8mb4_bin'],['order_number','varchar(120)','NO','utf8mb4_bin'],['url','varchar(2048)','NO','utf8mb4_bin']];
        $indexes=[];foreach(MariaDbSchemaInspector::indexes($db,$table)as$row)$indexes[$row['INDEX_NAME']]=[(int)$row['NON_UNIQUE'],$row['COLUMNS']];
        return $columns===$expected&&($indexes['uq_folder_order']??null)===[0,'source_folder_id:FULL:A:NO,order_number:FULL:A:NO']&&($indexes['ix_order']??null)===[1,'order_number:FULL:A:NO'];
    }
}
