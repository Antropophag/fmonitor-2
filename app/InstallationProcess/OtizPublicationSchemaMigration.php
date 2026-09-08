<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Canonical v20 owner of the complete legacy OTIZ family and publication receipt. */
final class OtizPublicationSchemaMigration
{
    private const RECEIPT='fm2_otiz_publications';

    public static function apply(\mysqli $db,string $prefix=''):array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);$table=$prefix.self::RECEIPT;
        if(self::isCompleteCompatible($db,$prefix))return['applied'=>false,'schemaVersion'=>20,'tablesCreated'=>[]];
        if(MariaDbSchemaInspector::tableExists($db,$table))return['applied'=>false,'schemaVersion'=>20,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];
        $manifests=self::manifests();unset($manifests[self::RECEIPT]);$present=array_filter(array_keys($manifests),fn(string$name):bool=>MariaDbSchemaInspector::tableExists($db,$prefix.$name));
        if($present!==[]){foreach($manifests as$name=>$manifest)if(!MariaDbOtizSchemaManifest::matches($db,$prefix.$name,$manifest[0],$manifest[1]))return['applied'=>false,'schemaVersion'=>20,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$prefix.$name]];}
        $legacy=PilotOtizSchemaMigration::apply($db,$prefix);
        if(($legacy['reason']??null)==='SCHEMA_MIGRATION_CONFLICT')return['applied'=>false,'schemaVersion'=>20,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$table]];
        $db->query("CREATE TABLE `{$table}`(snapshot_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,actor_user_id BIGINT UNSIGNED NOT NULL,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,manifest_version VARCHAR(40) NOT NULL,manifest_sha256 CHAR(64) NOT NULL,object_count INT UNSIGNED NOT NULL,allocation_count INT UNSIGNED NOT NULL,issue_count INT UNSIGNED NOT NULL,published_at VARCHAR(40) NOT NULL,UNIQUE KEY uq_actor_operation(actor_user_id,operation_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if(!self::isCompleteCompatible($db,$prefix))throw new \RuntimeException('OTIZ publication schema migration failed.');
        return['applied'=>true,'schemaVersion'=>20,'tablesCreated'=>[$table]];
    }

    public static function isCompleteCompatible(\mysqli $db,string $prefix=''):bool
    {
        try{
            MariaDbSchemaInspector::validateTablePrefix($prefix);foreach(self::manifests()as$table=>$manifest)if(!MariaDbOtizSchemaManifest::matches($db,$prefix.$table,$manifest[0],$manifest[1]))return false;return true;
        }catch(\Throwable){return false;}
    }

    public static function assertReady(\mysqli $db,string $prefix=''):void
    {
        if(!self::isCompleteCompatible($db,$prefix))throw new \RuntimeException('OTIZ publication schema unavailable.');
    }

    /** @return array<string,array{0:list<array{0:string,1:string,2:string,3:string}>,1:array<string,array{0:int,1:string}>}> */
    private static function manifests():array
    {
        $c=static fn(string$n,string$t,string$nullable='NO',string$extra=''):array=>[$n,$t,$nullable,$extra];$ix=static fn(string ...$names):string=>implode(',',array_map(fn(string$n):string=>$n.':FULL:A:NO',$names));
        return[
            'fm2_pilot_otiz_snapshots'=>[[ $c('id','bigint(20) unsigned','NO','auto_increment'),$c('report_date','date'),$c('status',"enum('draft','accepted')"),$c('previous_snapshot_id','bigint(20) unsigned','YES'),$c('rules_version','varchar(80)'),$c('calculated_at','varchar(40)'),$c('calculated_by_user_id','bigint(20) unsigned'),$c('accepted_at','varchar(40)','YES'),$c('accepted_by_user_id','bigint(20) unsigned','YES'),$c('total_pool_cents','bigint(20)'),$c('total_closed_cents','bigint(20)'),$c('total_available_cents','bigint(20)'),$c('content_hash','char(64)')],['PRIMARY'=>[0,$ix('id')],'report_date'=>[1,$ix('report_date','status')]]],
            'fm2_pilot_otiz_snapshot_objects'=>[[ $c('snapshot_id','bigint(20) unsigned'),$c('object_id','bigint(20) unsigned'),$c('regnumber','varchar(120)'),$c('address','varchar(500)'),$c('previous_progress_bp','int(11)'),$c('current_progress_bp','int(11)'),$c('progress_fact_date','date'),$c('premium_cents','bigint(20)'),$c('shaft_bp','int(11)'),$c('kss_bp','int(11)'),$c('accrued_cents','bigint(20)'),$c('fund_cents','bigint(20)'),$c('closed_before_cents','bigint(20)'),$c('remaining_cents','bigint(20)'),$c('pool_cents','bigint(20)'),$c('distributed_cents','bigint(20)'),$c('undistributed_cents','bigint(20)'),$c('calculation_state','varchar(40)'),$c('inputs_json','longtext')],['PRIMARY'=>[0,$ix('snapshot_id','object_id')]]],
            'fm2_pilot_otiz_snapshot_allocations'=>[[ $c('id','bigint(20) unsigned','NO','auto_increment'),$c('snapshot_id','bigint(20) unsigned'),$c('object_id','bigint(20) unsigned'),$c('tab_id','varchar(40)'),$c('full_name','varchar(300)'),$c('position_name','varchar(200)'),$c('contribution_bp','int(11)'),$c('base_ktu_bp','int(11)'),$c('adjustment_ktu_bp','int(11)'),$c('effective_ktu_bp','int(11)'),$c('share_bp','int(11)'),$c('amount_cents','bigint(20)'),$c('employment_status','varchar(40)'),$c('participation_basis','varchar(300)')],['PRIMARY'=>[0,$ix('id')],'snapshot_id'=>[1,$ix('snapshot_id','object_id')]]],
            'fm2_pilot_otiz_snapshot_issues'=>[[ $c('id','bigint(20) unsigned','NO','auto_increment'),$c('snapshot_id','bigint(20) unsigned'),$c('object_id','bigint(20) unsigned'),$c('severity',"enum('blocker','warning')"),$c('issue_code','varchar(80)'),$c('message','varchar(600)'),$c('owner_role','varchar(120)'),$c('state',"enum('open','resolved')"),$c('resolution','varchar(600)','YES'),$c('resolved_by_user_id','bigint(20) unsigned','YES'),$c('resolved_at','varchar(40)','YES')],['PRIMARY'=>[0,$ix('id')],'snapshot_id'=>[1,$ix('snapshot_id','object_id','severity')]]],
            'fm2_pilot_otiz_snapshot_evidence'=>[[ $c('snapshot_id','bigint(20) unsigned'),$c('legacy_object_id','bigint(20) unsigned'),$c('admission_state',"enum('confirmed_not_mapped','excluded')"),$c('source_label','varchar(160)'),$c('source_locator','varchar(160)'),$c('snapshot_hash','char(64)'),$c('projection_hash','char(64)'),$c('evidence_grade','char(1)'),$c('payload_json','longtext')],['PRIMARY'=>[0,$ix('snapshot_id','legacy_object_id')]]],
            'fm2_pilot_otiz_payment_closures'=>[[ $c('id','bigint(20) unsigned','NO','auto_increment'),$c('snapshot_id','bigint(20) unsigned'),$c('object_id','bigint(20) unsigned'),$c('closed_on','date'),$c('paid_cents','bigint(20)'),$c('discipline_cents','bigint(20)'),$c('deadline_cents','bigint(20)'),$c('basis','varchar(500)'),$c('artifact','varchar(300)'),$c('created_by_user_id','bigint(20) unsigned'),$c('created_at','varchar(40)'),$c('reverses_payment_closure_id','bigint(20) unsigned','YES')],['PRIMARY'=>[0,$ix('id')],'object_id'=>[1,$ix('object_id','closed_on')],'snapshot_id'=>[1,$ix('snapshot_id')],'unique_reversal'=>[0,$ix('reverses_payment_closure_id')]]],
            'fm2_pilot_otiz_events'=>[[ $c('id','bigint(20) unsigned','NO','auto_increment'),$c('snapshot_id','bigint(20) unsigned','YES'),$c('object_id','bigint(20) unsigned','YES'),$c('event_type','varchar(80)'),$c('payload_json','longtext'),$c('actor_user_id','bigint(20) unsigned'),$c('occurred_at','varchar(40)')],['PRIMARY'=>[0,$ix('id')],'snapshot_id'=>[1,$ix('snapshot_id','id')]]],
            self::RECEIPT=>[[ $c('snapshot_id','bigint(20) unsigned'),$c('actor_user_id','bigint(20) unsigned'),$c('operation_id','char(36)'),$c('request_sha256','char(64)'),$c('manifest_version','varchar(40)'),$c('manifest_sha256','char(64)'),$c('object_count','int(10) unsigned'),$c('allocation_count','int(10) unsigned'),$c('issue_count','int(10) unsigned'),$c('published_at','varchar(40)')],['PRIMARY'=>[0,$ix('snapshot_id')],'uq_actor_operation'=>[0,$ix('actor_user_id','operation_id')]]],
        ];
    }
}
