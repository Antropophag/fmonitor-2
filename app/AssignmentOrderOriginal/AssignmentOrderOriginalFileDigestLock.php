<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal The owner identity is private to one FileOrphans instance. */
final class AssignmentOrderOriginalFileDigestLock implements AssignmentOrderOriginalDigestLock
{
    private bool $released=false;
    private function __construct(private object $owner,private string $id,
        private AssignmentOrderOriginalStorageStatus $value,private $handle,
        private ?AssignmentOrderOriginalOrphanCandidate $candidate) {}
    public static function acquire(object $owner,string $root,string $id,?AssignmentOrderOriginalOrphanCandidate $candidate,
        AssignmentOrderOriginalFaultInjector $faults,?AssignmentOrderOriginalStorageObserver $observer):self
    {
        $handle=null;
        try {
            if(!AssignmentOrderOriginalMaintenanceValues::identity($id)) throw new \RuntimeException();
            try {$faults->before(AssignmentOrderOriginalFaultPoint::DIGEST_LOCK);}
            catch(\Throwable) {return new self($owner,$id,AssignmentOrderOriginalStorageStatus::LOCKED,null,$candidate);}
            $handle=AssignmentOrderOriginalFileState::lock($root.'/.lock-'.hash('sha256',$id));
            if(!flock($handle,LOCK_EX|LOCK_NB)) {
                $value=AssignmentOrderOriginalStorageStatus::LOCKED;
            } else {
                $observer?->observe(AssignmentOrderOriginalStorageEvent::DIGEST_LOCK_ACQUIRED,$id);
                $result=new self($owner,$id,AssignmentOrderOriginalStorageStatus::OK,$handle,$candidate);
                $handle=null;
                return $result;
            }
        } catch(\Throwable) {$value=AssignmentOrderOriginalStorageStatus::FAILED;}
        finally {
            if(is_resource($handle)) {
                try {flock($handle,LOCK_UN);} catch(\Throwable) {}
                try {fclose($handle);} catch(\Throwable) {}
            }
        }
        return new self($owner,$id,$value,null,$candidate);
    }
    public function status():AssignmentOrderOriginalStorageStatus {return $this->value;}
    public function opaqueIdentity():string {return $this->id;}
    public function candidateFor(object $owner):?AssignmentOrderOriginalOrphanCandidate
    {return $owner===$this->owner&&!$this->released&&$this->value===AssignmentOrderOriginalStorageStatus::OK?$this->candidate:null;}
    public function release():void
    {
        if($this->released) return;
        $this->released=true; $handle=$this->handle; $this->handle=null; $failed=false;
        if(is_resource($handle)) {
            try {if(!flock($handle,LOCK_UN))$failed=true;} catch(\Throwable) {$failed=true;}
            try {if(!fclose($handle))$failed=true;} catch(\Throwable) {$failed=true;}
        }
        if($failed) throw new \RuntimeException();
    }
    public function __destruct() {try {$this->release();} catch(\Throwable) {}}
}

final readonly class AssignmentOrderOriginalFileOrphanPage implements AssignmentOrderOriginalOrphanPage
{
    public function __construct(private AssignmentOrderOriginalStorageStatus $value,private array $items=[],private ?string $cursor=null) {}
    public function status():AssignmentOrderOriginalStorageStatus {return $this->value;}
    public function candidates():array {return $this->items;}
    public function nextCursor():?string {return $this->cursor;}
}
