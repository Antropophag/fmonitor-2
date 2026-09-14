<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class StandRestoreReconciliation
{
    private array $fixture=[];

    public function run(string$manifest,string$priorOperation,string$reconciliationId,string$authorizationPath,?string$fixturePath):array
    {
        try{$target=StandBackupBundle::target($manifest);}catch(\Throwable){return$this->result('TARGET_INVALID');}
        $evidence=$target['evidence'];$ledger=$evidence.'/restore-operations.jsonl';$lease=$evidence.'/restore-lease.json';$recovery=$evidence.'/restore-reconciliations.jsonl';
        try{$records=$this->restoreRecords($ledger);}catch(\Throwable){return$this->result('OPERATION_INVALID');}
        $record=null;foreach($records as$item)if($item['operation_id']===$priorOperation){$record=$item;break;}
        if($record===null)return$this->result('OPERATION_NOT_FOUND');
        if($record['outcome']!=='OUTCOME_UNKNOWN')return$this->result('OPERATION_NOT_UNKNOWN');
        try{$facts=$this->facts($recovery);}catch(\Throwable){return$this->result('RECOVERY_INVALID');}
        try{$authorization=StandRestoreReconciliationAuthorization::load($authorizationPath,$reconciliationId,$priorOperation,$target);}catch(\Throwable){return$this->result('AUTHORIZATION_INVALID');}
        $expected=$this->fact($authorization,$record);
        foreach($facts as$fact)if($fact['prior_operation_id']===$priorOperation||$fact['reconciliation_id']===$reconciliationId){
            if(!$this->sameFact($fact,$expected))return$this->result('OPERATION_CONFLICT');
            if(!$this->replayAdmission($target,$ledger,$record,$fact,$authorization,$fixturePath))return$this->result('TARGET_INVALID');
            return$this->repair($lease,$fact,$authorization,$target);
        }
        if(file_exists($evidence.'/restored.json')||is_link($evidence.'/restored.json'))return$this->result('RESTORED_POINTER_PRESENT');
        try{$leaseValue=$this->lease($lease);}catch(\Throwable){return$this->result('LEASE_INVALID');}
        if(($leaseValue['state']??null)==='ROLLBACK_ONLY')return$this->result('OPERATION_CONFLICT');
        if($leaseValue!==['bundle_digest'=>$record['bundle_digest'],'operation_id'=>$priorOperation,'target_digest'=>$record['target_digest']]||StandBackupFilesystem::digest(StandBackupFilesystem::regularBytes($lease))!==$authorization->value('lease_digest'))return$this->result('LEASE_INVALID');
        if($record['target_digest']!==$target['target_digest']||$record['bundle_digest']!==$authorization->value('bundle_digest')||StandBackupFilesystem::digest(StandBackupFilesystem::regularBytes($ledger))!==$authorization->value('unknown_record_digest'))return$this->result('AUTHORIZATION_INVALID');
        try{StandBackupBundle::verified($target,$authorization->value('rollback_bundle_digest'));}catch(\Throwable){return$this->result('ROLLBACK_BUNDLE_INVALID');}
        if(!$this->attest($fixturePath,$authorization,$target))return$this->result('TARGET_INVALID');
        if($this->interrupt('before_fact_append')||$this->interrupt('during_fact_append'))return$this->result('OUTCOME_UNKNOWN');
        try{StandBackupFilesystem::append($recovery,StandBackupFilesystem::canonical($expected));$this->trace('append_reconciliation_fact');}catch(\Throwable){return$this->result('OUTCOME_UNKNOWN');}
        if($this->interrupt('after_fact_fsync'))return$this->result('OUTCOME_UNKNOWN');
        try{StandBackupFilesystem::fsyncDirectory($evidence);$this->trace('fsync_reconciliation_directory');}catch(\Throwable){return$this->result('OUTCOME_UNKNOWN');}
        if($this->interrupt('after_fact_directory_fsync'))return$this->result('OUTCOME_UNKNOWN');
        return$this->repair($lease,$expected,$authorization,$target);
    }

    private function repair(string$lease,array$fact,StandRestoreReconciliationAuthorization$authorization,array$target):array
    {
        if(file_exists($target['evidence'].'/restored.json')||is_link($target['evidence'].'/restored.json'))return$this->result('OPERATION_CONFLICT');
        try{$value=$this->lease($lease);}catch(\Throwable){return$this->result('OPERATION_CONFLICT');}
        $original=['bundle_digest'=>$fact['bundle_digest'],'operation_id'=>$fact['prior_operation_id'],'target_digest'=>$fact['target_digest']];
        $transferred=['bundle_digest'=>$fact['bundle_digest'],'operation_id'=>$fact['prior_operation_id'],'reconciliation_id'=>$fact['reconciliation_id'],'state'=>'ROLLBACK_ONLY','target_digest'=>$fact['target_digest']];
        if($value===$transferred)return$this->result('UNKNOWN_RECONCILED_FOR_ROLLBACK');
        if($value!==$original||StandBackupFilesystem::digest(StandBackupFilesystem::regularBytes($lease))!==$fact['lease_digest'])return$this->result('OPERATION_CONFLICT');
        if($authorization->digest()!==$fact['authorization_digest'])return$this->result('OPERATION_CONFLICT');
        try{StandBackupFilesystem::atomic($lease,StandBackupFilesystem::canonical($transferred));$this->trace('transition_restore_lease');}catch(\Throwable){return$this->result('OUTCOME_UNKNOWN');}
        if($this->interrupt('after_lease_transition')||$this->interrupt('before_ready_publish'))return$this->result('OUTCOME_UNKNOWN');
        return$this->result('UNKNOWN_RECONCILED_FOR_ROLLBACK');
    }

    private function fact(StandRestoreReconciliationAuthorization$a,array$r):array{return['authorization_digest'=>$a->digest(),'bundle_digest'=>$r['bundle_digest'],'forward_completion'=>'ABANDONED','lease_digest'=>$a->value('lease_digest'),'next_action'=>'ROLLBACK','previous_outcome'=>'OUTCOME_UNKNOWN','prior_operation_id'=>$r['operation_id'],'reconciled_at'=>gmdate('Y-m-d\TH:i:s\Z'),'reconciliation_id'=>$a->value('reconciliation_id'),'rollback_bundle_digest'=>$a->value('rollback_bundle_digest'),'state'=>'ROLLBACK_ONLY','success_confirmed'=>false,'target_digest'=>$r['target_digest'],'unknown_record_digest'=>$a->value('unknown_record_digest'),'version'=>1];}
    private function sameFact(array$a,array$b):bool{$b['reconciled_at']=$a['reconciled_at']??null;return$a===$b;}
    private function replayAdmission(array$target,string$ledger,array$record,array$fact,StandRestoreReconciliationAuthorization$authorization,?string$fixturePath):bool
    {
        try{
            if(StandBackupFilesystem::digest(StandBackupFilesystem::regularBytes($ledger))!==$fact['unknown_record_digest'])return false;
            if($record['operation_id']!==$fact['prior_operation_id']||$record['outcome']!=='OUTCOME_UNKNOWN'||$record['target_digest']!==$fact['target_digest']||$record['bundle_digest']!==$fact['bundle_digest'])return false;
            if(file_exists($target['evidence'].'/restored.json')||is_link($target['evidence'].'/restored.json'))return false;
            StandBackupBundle::verified($target,$fact['rollback_bundle_digest']);
            return$this->attest($fixturePath,$authorization,$target);
        }catch(\Throwable){return false;}
    }
    private function restoreRecords(string$path):array{$bytes=StandBackupFilesystem::regularBytes($path);if(!str_ends_with($bytes,"\n"))throw new \RuntimeException();$out=[];foreach(explode("\n",rtrim($bytes,"\n"))as$line){$v=json_decode($line,true,512,JSON_THROW_ON_ERROR);if(!is_array($v)||array_is_list($v)||StandBackupFilesystem::canonical($v)!==$line."\n"||!StandBackupBundle::uuid($v['operation_id']??null)||!in_array($v['outcome']??null,['RESTORE_VERIFIED','RESTORE_FAILED','OUTCOME_UNKNOWN'],true))throw new \RuntimeException();$out[]=$v;}return$out;}
    private function facts(string$path):array{if(!file_exists($path)&&!is_link($path))return[];$bytes=StandBackupFilesystem::regularBytes($path);if(!str_ends_with($bytes,"\n"))throw new \RuntimeException();$out=[];foreach(explode("\n",rtrim($bytes,"\n"))as$line){$v=json_decode($line,true,512,JSON_THROW_ON_ERROR);if(!is_array($v)||array_is_list($v)||StandBackupFilesystem::canonical($v)!==$line."\n")throw new \RuntimeException();$out[]=$v;}return$out;}
    private function lease(string$path):array{$bytes=StandBackupFilesystem::regularBytes($path);$v=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);if(!is_array($v)||array_is_list($v)||StandBackupFilesystem::canonical($v)!==$bytes)return[];return$v;}
    private function attest(?string$path,StandRestoreReconciliationAuthorization$a,array$target):bool{try{if(getenv('FMONITOR_STAND_RECONCILE_TEST_MODE')==='1'){if(!is_string($path))return false;$real=realpath($path);if($real===false||$real!==$path||is_link($path)||dirname($real)!==dirname($target['evidence']))return false;$this->fixture=json_decode(StandBackupFilesystem::regularBytes($path),true,512,JSON_THROW_ON_ERROR);return($this->fixture['production_overlap']??true)===false&&($this->fixture['observed']??null)===$a->value('observed')&&($this->fixture['runtime']??null)===$a->value('runtime')&&($this->fixture['source']??null)===$a->value('source')&&($this->fixture['image']??null)===$a->value('image');}if($path!==null)return false;$manifest=$target['manifest'];if(!str_starts_with($manifest['project'],'fm2-disposable-')||!str_starts_with($manifest['database']['name'],'fm2_disposable_'))return false;$config=StandRuntimeConfiguration::fromEnvironment();if(['artifact_volume_path'=>$config->artifactVolumePath(),'process_table_prefix'=>substr($config->restoreProbeTable(),0,-strlen('fm2_restore_rehearsal_probe')),'session_volume_path'=>$config->sessionVolumePath()]!==$a->value('runtime'))return false;$process=new NativeStandProcess();$compose=['docker','compose','--project-name',$manifest['project'],'--file',$manifest['compose_file']];$databaseId=trim($process->run([...$compose,'ps','-q','db']));$observed=['database_id'=>$databaseId,'project_id'=>trim($process->run(['docker','inspect','--format','{{index .Config.Labels "com.docker.compose.project"}}',$databaseId])),'network_id'=>trim($process->run(['docker','inspect','--format','{{range $k,$v := .NetworkSettings.Networks}}{{$v.NetworkID}}{{end}}',$databaseId])),'volumes'=>$manifest['volumes']];foreach($observed['volumes']as$role=>$volume)$observed['volumes'][$role]['observed_id']=hash('sha256',$process->run(['docker','volume','inspect',$volume['name']]));return$observed===$a->value('observed')&&$manifest['database']['observed_id']===$databaseId;}catch(\Throwable){return false;}}
    private function interrupt(string$point):bool{return($this->fixture['interrupt_at']??null)===$point;}
    private function trace(string$event):void{if(is_string($this->fixture['trace_path']??null))StandBackupFilesystem::append($this->fixture['trace_path'],StandBackupFilesystem::canonical(['event'=>$event]));}
    private function result(string$outcome):array{return match($outcome){'UNKNOWN_RECONCILED_FOR_ROLLBACK'=>['result'=>['ok'=>true,'outcome'=>$outcome],'exitCode'=>0],'OUTCOME_UNKNOWN'=>['result'=>['ok'=>false,'outcome'=>$outcome],'exitCode'=>70],'OPERATION_CONFLICT'=>['result'=>['ok'=>false,'reason'=>$outcome],'exitCode'=>65],default=>['result'=>['ok'=>false,'reason'=>$outcome],'exitCode'=>64]};}
}
