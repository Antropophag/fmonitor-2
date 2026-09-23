<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Canonical v33 installer-leading lookup indexes for bounded assignment reads. */
final class InstallerAssignmentLookupIndexSchemaMigration
{
    private const INDEX='ix_installer_assignment';
    private const TABLES=['fm2_order_installers','fm2_assignment_order_selection_members'];

    public static function apply(\mysqli $db,string $prefix=''):array
    {
        IdentityAccessDefinitionSchemaMigration::assertPrefix($prefix);$states=[];
        foreach(self::TABLES as$table){$physical=$prefix.$table;if(!self::tableExists($db,$physical))return self::conflict($physical);$states[$table]=self::indexState($db,$physical);if($states[$table]==='conflict')return self::conflict($physical);}
        $changed=[];foreach($states as$table=>$state)if($state==='missing'){$physical=$prefix.$table;$db->query("ALTER TABLE `{$physical}` ADD INDEX `".self::INDEX.'` (installer_tab_id,assignment_order_id)');$changed[]=$physical;}
        foreach(self::TABLES as$table)if(self::indexState($db,$prefix.$table)!=='ready')throw new \RuntimeException('Installer assignment lookup index migration failed.');
        return['applied'=>$changed!==[],'schemaVersion'=>33,'indexesChanged'=>$changed];
    }

    private static function conflict(string $table):array
    {return['applied'=>false,'schemaVersion'=>33,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];}
    private static function tableExists(\mysqli$db,string$table):bool
    {$s=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$table]);return(int)$s->get_result()->fetch_column()===1;}
    private static function indexState(\mysqli$db,string$table):string
    {
        $s=$db->prepare("SELECT NON_UNIQUE,INDEX_TYPE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) columns_list,GROUP_CONCAT(COALESCE(CAST(SUB_PART AS CHAR),'FULL') ORDER BY SEQ_IN_INDEX) sub_parts FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? GROUP BY NON_UNIQUE,INDEX_TYPE");$s->execute([$table,self::INDEX]);$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);if($rows===[])return'missing';if(count($rows)!==1)return'conflict';$row=$rows[0];return[(int)$row['NON_UNIQUE'],strtoupper((string)$row['INDEX_TYPE']),$row['columns_list'],$row['sub_parts']] === [1,'BTREE','installer_tab_id,assignment_order_id','FULL,FULL']?'ready':'conflict';
    }
}
