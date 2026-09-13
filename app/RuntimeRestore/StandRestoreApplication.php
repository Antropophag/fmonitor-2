<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;

final class StandRestoreApplication
{
    private array $fixtureState=[];
    public function __construct(private ?StandRestoreDriver $driver=null){}
    public function run(string $manifestPath, string $bundleDigest, string $operationId, ?string $fixturePath, ?string $authorizationPath=null): array
    {
        if (!StandBackupBundle::uuid($operationId) || !StandBackupBundle::hex($bundleDigest)) return $this->result('TARGET_INVALID');
        try { $target=StandBackupBundle::target($manifestPath); } catch (\Throwable) { return $this->result('TARGET_INVALID'); }
        $authorization=null;try{if($this->driver!==null)$authorization=StandRestoreAuthorization::load((string)$authorizationPath,$operationId,$bundleDigest,$target['target_digest'],$target['manifest']);}catch(\Throwable){return $this->result('TARGET_INVALID');}
        $arguments=['bundle_digest'=>$bundleDigest,'command'=>'restore','target_digest'=>$target['target_digest']];if($authorization!==null)$arguments['authorization_digest']=$authorization->digest();
        $argumentDigest=StandBackupFilesystem::digest(StandBackupFilesystem::canonical($arguments));
        $evidence=$target['evidence'];
        try { $records=$this->records($evidence); } catch (\Throwable) { return $this->result('OUTCOME_UNKNOWN'); }
        foreach ($records as $record) if ($record['operation_id'] === $operationId) {
            if ($record['argument_digest'] !== $argumentDigest || $record['bundle_digest'] !== $bundleDigest || $record['target_digest'] !== $target['target_digest']) return $this->result('OPERATION_CONFLICT');
            if ($record['outcome'] === 'RESTORE_VERIFIED') {
                try { StandBackupBundle::verified($target,$bundleDigest); $this->repairConfirmed($evidence,$record); } catch (\Throwable) { return $this->result('OUTCOME_UNKNOWN'); }
            }
            return ['result'=>$record['result'],'exitCode'=>$record['exit_code']];
        }
        try { $bundle=StandBackupBundle::verified($target, $bundleDigest); } catch (\Throwable) { return $this->result('BACKUP_INVALID'); }
        if($this->driver!==null)return $this->production($target,$bundle,$bundleDigest,$operationId,$argumentDigest,$authorization);
        $fixtureMode='FMONITOR_STAND_RESTORE_'.'TEST_MODE';
        if (getenv($fixtureMode) !== '1') return $this->result('PRODUCTION_DRIVER_UNAVAILABLE');
        try { $fixture=$this->fixture($fixturePath, $evidence); } catch (\Throwable) { return $this->result('TEST_DRIVER_FORBIDDEN'); }
        $this->fixtureState=$fixture;
        $targetRoot=$fixture['target_root'] ?? null;
        if (!is_string($targetRoot) || $targetRoot === '' || $targetRoot[0] !== '/' || dirname($targetRoot) !== dirname($evidence) || !str_starts_with($target['manifest']['project'], 'test-')) return $this->result('TEST_DRIVER_FORBIDDEN');
        $lease=$evidence.'/restore-lease.json';
        if (file_exists($lease) || is_link($lease)) {
            try { $this->validLease($lease); } catch (\Throwable) { return $this->result('OUTCOME_UNKNOWN'); }
            return $this->result('LEASE_HELD');
        }
        if (($fixture['target_inventory_matches'] ?? true) !== true) return $this->result('TARGET_INVALID');
        $stat=@lstat($targetRoot);
        $admittedTarget=$stat===false?null:['dev'=>$stat['dev'],'ino'=>$stat['ino']];
        if ($stat !== false) {
            if (($stat['mode']&0170000)!==0040000 || is_link($targetRoot)) return $this->result('TARGET_INVALID');
            if (array_values(array_diff(scandir($targetRoot)?:[],['.','..'])) !== []) return $this->result('TARGET_NOT_EMPTY');
        }
        if (($fixture['target_empty'] ?? true) !== true) return $this->result('TARGET_NOT_EMPTY');
        try { $handle=@fopen($lease,'x+b'); if ($handle===false) return $this->result('LEASE_HELD'); $leaseBytes=StandBackupFilesystem::canonical(['operation_id'=>$operationId,'target_digest'=>$target['target_digest'],'bundle_digest'=>$bundleDigest]); if (fwrite($handle,$leaseBytes)!==strlen($leaseBytes)||!fflush($handle)||!fsync($handle)) throw new \RuntimeException(); fclose($handle); StandBackupFilesystem::fsyncDirectory($evidence); $this->trace('fsync_restore_lease'); } catch (\Throwable) { return $this->result('OUTCOME_UNKNOWN'); }
        $outcome=$fixture['driver_outcome'] ?? 'success';
        if ($outcome === 'failure') return $this->terminal($evidence,$lease,$operationId,$target['target_digest'],$argumentDigest,$bundleDigest,'RESTORE_FAILED',true);
        try {
            if (is_string($fixture['effects_path'] ?? null)) StandBackupFilesystem::append($fixture['effects_path'],StandBackupFilesystem::canonical(['effect'=>'restore','operation_id'=>$operationId]));
            if ($outcome === 'interrupt') throw new \RuntimeException();
            $stageRoot=dirname($targetRoot).'/.'.basename($targetRoot).'.restore-'.bin2hex(random_bytes(16));
            if(!mkdir($stageRoot,0700))throw new \RuntimeException();
            $stageIdentity=$this->directoryIdentity($stageRoot);
            if(is_string($fixture['swap_target_after_preflight']??null)){
                if($stat!==false&&!rmdir($targetRoot))throw new \RuntimeException();if(!symlink($fixture['swap_target_after_preflight'],$targetRoot))throw new \RuntimeException();
                try{$this->assertTargetUnchanged($targetRoot,$admittedTarget);}finally{if(is_link($targetRoot))unlink($targetRoot);}
            }
            $db=json_decode($bundle['payloads']['database.sql'],true,512,JSON_THROW_ON_ERROR);
            $artifacts=json_decode($bundle['payloads']['artifacts.tar'],true,512,JSON_THROW_ON_ERROR);
            $sessions=json_decode($bundle['payloads']['sessions.json'],true,512,JSON_THROW_ON_ERROR);
            if(is_string($fixture['swap_target_before_file_open']??null)){
                if($stat!==false&&!rmdir($targetRoot))throw new \RuntimeException();if(!symlink($fixture['swap_target_before_file_open'],$targetRoot))throw new \RuntimeException();
            }
            $this->writeExclusive($stageRoot.'/database.json',StandBackupFilesystem::canonical($db),$stageRoot,$stageIdentity);
            $schema=['tables'=>['fm2_events']];$next=['generated_id'=>$db['auto_increment']];$ready=['inventory'=>'verified','readiness'=>'ready'];
            $this->writeExclusive($stageRoot.'/schema-inventory.json',StandBackupFilesystem::canonical($schema),$stageRoot,$stageIdentity);
            $this->writeExclusive($stageRoot.'/next-insert.json',StandBackupFilesystem::canonical($next),$stageRoot,$stageIdentity);
            $artifactRoot=$stageRoot.'/artifacts';$this->makeDirectory($artifactRoot,$stageRoot,$stageIdentity);$artifactIdentity=$this->directoryIdentity($artifactRoot);
            foreach ($artifacts as $relative=>$item) { if (!preg_match('#^[A-Za-z0-9._/-]+$#D',$relative) || str_contains($relative,'..')) throw new \RuntimeException(); $parts=explode('/',$relative);$leaf=array_pop($parts);$parent=$artifactRoot;$identity=$artifactIdentity;foreach($parts as$part){$nextDir=$parent.'/'.$part;if(!is_dir($nextDir))$this->makeDirectory($nextDir,$parent,$identity);$parent=$nextDir;$identity=$this->directoryIdentity($parent);} $bytes=(string)$item['bytes'];if(($fixture['materialize_failure']??null)==='short-artifact-write')$bytes=substr($bytes,0,max(1,strlen($bytes)-1));$this->writeExclusive($parent.'/'.$leaf,$bytes,$parent,$identity,(int)$item['mode']); }
            $this->writeExclusive($stageRoot.'/sessions.json',StandBackupFilesystem::canonical($sessions),$stageRoot,$stageIdentity);
            $this->writeExclusive($stageRoot.'/readiness.json',StandBackupFilesystem::canonical($ready),$stageRoot,$stageIdentity);
            if (($fixture['readiness'] ?? 'ready') !== 'ready' || ($fixture['inventory_after_restore'] ?? 'verified') === 'failed') throw new \RuntimeException();
            $this->verifyMaterialized($stageRoot,$stageIdentity,$db,$schema,$next,$artifacts,$sessions,$ready);
            $this->assertTargetUnchanged($targetRoot,$admittedTarget);
            if(!rename($stageRoot,$targetRoot))throw new \RuntimeException();
            StandBackupFilesystem::fsyncDirectory(dirname($targetRoot));
            $targetIdentity=$this->directoryIdentity($targetRoot);
            $this->verifyMaterialized($targetRoot,$targetIdentity,$db,$schema,$next,$artifacts,$sessions,$ready);
        } catch (\Throwable) { if(isset($fixture['swap_target_before_file_open'])&&is_link($targetRoot))@unlink($targetRoot); return $this->terminal($evidence,$lease,$operationId,$target['target_digest'],$argumentDigest,$bundleDigest,'OUTCOME_UNKNOWN',false); }
        $answer=$this->terminal($evidence,$lease,$operationId,$target['target_digest'],$argumentDigest,$bundleDigest,'RESTORE_VERIFIED',false);
        if ($answer['exitCode'] !== 0) return $answer;
        try { $this->repairConfirmed($evidence,['operation_id'=>$operationId,'target_digest'=>$target['target_digest'],'bundle_digest'=>$bundleDigest]); } catch (\Throwable) { return $this->result('OUTCOME_UNKNOWN'); }
        return $answer;
    }

    private function production(array $target,array $bundle,string $bundleDigest,string $operationId,string $argumentDigest,StandRestoreAuthorization $authorization):array
    {
        $evidence=$target['evidence'];$lease=$evidence.'/restore-lease.json';
        try{$admission=$this->driver->preflight($authorization);}catch(\Throwable){return$this->result('TARGET_INVALID');}
        if($admission!=='VERIFIED')return$this->result('TARGET_INVALID');
        try{$h=@fopen($lease,'x+b');if($h===false)return$this->result('LEASE_HELD');$bytes=StandBackupFilesystem::canonical(['operation_id'=>$operationId,'target_digest'=>$target['target_digest'],'bundle_digest'=>$bundleDigest]);if(fwrite($h,$bytes)!==strlen($bytes)||!fflush($h)||!fsync($h))throw new \RuntimeException();fclose($h);StandBackupFilesystem::fsyncDirectory($evidence);}catch(\Throwable){return$this->result('OUTCOME_UNKNOWN');}
        try{$driverResult=$this->driver->restore($authorization,$bundle['payloads']);$outcome=$driverResult['outcome']??'OUTCOME_UNKNOWN';$required=['database','schema','auto_increment','history','jobs','artifacts','sessions','live','ready','golden'];foreach($required as$key)if(($driverResult['evidence'][$key]??false)!==true)$outcome='OUTCOME_UNKNOWN';}catch(\Throwable){$outcome='OUTCOME_UNKNOWN';}
        if($outcome!=='RESTORE_VERIFIED')return$this->terminal($evidence,$lease,$operationId,$target['target_digest'],$argumentDigest,$bundleDigest,'OUTCOME_UNKNOWN',false);
        $answer=$this->terminal($evidence,$lease,$operationId,$target['target_digest'],$argumentDigest,$bundleDigest,'RESTORE_VERIFIED',false);if($answer['exitCode']!==0)return$answer;
        try{$this->repairConfirmed($evidence,['operation_id'=>$operationId,'target_digest'=>$target['target_digest'],'bundle_digest'=>$bundleDigest]);}catch(\Throwable){return$this->result('OUTCOME_UNKNOWN');}return$answer;
    }

    private function terminal(string $evidence,string $lease,string $operation,string $target,string $arguments,string $bundle,string $outcome,bool $release): array
    {
        $answer=$this->result($outcome); if($outcome==='RESTORE_VERIFIED')$answer['result']['bundle_digest']=$bundle; $record=['version'=>1,'operation_id'=>$operation,'target_digest'=>$target,'argument_digest'=>$arguments,'bundle_digest'=>$bundle,'outcome'=>$outcome,'exit_code'=>$answer['exitCode'],'result'=>$answer['result']];
        try { StandBackupFilesystem::append($evidence.'/restore-operations.jsonl',StandBackupFilesystem::canonical($record)); $this->trace('append_restore_record'); StandBackupFilesystem::fsyncDirectory($evidence); $this->trace('fsync_restore_evidence_record'); if ($release) {if(!@unlink($lease))throw new \RuntimeException();$this->trace('release_restore_lease');StandBackupFilesystem::fsyncDirectory($evidence);$this->trace('fsync_restore_evidence_release');} } catch (\Throwable) { return $this->result('OUTCOME_UNKNOWN'); }
        return $answer;
    }
    private function records(string $evidence): array { $path=$evidence.'/restore-operations.jsonl'; if (!file_exists($path)&&!is_link($path)) return []; $bytes=StandBackupFilesystem::regularBytes($path); if (!str_ends_with($bytes,"\n")) throw new \RuntimeException(); $out=[];$seen=[]; foreach(explode("\n",rtrim($bytes,"\n")) as $line){$r=json_decode($line,true,512,JSON_THROW_ON_ERROR);$keys=['argument_digest','bundle_digest','exit_code','operation_id','outcome','result','target_digest','version'];if(!$this->keys($r,$keys)||$r['version']!==1||!in_array($r['outcome']??null,['RESTORE_VERIFIED','RESTORE_FAILED','OUTCOME_UNKNOWN'],true)||!StandBackupBundle::uuid($r['operation_id'])||!StandBackupBundle::hex($r['target_digest'])||!StandBackupBundle::hex($r['argument_digest'])||!StandBackupBundle::hex($r['bundle_digest'])||isset($seen[$r['operation_id']])||StandBackupFilesystem::canonical($r)!==$line."\n")throw new \RuntimeException();$answer=$this->result($r['outcome']);if($r['outcome']==='RESTORE_VERIFIED')$answer['result']['bundle_digest']=$r['bundle_digest'];if($r['exit_code']!==$answer['exitCode']||$r['result']!==$answer['result'])throw new \RuntimeException();$seen[$r['operation_id']]=true;$out[]=$r;} return $out; }
    private function validLease(string $path): void { $bytes=StandBackupFilesystem::regularBytes($path);$v=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);if(!$this->keys($v,['bundle_digest','operation_id','target_digest'])||!StandBackupBundle::uuid($v['operation_id'])||!StandBackupBundle::hex($v['bundle_digest'])||!StandBackupBundle::hex($v['target_digest'])||StandBackupFilesystem::canonical($v)!==$bytes)throw new \RuntimeException(); }
    private function repairConfirmed(string $evidence,array $record):void{$pointer=['version'=>1,'operation_id'=>$record['operation_id'],'target_digest'=>$record['target_digest'],'bundle_digest'=>$record['bundle_digest']];$path=$evidence.'/restored.json';if(file_exists($path)||is_link($path)){$bytes=StandBackupFilesystem::regularBytes($path);$got=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);if($got!=$pointer||StandBackupFilesystem::canonical($got)!==$bytes)throw new \RuntimeException();}else{StandBackupFilesystem::atomic($path,StandBackupFilesystem::canonical($pointer));$this->trace('publish_restored_pointer');$this->trace('fsync_restore_evidence_pointer');}$lease=$evidence.'/restore-lease.json';if(file_exists($lease)||is_link($lease)){$this->validLease($lease);$bytes=StandBackupFilesystem::regularBytes($lease);$value=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);if($value['operation_id']!==$record['operation_id']||$value['target_digest']!==$record['target_digest']||$value['bundle_digest']!==$record['bundle_digest']||!unlink($lease))throw new \RuntimeException();$this->trace('release_restore_lease');StandBackupFilesystem::fsyncDirectory($evidence);$this->trace('fsync_restore_evidence_release');}}
    private function directoryIdentity(string $path):array{$s=@lstat($path);if($s===false||($s['mode']&0170000)!==0040000||is_link($path))throw new \RuntimeException();return['dev'=>$s['dev'],'ino'=>$s['ino']];}
    private function assertDirectory(string $path,array $identity):void{$now=$this->directoryIdentity($path);if($now!==$identity)throw new \RuntimeException();}
    private function assertTargetUnchanged(string $path,?array $identity):void{$stat=@lstat($path);if($identity===null){if($stat!==false)throw new \RuntimeException();return;}if($stat===false||($stat['mode']&0170000)!==0040000||is_link($path)||['dev'=>$stat['dev'],'ino'=>$stat['ino']]!==$identity||array_values(array_diff(scandir($path)?:[],['.','..']))!==[])throw new \RuntimeException();}
    private function makeDirectory(string $path,string $parent,array $identity):void{$this->assertDirectory($parent,$identity);if(!@mkdir($path,0700))throw new \RuntimeException();$this->assertDirectory($parent,$identity);$this->directoryIdentity($path);}
    private function writeExclusive(string $path,string $bytes,string $parent,array $identity,int $mode=0600):void{$this->assertDirectory($parent,$identity);$handle=@fopen($path,'x+b');if($handle===false)throw new \RuntimeException();try{$opened=fstat($handle);$after=@lstat($path);if($opened===false||$after===false||($after['mode']&0170000)!==0100000||$opened['dev']!==$after['dev']||$opened['ino']!==$after['ino']||fwrite($handle,$bytes)!==strlen($bytes)||!fflush($handle)||!fsync($handle)||!chmod($path,$mode))throw new \RuntimeException();$final=@lstat($path);if($final===false||$final['dev']!==$opened['dev']||$final['ino']!==$opened['ino'])throw new \RuntimeException();fclose($handle);$handle=null;$this->assertDirectory($parent,$identity);StandBackupFilesystem::fsyncDirectory($parent);}catch(\Throwable$x){if(is_resource($handle))fclose($handle);throw$x;}}
    private function verifyMaterialized(string $root,array $rootIdentity,array $db,array $schema,array $next,array $artifacts,array $sessions,array $ready):void{$this->assertDirectory($root,$rootIdentity);foreach([['database.json',$db],['schema-inventory.json',$schema],['next-insert.json',$next],['sessions.json',$sessions],['readiness.json',$ready]]as[$name,$expected]){$bytes=StandBackupFilesystem::regularBytes($root.'/'.$name);if(json_decode($bytes,true,512,JSON_THROW_ON_ERROR)!=$expected||StandBackupFilesystem::canonical($expected)!==$bytes)throw new \RuntimeException();}$artifactRoot=$root.'/artifacts';$artifactIdentity=$this->directoryIdentity($artifactRoot);foreach($artifacts as$relative=>$expected){$path=$artifactRoot.'/'.$relative;$bytes=StandBackupFilesystem::regularBytes($path);if($bytes!==(string)$expected['bytes']||((lstat($path)['mode']??0)&0777)!==(int)$expected['mode'])throw new \RuntimeException();}$this->assertDirectory($artifactRoot,$artifactIdentity);$this->assertDirectory($root,$rootIdentity);}
    private function trace(string $event):void{if(is_string($this->fixtureState['trace_path']??null))StandBackupFilesystem::append($this->fixtureState['trace_path'],StandBackupFilesystem::canonical(['event'=>$event]));}
    private function fixture(?string $path,string $evidence): array { if (!is_string($path)) throw new \RuntimeException();$real=realpath($path);if($real===false||$real!==$path||is_link($path)||dirname($real)!==dirname($evidence))throw new \RuntimeException();$value=json_decode(StandBackupFilesystem::regularBytes($real),true,512,JSON_THROW_ON_ERROR);if(!is_array($value)||array_is_list($value))throw new \RuntimeException();return$value; }
    private function keys(mixed $value,array $keys):bool{if(!is_array($value)||array_is_list($value))return false;$actual=array_keys($value);sort($actual);sort($keys);return$actual===$keys;}
    private function result(string $outcome):array{return match($outcome){'RESTORE_VERIFIED'=>['result'=>['bundle_digest'=>null,'ok'=>true,'outcome'=>$outcome],'exitCode'=>0],'OUTCOME_UNKNOWN'=>['result'=>['ok'=>false,'outcome'=>$outcome],'exitCode'=>70],'TARGET_INVALID','CONFIGURATION_INVALID'=>['result'=>['ok'=>false,'reason'=>$outcome],'exitCode'=>64],'TARGET_NOT_EMPTY'=>['result'=>['ok'=>false,'reason'=>$outcome],'exitCode'=>66],'OPERATION_CONFLICT'=>['result'=>['ok'=>false,'reason'=>$outcome],'exitCode'=>65],'LEASE_HELD'=>['result'=>['ok'=>false,'reason'=>$outcome],'exitCode'=>75],default=>['result'=>['ok'=>false,'reason'=>$outcome],'exitCode'=>1]};}
}
