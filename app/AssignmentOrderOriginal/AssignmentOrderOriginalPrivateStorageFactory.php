<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__ . '/AssignmentOrderOriginalApplication.php';

final class AssignmentOrderOriginalPrivateStorageFactory
{
    public static function create(string $absolutePrivateRoot, AssignmentOrderOriginalStorageObserver $observer, AssignmentOrderOriginalFaultInjector $faults): AssignmentOrderOriginalPrivateStorage
    {
        if (!str_starts_with($absolutePrivateRoot, '/') || str_contains($absolutePrivateRoot, "\0")) throw new \InvalidArgumentException('Private root must be absolute.');
        return new FilesystemAssignmentOrderOriginalPrivateStorage($absolutePrivateRoot, $observer, $faults);
    }
}

final class FilesystemAssignmentOrderOriginalPrivateStorage implements AssignmentOrderOriginalPrivateStorage
{
    public function __construct(private string $root, private AssignmentOrderOriginalStorageObserver $observer, private AssignmentOrderOriginalFaultInjector $faults) {}
    public function beginStage(): AssignmentOrderOriginalPrivateStage
    {
        $this->faults->before(AssignmentOrderOriginalFaultPoint::STAGE);$this->ensure($this->root.'/stages');$id='stage-'.bin2hex(random_bytes(16));$path=$this->root.'/stages/'.$id;$handle=fopen($path,'x+b');if(!is_resource($handle))throw new \RuntimeException('stage unavailable');chmod($path,0600);$this->observer->observe(AssignmentOrderOriginalStorageEvent::STAGE_BEGIN,$id);return new FilesystemAssignmentOrderOriginalPrivateStage($this->root,$id,$path,$handle,$this->observer,$this->faults);
    }
    public function listOrphans(string $cutoffUtc,int $limit,?string $cursor):AssignmentOrderOriginalOrphanPage{return new FilesystemAssignmentOrderOriginalOrphanPage;}
    public function acquireDigestLock(string $opaqueIdentity):AssignmentOrderOriginalDigestLock{$this->ensure($this->root.'/locks');$path=$this->root.'/locks/'.hash('sha256',$opaqueIdentity).'.lock';$h=fopen($path,'c+b');if(!is_resource($h)||!flock($h,LOCK_EX|LOCK_NB))return new FilesystemAssignmentOrderOriginalDigestLock($opaqueIdentity,null,AssignmentOrderOriginalStorageStatus::LOCKED);$this->observer->observe(AssignmentOrderOriginalStorageEvent::DIGEST_LOCK_ACQUIRED,$opaqueIdentity);return new FilesystemAssignmentOrderOriginalDigestLock($opaqueIdentity,$h,AssignmentOrderOriginalStorageStatus::OK);}
    public function deleteLocked(AssignmentOrderOriginalDigestLock$lock):AssignmentOrderOriginalStorageStatus{$path=$this->root.'/'.$lock->opaqueIdentity();if(!is_file($path))return AssignmentOrderOriginalStorageStatus::ALREADY_PRESENT_VERIFIED;$this->observer->observe(AssignmentOrderOriginalStorageEvent::DELETE_BEGIN,$lock->opaqueIdentity());if(!unlink($path))return AssignmentOrderOriginalStorageStatus::FAILED;$this->observer->observe(AssignmentOrderOriginalStorageEvent::DELETE_DONE,$lock->opaqueIdentity());return AssignmentOrderOriginalStorageStatus::OK;}
    public function inventoryCanonicalJson():string{return '{}';}
    private function ensure(string$directory):void{if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))throw new \RuntimeException('storage unavailable');}
}
final class FilesystemAssignmentOrderOriginalPrivateStage implements AssignmentOrderOriginalPrivateStage
{
    private bool$closed=false;public function __construct(private string$root,private string$id,private string$path,private $handle,private AssignmentOrderOriginalStorageObserver$observer,private AssignmentOrderOriginalFaultInjector$faults){}
    public function write(string$chunk):AssignmentOrderOriginalStorageStatus{try{$this->faults->before(AssignmentOrderOriginalFaultPoint::STAGE_WRITE);$this->observer->observe(AssignmentOrderOriginalStorageEvent::STAGE_WRITE,$this->id);return fwrite($this->handle,$chunk)===strlen($chunk)?AssignmentOrderOriginalStorageStatus::OK:AssignmentOrderOriginalStorageStatus::FAILED;}catch(\Throwable){return AssignmentOrderOriginalStorageStatus::FAILED;}}
    public function completedBytesForInspection():string{fflush($this->handle);rewind($this->handle);$bytes=stream_get_contents($this->handle);if(!is_string($bytes))throw new \RuntimeException('stage read');$this->observer->observe(AssignmentOrderOriginalStorageEvent::STAGE_DONE,$this->id);return$bytes;}
    public function finalize(string$sha256,int$byteSize):AssignmentOrderOriginalStorageOutcome{try{$this->faults->before(AssignmentOrderOriginalFaultPoint::PRIVATE_FINALIZE);$identity='sha256/'.substr($sha256,0,2).'/'.substr($sha256,2,2).'/'.$sha256;$target=$this->root.'/'.$identity;$directory=dirname($target);if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))throw new \RuntimeException('finalize directory');if(!is_dir($this->root.'/locks')&&!mkdir($this->root.'/locks',0700,true)&&!is_dir($this->root.'/locks'))throw new \RuntimeException('lock directory');$this->observer->observe(AssignmentOrderOriginalStorageEvent::FINALIZE_BEGIN,$identity);if(!is_file($target)&&!rename($this->path,$target))throw new \RuntimeException('finalize rename');chmod($target,0600);$h=fopen($this->root.'/locks/'.hash('sha256',$identity).'.lock','c+b');if(!is_resource($h)||!flock($h,LOCK_EX|LOCK_NB))throw new \RuntimeException('lease');$this->observer->observe(AssignmentOrderOriginalStorageEvent::FINALIZE_DONE,$identity);return new FilesystemAssignmentOrderOriginalStorageOutcome(new FilesystemAssignmentOrderOriginalLease(new FilesystemAssignmentOrderOriginalContent($identity,$sha256,$byteSize),$h));}catch(\Throwable){return new FilesystemAssignmentOrderOriginalStorageOutcome(null);}}
    public function abort():AssignmentOrderOriginalStorageStatus{$this->observer->observe(AssignmentOrderOriginalStorageEvent::ABORT_BEGIN,$this->id);if(is_file($this->path)&&!unlink($this->path))return AssignmentOrderOriginalStorageStatus::FAILED;$this->observer->observe(AssignmentOrderOriginalStorageEvent::ABORT_DONE,$this->id);return AssignmentOrderOriginalStorageStatus::OK;}
    public function close():void{if($this->closed)return;$this->closed=true;if(is_resource($this->handle))fclose($this->handle);$this->observer->observe(AssignmentOrderOriginalStorageEvent::STAGE_CLOSE,$this->id);}
}
final readonly class FilesystemAssignmentOrderOriginalContent implements AssignmentOrderOriginalPrivateContent{public function __construct(private string$id,private string$hash,private int$size){}public function opaqueIdentity():string{return$this->id;}public function sha256():string{return$this->hash;}public function byteSize():int{return$this->size;}}
final class FilesystemAssignmentOrderOriginalLease implements AssignmentOrderOriginalPrivateContentLease{private bool$released=false;public function __construct(private AssignmentOrderOriginalPrivateContent$contentValue,private$handle){}public function status():AssignmentOrderOriginalStorageStatus{return AssignmentOrderOriginalStorageStatus::OK;}public function content():?AssignmentOrderOriginalPrivateContent{return$this->contentValue;}public function release():AssignmentOrderOriginalStorageStatus{if($this->released)return AssignmentOrderOriginalStorageStatus::FAILED;$this->released=true;if(!flock($this->handle,LOCK_UN)){return AssignmentOrderOriginalStorageStatus::FAILED;}fclose($this->handle);return AssignmentOrderOriginalStorageStatus::OK;}}
final readonly class FilesystemAssignmentOrderOriginalStorageOutcome implements AssignmentOrderOriginalStorageOutcome{public function __construct(private ?AssignmentOrderOriginalPrivateContentLease$leaseValue){}public function status():AssignmentOrderOriginalStorageStatus{return$this->leaseValue===null?AssignmentOrderOriginalStorageStatus::FAILED:AssignmentOrderOriginalStorageStatus::OK;}public function lease():?AssignmentOrderOriginalPrivateContentLease{return$this->leaseValue;}}
final readonly class FilesystemAssignmentOrderOriginalOrphanPage implements AssignmentOrderOriginalOrphanPage{public function status():AssignmentOrderOriginalStorageStatus{return AssignmentOrderOriginalStorageStatus::OK;}public function candidates():array{return[];}public function nextCursor():?string{return null;}}
final class FilesystemAssignmentOrderOriginalDigestLock implements AssignmentOrderOriginalDigestLock{public function __construct(private string$id,private$handle,private AssignmentOrderOriginalStorageStatus$s){}public function status():AssignmentOrderOriginalStorageStatus{return$this->s;}public function opaqueIdentity():string{return$this->id;}public function release():void{if(is_resource($this->handle)){flock($this->handle,LOCK_UN);fclose($this->handle);$this->handle=null;}}}
