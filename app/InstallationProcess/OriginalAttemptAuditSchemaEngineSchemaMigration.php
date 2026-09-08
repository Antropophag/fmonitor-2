<?php

declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
use FMonitor2\AssignmentOrderOriginal as O;
require_once dirname(__DIR__).'/AssignmentOrderOriginal/AssignmentOrderOriginalSchemaMigration.php';
require_once dirname(__DIR__).'/AssignmentOrderOriginal/MariaDbCheckCanonicalizer.php';
require_once __DIR__.'/MariaDbOriginalAttemptAuditSchemaFingerprint.php';

/** @internal Sole v3 DDL/lock owner; all facts remain append-only. */
final readonly class OriginalAttemptAuditSchemaEngine
{
    public function __construct(private OriginalAttemptAuditSchemaObserver $observer){}
    public function apply(\mysqli $db,string $prefix):array
    {
        $lock=null;$held=false;
        try {
            MariaDbSchemaInspector::validateTablePrefix($prefix);
            $state=$db->query('SELECT @@in_transaction active');
            if(!$state||$state->num_rows!==1||!in_array($state->fetch_assoc()['active']??null,[0,'0'],true))self::fail();
            $identity=$db->query('SELECT DATABASE() name');$database=$identity?->fetch_assoc()['name']??null;
            if(!is_string($database)||$database==='')self::fail();
            $lock=hash('sha256',$database."\0".$prefix."\0original-audit-v3");
            if(!self::one($db,"SELECT GET_LOCK('{$lock}',5) value"))self::fail();$held=true;
            return $this->upgrade($db,$prefix);
        } catch(\Throwable) {self::fail();}
        finally {
            if($held) {
                try {if(!self::one($db,"SELECT RELEASE_LOCK('{$lock}') value"))self::fail();}
                catch(\Throwable){self::fail();}
            }
        }
    }
    private function upgrade(\mysqli $db,string $prefix):array
    {
        $state=MariaDbOriginalAttemptAuditSchemaFingerprint::state($db,$prefix);
        if($state==='V3')return MariaDbOriginalAttemptAuditSchemaFingerprint::fullV3($db,$prefix)
            ? ['applied'=>false,'reason'=>null] : self::conflict();
        if($state==='CONFLICT')return self::conflict();
        if(!in_array(ProcessCapabilityChecksClassifier::inspect($db,$prefix.'fm2_process_user_capabilities')['state']??null,['v4','v5'],true))return self::conflict();
        $setup=O\AssignmentOrderOriginalSchemaMigration::apply($db,$prefix);
        if($setup->status()===O\AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT)return self::conflict();
        if(MariaDbOriginalAttemptAuditSchemaFingerprint::state($db,$prefix)!=='V2')self::fail();
        $names=MariaDbOriginalAttemptAuditSchemaFingerprint::alterationNames($db,$prefix);
        $clauses=['DROP INDEX `'.$names['index'].'`',
            'ADD INDEX `'.MariaDbOriginalAttemptAuditSchemaFingerprint::INDEX.'` (request_id,status,reason_code)',
            'DROP FOREIGN KEY `'.$names['foreignKey'].'`'];
        foreach($names['checks'] as $check)$clauses[]='DROP CONSTRAINT `'.$check.'`';
        $clauses[]='ADD CONSTRAINT `ck_ao_audit_status_v3` CHECK ('.MariaDbOriginalAttemptAuditSchemaFingerprint::statusCheck().')';
        $clauses[]='ADD CONSTRAINT `ck_ao_audit_pair_v3` CHECK ('.MariaDbOriginalAttemptAuditSchemaFingerprint::pairCheck().')';
        $this->observer->observe(OriginalAttemptAuditSchemaPhase::BEFORE_AUDIT_ALTER);
        if($db->query('ALTER TABLE `'.$prefix.MariaDbOriginalAttemptAuditSchemaFingerprint::AUDIT.'` '.implode(',',$clauses))!==true)self::fail();
        $this->observer->observe(OriginalAttemptAuditSchemaPhase::AFTER_AUDIT_ALTER);
        if(!MariaDbOriginalAttemptAuditSchemaFingerprint::fullV3($db,$prefix))self::fail();
        return ['applied'=>true,'reason'=>null];
    }
    private static function one(\mysqli $db,string $sql):bool
    {$result=$db->query($sql);return $result instanceof \mysqli_result&&$result->num_rows===1&&in_array($result->fetch_assoc()['value']??null,[1,'1'],true);}
    private static function conflict():array{return ['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT'];}
    private static function fail():never{throw new DatabaseUnavailable('Original attempt audit schema unavailable.');}
}
