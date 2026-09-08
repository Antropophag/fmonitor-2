<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Canonical v21 owner of migrated-evidence decision and projection storage. */
final class OtizEvidenceSchemaMigration
{
    private const TABLES=['fm2_migrated_evidence_decisions','fm2_migrated_evidence_projection','fm2_migrated_evidence_conflicts','fm2_migrated_evidence_decision_state','fm2_migration_quarantine_decisions'];

    public static function apply(\mysqli $db,string $prefix=''):array
    {
        MariaDbSchemaInspector::validateTablePrefix($prefix);if(self::isCompleteCompatible($db,$prefix))return['applied'=>false,'schemaVersion'=>21,'tablesCreated'=>[]];
        $present=[];foreach(self::TABLES as$table)if(MariaDbSchemaInspector::tableExists($db,$prefix.$table))$present[]=$table;
        if($present!==[]){$conflicts=[];foreach(self::definitions()as$table=>$manifest)if(!MariaDbOtizSchemaManifest::matches($db,$prefix.$table,$manifest[0],$manifest[1]))$conflicts[]=$prefix.$table;return['applied'=>false,'schemaVersion'=>21,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>$conflicts];}
        $p=$prefix;$ddl=[
            "CREATE TABLE `{$p}fm2_migrated_evidence_decisions`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,snapshot_id BIGINT UNSIGNED NOT NULL,snapshot_sha256 CHAR(64) NOT NULL,projection_sha256 CHAR(64) NOT NULL,source_locator VARCHAR(500) NOT NULL,issue_code VARCHAR(80) NOT NULL,outcome VARCHAR(40) NOT NULL,target_locator VARCHAR(500) NULL,reason VARCHAR(1000) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,UNIQUE KEY uq_operation(operation_id),KEY ix_snapshot_issue(snapshot_id,issue_code,id),KEY ix_actor(actor_user_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `{$p}fm2_migrated_evidence_projection`(snapshot_id BIGINT UNSIGNED NOT NULL,legacy_object_id BIGINT UNSIGNED NOT NULL,projection_version VARCHAR(80) NOT NULL,input_sha256 CHAR(64) NOT NULL,projection_sha256 CHAR(64) NOT NULL,classification VARCHAR(40) NOT NULL,evidence_grade CHAR(1) NOT NULL,confidence VARCHAR(20) NOT NULL,quarantine_count INT UNSIGNED NOT NULL,conflict_codes_json JSON NOT NULL,conflict_search VARCHAR(2000) NOT NULL,payload_json LONGTEXT NOT NULL,projected_at DATETIME NOT NULL,PRIMARY KEY(snapshot_id,legacy_object_id),KEY ix_filter(classification,evidence_grade,quarantine_count,legacy_object_id),KEY ix_object(legacy_object_id,snapshot_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `{$p}fm2_migrated_evidence_conflicts`(snapshot_id BIGINT UNSIGNED NOT NULL,issue_code VARCHAR(80) NOT NULL,PRIMARY KEY(snapshot_id,issue_code),KEY ix_code(issue_code,snapshot_id)) ENGINE=InnoDB",
            "CREATE TABLE `{$p}fm2_migrated_evidence_decision_state`(snapshot_id BIGINT UNSIGNED NOT NULL,issue_code VARCHAR(80) NOT NULL,decision_id BIGINT UNSIGNED NOT NULL,outcome VARCHAR(40) NOT NULL,PRIMARY KEY(snapshot_id,issue_code),KEY ix_outcome(outcome,snapshot_id)) ENGINE=InnoDB",
            "CREATE TABLE `{$p}fm2_migration_quarantine_decisions`(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,source_locator VARCHAR(80) NOT NULL,source_cutoff_at DATETIME NOT NULL,source_digest CHAR(64) NOT NULL,classification_version VARCHAR(80) NOT NULL,quarantine_code VARCHAR(100) NOT NULL,outcome VARCHAR(40) NOT NULL,reason VARCHAR(1000) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,UNIQUE KEY uq_operation(operation_id),KEY ix_reference(source_locator,source_cutoff_at,source_digest,classification_version,quarantine_code,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];foreach($ddl as$sql)$db->query($sql);if(!self::isCompleteCompatible($db,$prefix))throw new \RuntimeException('OTIZ evidence schema migration failed.');return['applied'=>true,'schemaVersion'=>21,'tablesCreated'=>array_map(fn(string$table):string=>$prefix.$table,self::TABLES)];
    }

    public static function isCompleteCompatible(\mysqli $db,string $prefix=''):bool
    {
        try{MariaDbSchemaInspector::validateTablePrefix($prefix);foreach(self::definitions()as$table=>$manifest)if(!MariaDbOtizSchemaManifest::matches($db,$prefix.$table,$manifest[0],$manifest[1]))return false;return true;}catch(\Throwable){return false;}
    }

    public static function assertReady(\mysqli $db,string $prefix=''):void
    {
        if(!self::isCompleteCompatible($db,$prefix))throw new \RuntimeException('OTIZ evidence schema unavailable.');
    }

    /** @return array<string,array{0:list<array{0:string,1:string,2:string,3:string}>,1:array<string,array{0:int,1:string}>}> */
    private static function definitions():array
    {
        $c=static fn(string$n,string$t,string$nullable='NO',string$extra=''):array=>[$n,$t,$nullable,$extra];$ix=static fn(string ...$names):string=>implode(',',array_map(fn(string$n):string=>$n.':FULL:A:NO',$names));
        return[
            self::TABLES[0]=>[[ $c('id','bigint(20) unsigned','NO','auto_increment'),$c('operation_id','char(36)'),$c('request_sha256','char(64)'),$c('snapshot_id','bigint(20) unsigned'),$c('snapshot_sha256','char(64)'),$c('projection_sha256','char(64)'),$c('source_locator','varchar(500)'),$c('issue_code','varchar(80)'),$c('outcome','varchar(40)'),$c('target_locator','varchar(500)','YES'),$c('reason','varchar(1000)'),$c('actor_user_id','bigint(20) unsigned'),$c('occurred_at','varchar(40)')],['PRIMARY'=>[0,$ix('id')],'ix_actor'=>[1,$ix('actor_user_id','id')],'ix_snapshot_issue'=>[1,$ix('snapshot_id','issue_code','id')],'uq_operation'=>[0,$ix('operation_id')]]],
            self::TABLES[1]=>[[ $c('snapshot_id','bigint(20) unsigned'),$c('legacy_object_id','bigint(20) unsigned'),$c('projection_version','varchar(80)'),$c('input_sha256','char(64)'),$c('projection_sha256','char(64)'),$c('classification','varchar(40)'),$c('evidence_grade','char(1)'),$c('confidence','varchar(20)'),$c('quarantine_count','int(10) unsigned'),$c('conflict_codes_json','longtext'),$c('conflict_search','varchar(2000)'),$c('payload_json','longtext'),$c('projected_at','datetime')],['PRIMARY'=>[0,$ix('snapshot_id','legacy_object_id')],'ix_filter'=>[1,$ix('classification','evidence_grade','quarantine_count','legacy_object_id')],'ix_object'=>[1,$ix('legacy_object_id','snapshot_id')]]],
            self::TABLES[2]=>[[ $c('snapshot_id','bigint(20) unsigned'),$c('issue_code','varchar(80)')],['PRIMARY'=>[0,$ix('snapshot_id','issue_code')],'ix_code'=>[1,$ix('issue_code','snapshot_id')]]],
            self::TABLES[3]=>[[ $c('snapshot_id','bigint(20) unsigned'),$c('issue_code','varchar(80)'),$c('decision_id','bigint(20) unsigned'),$c('outcome','varchar(40)')],['PRIMARY'=>[0,$ix('snapshot_id','issue_code')],'ix_outcome'=>[1,$ix('outcome','snapshot_id')]]],
            self::TABLES[4]=>[[ $c('id','bigint(20) unsigned','NO','auto_increment'),$c('operation_id','char(36)'),$c('request_sha256','char(64)'),$c('source_locator','varchar(80)'),$c('source_cutoff_at','datetime'),$c('source_digest','char(64)'),$c('classification_version','varchar(80)'),$c('quarantine_code','varchar(100)'),$c('outcome','varchar(40)'),$c('reason','varchar(1000)'),$c('actor_user_id','bigint(20) unsigned'),$c('occurred_at','varchar(40)')],['PRIMARY'=>[0,$ix('id')],'ix_reference'=>[1,$ix('source_locator','source_cutoff_at','source_digest','classification_version','quarantine_code','id')],'uq_operation'=>[0,$ix('operation_id')]]],
        ];
    }
}
