<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Read-only predecessor contract from MIGRATION-PROCESS-001, not a source migrator. */
final class MariaDbAssignmentOrderIdentityRegistrySourceShape
{
    public static function compatible(\mysqli $db,string $prefix): bool
    {
        if (!ProductionProcessSchemaMigration::isInstallationCasesCompatible($db,$prefix)) { return false; }
        $table=$prefix.'fm2_assignment_orders';
        $properties=MariaDbSchemaInspector::tableProperties($db,$table);
        if ($properties===null || $properties['ENGINE']!=='InnoDB' || !str_starts_with((string)$properties['TABLE_COLLATION'],'utf8mb4_')) { return false; }
        $rows=MariaDbSchemaInspector::columns($db,$table);$actual=[];
        foreach ($rows as $row) {
            $character=str_starts_with(strtolower($row['COLUMN_TYPE']),'varchar');
            if ($character ? ($row['CHARACTER_SET_NAME']!=='utf8mb4' || !str_starts_with((string)$row['COLLATION_NAME'],'utf8mb4_'))
                : ([$row['CHARACTER_SET_NAME'],$row['COLLATION_NAME']]!==[null,null])) { return false; }
            $actual[]=[$row['COLUMN_NAME'],strtolower($row['COLUMN_TYPE']),$row['IS_NULLABLE'],$row['EXTRA']];
        }
        if ($actual!==self::columns()) { return false; }
        $actual=[];
        foreach (MariaDbSchemaInspector::indexes($db,$table) as $row) {
            $actual[]=[$row['INDEX_NAME']==='PRIMARY'?'primary':((int)$row['NON_UNIQUE']===0?'unique':'index'),$row['COLUMNS']];
        }
        $expected=[['primary','id:FULL:A:NO'],['unique','installation_case_id:FULL:A:NO,version_no:FULL:A:NO'],
            ['index','installation_case_id:FULL:A:NO,status:FULL:A:NO'],['index','previous_assignment_order_id:FULL:A:NO']];
        sort($actual);sort($expected);if ($actual!==$expected) { return false; }
        $database=MariaDbAssignmentOrderIdentityRegistrySql::one($db,'SELECT DATABASE() n')['n'];
        $actual=array_map(static fn($row)=>array_slice($row,1),MariaDbAssignmentOrderIdentityRegistryCatalog::foreignKeys($db,$table));
        $expected=[['installation_case_id',$database,$prefix.'fm2_installation_cases','id','RESTRICT','RESTRICT'],
            ['previous_assignment_order_id',$database,$table,'id','RESTRICT','RESTRICT']];
        sort($actual);sort($expected);
        return $actual===$expected && MariaDbSchemaInspector::checks($db,$table)===[];
    }

    private static function columns(): array
    {
        return [
            ['id','bigint(20) unsigned','NO','auto_increment'],
            ['installation_case_id','bigint(20) unsigned','NO',''],
            ['version_no','smallint(5) unsigned','NO',''],
            ['kind','varchar(40)','NO',''],['status','varchar(40)','NO',''],['order_date','date','NO',''],
            ['registration_number','varchar(120)','YES',''],['registered_at','varchar(40)','YES',''],
            ['registration_actor_type','varchar(40)','YES',''],['registration_actor_id','varchar(120)','YES',''],
            ['registration_source','varchar(40)','YES',''],['external_registration_id','varchar(120)','YES',''],
            ['control_engineer_user_id','bigint(20) unsigned','NO',''],
            ['control_engineer_fio_snapshot','varchar(300)','NO',''],['control_engineer_position_snapshot','varchar(300)','NO',''],
            ['organization_form','varchar(40)','NO',''],['previous_assignment_order_id','bigint(20) unsigned','YES',''],
            ['object_address_snapshot','varchar(500)','NO',''],['entrance_snapshot','varchar(80)','NO',''],
            ['object_registration_number_snapshot','varchar(120)','NO',''],
            ['planned_start_date_snapshot','date','NO',''],['planned_finish_date_snapshot','date','NO',''],
            ['pto_act_date_snapshot','date','YES',''],['prepared_at','varchar(40)','NO',''],
            ['prepared_by_user_id','bigint(20) unsigned','NO',''],
        ];
    }
}
