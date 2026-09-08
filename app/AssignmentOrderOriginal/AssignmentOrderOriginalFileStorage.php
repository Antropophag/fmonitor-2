<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

final class AssignmentOrderOriginalPrivateOrphanFixtureUnavailable extends \RuntimeException{public function __construct(){parent::__construct('AssignmentOrderOriginalPrivateOrphanFixtureUnavailable',0,null);}}
final class AssignmentOrderOriginalPrivateOrphanFixtureConflict extends \RuntimeException{public function __construct(){parent::__construct('AssignmentOrderOriginalPrivateOrphanFixtureConflict',0,null);}}
final class AssignmentOrderOriginalFileState{
 public static function read(string$r):array{$f=$r.'/.aoou-state.json';if(!is_file($f))return['stages'=>[],'finalized'=>[]];$v=json_decode((string)file_get_contents($f),true,512,JSON_THROW_ON_ERROR);if(!is_array($v)||!isset($v['stages'],$v['finalized']))throw new \RuntimeException();return$v;}
 public static function lock(string$f){$h=@fopen($f,'c+b');if(!$h)throw new \RuntimeException();chmod($f,0600);return$h;}
 public static function atomic(string$f,string$b):void{$t=$f.'.tmp-'.bin2hex(random_bytes(8));$h=@fopen($t,'x+b');if(!$h)throw new \RuntimeException();try{chmod($t,0600);for($o=0;$o<strlen($b);$o+=$n){$n=fwrite($h,substr($b,$o));if(!is_int($n)||$n<1)throw new \RuntimeException();}if(!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new \RuntimeException();}catch(\Throwable$e){fclose($h);@unlink($t);throw$e;}fclose($h);if(!@rename($t,$f)){@unlink($t);throw new \RuntimeException();}}
 public static function mutate(string$r,callable$f):mixed{$h=self::lock($r.'/.aoou-state.lock');try{if(!flock($h,LOCK_EX))throw new \RuntimeException();$s=self::read($r);$v=$f($s);self::atomic($r.'/.aoou-state.json',json_encode($s,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));return$v;}finally{flock($h,LOCK_UN);fclose($h);}}
}
final readonly class AssignmentOrderOriginalFileContent implements AssignmentOrderOriginalPrivateContent{public function __construct(private string$i,private string$h,private int$n){}public function opaqueIdentity():string{return$this->i;}public function sha256():string{return$this->h;}public function byteSize():int{return$this->n;}}
final class AssignmentOrderOriginalFileLease implements AssignmentOrderOriginalPrivateContentLease{private bool$r=false;public function __construct(private$h,private AssignmentOrderOriginalFileContent$c,private AssignmentOrderOriginalFaultInjector$f){}public function status():AssignmentOrderOriginalStorageStatus{return AssignmentOrderOriginalStorageStatus::OK;}public function content():?AssignmentOrderOriginalPrivateContent{return$this->c;}public function release():AssignmentOrderOriginalStorageStatus{if($this->r)return AssignmentOrderOriginalStorageStatus::OK;$this->r=true;try{$this->f->before(AssignmentOrderOriginalFaultPoint::CONTENT_LEASE_RELEASE);flock($this->h,LOCK_UN);fclose($this->h);return AssignmentOrderOriginalStorageStatus::OK;}catch(\Throwable){return AssignmentOrderOriginalStorageStatus::FAILED;}}}
final readonly class AssignmentOrderOriginalFileOutcome implements AssignmentOrderOriginalStorageOutcome{public function __construct(private AssignmentOrderOriginalStorageStatus$s,private ?AssignmentOrderOriginalPrivateContentLease$l){}public function status():AssignmentOrderOriginalStorageStatus{return$this->s;}public function lease():?AssignmentOrderOriginalPrivateContentLease{return$this->l;}}
require_once __DIR__.'/AssignmentOrderOriginalFileStage.php';
require_once __DIR__.'/AssignmentOrderOriginalFileOrphans.php';
final class AssignmentOrderOriginalFileStorage implements AssignmentOrderOriginalPrivateStorage{
 private AssignmentOrderOriginalFileOrphans $orphans;
 public function __construct(private string$r,private AssignmentOrderOriginalClock$c,private AssignmentOrderOriginalFaultInjector$f,?AssignmentOrderOriginalStorageObserver$observer=null){self::validateRoot($r);$this->r=(string)realpath($r);$this->orphans=new AssignmentOrderOriginalFileOrphans($this->r,$f,$observer);}
 public static function validateRoot(string$r):void{if($r===''||is_link($r)||!is_dir($r))throw new \RuntimeException();$r=(string)realpath($r);$uid=function_exists('posix_geteuid')?posix_geteuid():getmyuid();$s=lstat($r);$mode=$s['mode']&0777;if($s['uid']!==$uid||!in_array($mode,[0700,0750],true))throw new \RuntimeException();for($p=dirname($r);$p!==dirname($p);$p=dirname($p)){$s=lstat($p);if(!$s||is_link($p)||(($s['mode']&0002)&&!($s['mode']&01000)))throw new \RuntimeException();}}
 public function beginStage():AssignmentOrderOriginalPrivateStage
 {
  $stage=null;
  try { return AssignmentOrderOriginalFileState::mutate($this->r,function(&$s)use(&$stage){
   $n=count($s['stages'])+1;
   do {$id='stage-'.str_pad((string)$n++,4,'0',STR_PAD_LEFT);} while(array_filter($s['stages'],fn($x)=>$x['opaqueIdentity']===$id));
   $at=$this->c->nowUtc();
   $stage=new AssignmentOrderOriginalFileStage($this->r,$id,$at,$this->r.'/.stage-'.$id,$this->f);
   $s['stages'][]=['byteSize'=>0,'createdAtUtc'=>$at,'opaqueIdentity'=>$id];
   return $stage;
  }); } catch(\Throwable $e) {if($stage!==null)$stage->discardUnpublished();throw $e;}
 }
 public function listOrphans(string $cutoffUtc,int $limit,?string $cursor):AssignmentOrderOriginalOrphanPage{return $this->orphans->page($cutoffUtc,$limit,$cursor);}
 public function acquireDigestLock(string $opaqueIdentity):AssignmentOrderOriginalDigestLock{return $this->orphans->acquire($opaqueIdentity);}
 public function deleteLocked(AssignmentOrderOriginalDigestLock $lock):AssignmentOrderOriginalStorageStatus{return $this->orphans->delete($lock);}
 public function inventoryCanonicalJson():string{return json_encode(AssignmentOrderOriginalFileState::read($this->r),JSON_THROW_ON_ERROR);}
}
interface AssignmentOrderOriginalPrivateOrphanFixture{public function create(AssignmentOrderOriginalPrivateOrphanFixtureCommand $command):void;}
require_once __DIR__.'/AssignmentOrderOriginalFileOrphanFixture.php';
final class AssignmentOrderOriginalPrivateOrphanFixtureFactory
{
 public static function create(string $privateStorageRoot,string $ownershipToken,AssignmentOrderOriginalClock $clock,AssignmentOrderOriginalProductionConfig $productionConfig,AssignmentOrderOriginalFaultInjector $faults):AssignmentOrderOriginalPrivateOrphanFixture
 {
  try {$authority=new AssignmentOrderOriginalOrphanFixtureAuthority($privateStorageRoot,$ownershipToken,$productionConfig);return new AssignmentOrderOriginalFileOrphanFixture($privateStorageRoot,$authority,$clock,$faults);}
  catch(\Throwable){throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();}
 }
}
final class AssignmentOrderOriginalSystemClock implements AssignmentOrderOriginalClock{public function nowUtc():string{return gmdate('Y-m-d\TH:i:s\Z');}}
final class AssignmentOrderOriginalNoFaults implements AssignmentOrderOriginalFaultInjector{public function before(AssignmentOrderOriginalFaultPoint$p):void{}}
final class AssignmentOrderOriginalRandomIds implements AssignmentOrderOriginalIdSource{public function nextRootId():AssignmentOrderOriginalIdResult{return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED,'root-'.bin2hex(random_bytes(16)));}public function nextRevisionId():AssignmentOrderOriginalIdResult{return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED,'revision-'.bin2hex(random_bytes(16)));}}
final class AssignmentOrderOriginalDiscardSafeLog implements AssignmentOrderOriginalSafeLogObserver{public function record(string$e,array$f):void{}}
require_once __DIR__.'/ProductionAssignmentOrderOriginalFactory.php';

final class AssignmentOrderOriginalPrivateStorageFactory
{
 public static function create(string $absolutePrivateRoot,AssignmentOrderOriginalStorageObserver $observer,AssignmentOrderOriginalFaultInjector $faults):AssignmentOrderOriginalPrivateStorage
 {return new AssignmentOrderOriginalFileStorage($absolutePrivateRoot,new AssignmentOrderOriginalSystemClock(),$faults,$observer);}
}
