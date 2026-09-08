<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Additive successor for honest unknown employed-from selection snapshots. */
final class AssignmentOrderSelectionUnknownEmploymentSchemaMigration
{
    private const ADDED=['employment_proof_kind','authority_system_snapshot','delivery_system_snapshot','delivery_person_id_snapshot','reconciliation_state_snapshot','full_snapshot_json'];
    public static function apply(\mysqli$db,string$prefix=''):array
    {
        self::prefix($prefix);if(self::isReady($db,$prefix))return['applied'=>false];
        $table=$prefix.'fm2_assignment_order_selection_members';$columns=self::columns($db,$table);
        if(!isset($columns['employed_from_snapshot'])||$columns['employed_from_snapshot']['IS_NULLABLE']!=='NO'||array_intersect(self::ADDED,array_keys($columns))!==[])return['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT'];
        $check=$prefix.'fm2_aosm_ck_unknown';$sql="ALTER TABLE `{$table}` MODIFY employed_from_snapshot DATE NULL,ADD employment_proof_kind VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER workforce_source_updated_at_snapshot,ADD authority_system_snapshot VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER employment_proof_kind,ADD delivery_system_snapshot VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER authority_system_snapshot,ADD delivery_person_id_snapshot BIGINT UNSIGNED NULL AFTER delivery_system_snapshot,ADD reconciliation_state_snapshot VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER delivery_person_id_snapshot,ADD full_snapshot_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL AFTER reconciliation_state_snapshot,ADD CONSTRAINT `{$check}` CHECK (employed_from_snapshot IS NOT NULL OR (employment_proof_kind='full_current' AND authority_system_snapshot='1c_zup' AND delivery_system_snapshot='bitrix24' AND delivery_person_id_snapshot BETWEEN 1 AND 9223372036854775807 AND reconciliation_state_snapshot='delivered' AND JSON_VALID(full_snapshot_json)))";
        try{if(!$db->query($sql)||!self::isReady($db,$prefix))throw new \RuntimeException();return['applied'=>true];}catch(\Throwable){throw new \RuntimeException('Assignment order unknown-employment schema unavailable.');}
    }
    public static function isReady(\mysqli$db,string$prefix=''):bool
    {
        self::prefix($prefix);try{$collation=MariaDbAssignmentOrderIdentityRegistryCatalog::collation($db);if($collation===null)return false;
            foreach(AssignmentOrderSelectionDefinitionSchemaMigration::tables($prefix)as$table){$expected=str_ends_with($table['name'],'fm2_assignment_order_selection_members')?self::definition($prefix,$collation):$table;if(MariaDbAssignmentOrderSelectionSchemaCatalog::shape($db,$expected,$collation)===null)return false;}return true;
        }catch(\Throwable){return false;}
    }
    public static function definition(string$prefix,string$collation):array
    {
        $table=AssignmentOrderSelectionMembersDefinitionSchemaMigration::table();foreach($table['columns']as&$column)if($column['name']==='employed_from_snapshot')$column['nullable']=true;unset($column);
        $table['columns'][]=['name'=>'employment_proof_kind','type'=>'varchar(20)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'];
        $table['columns'][]=['name'=>'authority_system_snapshot','type'=>'varchar(40)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'];
        $table['columns'][]=['name'=>'delivery_system_snapshot','type'=>'varchar(40)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'];
        $table['columns'][]=['name'=>'delivery_person_id_snapshot','type'=>'bigint unsigned','nullable'=>true,'default'=>null,'extra'=>'','charset'=>null,'collation'=>null];
        $table['columns'][]=['name'=>'reconciliation_state_snapshot','type'=>'varchar(40)','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'ascii','collation'=>'ascii_bin'];
        $table['columns'][]=['name'=>'full_snapshot_json','type'=>'longtext','nullable'=>true,'default'=>null,'extra'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_bin'];
        $table['checks'][]=['name'=>'@prefixfm2_aosm_ck_unknown','expression'=>"employed_from_snapshot IS NOT NULL OR (employment_proof_kind='full_current' AND authority_system_snapshot='1c_zup' AND delivery_system_snapshot='bitrix24' AND delivery_person_id_snapshot BETWEEN 1 AND 9223372036854775807 AND reconciliation_state_snapshot='delivered' AND JSON_VALID(full_snapshot_json))"];
        return json_decode(str_replace('@prefix',$prefix,json_encode($table,JSON_THROW_ON_ERROR)),true,512,JSON_THROW_ON_ERROR);
    }
    private static function columns(\mysqli$db,string$table):array
    {$s=$db->prepare('SELECT COLUMN_NAME,IS_NULLABLE,COLUMN_TYPE,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');if(!$s||!$s->execute([$table]))throw new \RuntimeException();$out=[];foreach($s->get_result()->fetch_all(MYSQLI_ASSOC)as$r)$out[$r['COLUMN_NAME']]=$r;$s->close();return$out;}
    private static function prefix(string$p):void{if(PHP_INT_SIZE!==8||preg_match('/^[A-Za-z0-9_]{0,25}$/D',$p)!==1)throw new \InvalidArgumentException('Invalid selection table prefix.');}
}
