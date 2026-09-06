<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Owns the writer and its exclusion before metadata publication. */
final class AssignmentOrderOriginalFileStage implements AssignmentOrderOriginalPrivateStage
{
    private $handle = null;
    private $exclusion = null;
    private bool $closed = false;
    public function __construct(private string $root, private string $id, private string $at,
        private string $file, private AssignmentOrderOriginalFaultInjector $faults)
    {
        try {
            $this->exclusion = AssignmentOrderOriginalFileState::lock($root.'/.lock-'.hash('sha256',$id));
            if (!flock($this->exclusion, LOCK_EX|LOCK_NB)) throw new \RuntimeException();
            $this->handle = @fopen($file,'x+b');
            if (!$this->handle) throw new \RuntimeException();
            if (!chmod($file,0600)) throw new \RuntimeException();
        } catch (\Throwable $e) {
            $created = is_resource($this->handle);
            try { $this->closeOwned(); } finally { if ($created) @unlink($file); }
            throw $e;
        }
    }
    public function write(string $bytes): AssignmentOrderOriginalStorageStatus
    {
        try {
            if ($this->closed) throw new \RuntimeException();
            $this->faults->before(AssignmentOrderOriginalFaultPoint::STAGE_WRITE);
            for ($offset=0;$offset<strlen($bytes);$offset+=$written) {
                $written=fwrite($this->handle,substr($bytes,$offset));
                if (!is_int($written)||$written<1) throw new \RuntimeException();
            }
            if (!fflush($this->handle)) throw new \RuntimeException();
            AssignmentOrderOriginalFileState::mutate($this->root,function(&$state)use($bytes){
                foreach($state['stages'] as &$row) if($row['opaqueIdentity']===$this->id) $row['byteSize']+=strlen($bytes);
            });
            return AssignmentOrderOriginalStorageStatus::OK;
        } catch (\Throwable) { return AssignmentOrderOriginalStorageStatus::FAILED; }
    }
    public function completedBytesForInspection(): string
    {
        if ($this->closed || !fflush($this->handle)) throw new \RuntimeException();
        $bytes=@file_get_contents($this->file);
        if (!is_string($bytes)) throw new \RuntimeException();
        return $bytes;
    }
    public function finalize(string $sha256,int $byteSize): AssignmentOrderOriginalStorageOutcome
    {
        $lock=null;
        try {
            $this->faults->before(AssignmentOrderOriginalFaultPoint::PRIVATE_FINALIZE);
            if ($this->closed || !preg_match('/^[a-f0-9]{64}$/D',$sha256) || !fflush($this->handle)
                || (function_exists('fsync')&&!fsync($this->handle))) throw new \RuntimeException();
            clearstatcache(true,$this->file);
            if (filesize($this->file)!==$byteSize || hash_file('sha256',$this->file)!==$sha256) throw new \RuntimeException();
            $id='content-sha256-'.$sha256;
            $lock=AssignmentOrderOriginalFileState::lock($this->root.'/.lock-'.hash('sha256',$id));
            if (!flock($lock,LOCK_EX|LOCK_NB)) return new AssignmentOrderOriginalFileOutcome(AssignmentOrderOriginalStorageStatus::LOCKED,null);
            $target=$this->root.'/content-'.$sha256.'.pdf'; $status=AssignmentOrderOriginalStorageStatus::OK;
            if (is_file($target)) {
                if (is_link($target)||filesize($target)!==$byteSize||hash_file('sha256',$target)!==$sha256||!unlink($this->file)) throw new \RuntimeException();
                $status=AssignmentOrderOriginalStorageStatus::ALREADY_PRESENT_VERIFIED;
            } elseif (!@rename($this->file,$target)||!chmod($target,0600)||filesize($target)!==$byteSize||hash_file('sha256',$target)!==$sha256) throw new \RuntimeException();
            $this->closed=true; $handle=$this->handle; $this->handle=null;
            if (!fclose($handle)) throw new \RuntimeException();
            AssignmentOrderOriginalFileState::mutate($this->root,function(&$state)use($id,$sha256,$byteSize){
                $state['stages']=array_values(array_filter($state['stages'],fn($row)=>$row['opaqueIdentity']!==$this->id));
                foreach($state['finalized'] as $row) if($row['opaqueIdentity']===$id) return;
                $state['finalized'][]=['byteSize'=>$byteSize,'finalizedAtUtc'=>$this->at,'opaqueIdentity'=>$id,'sha256'=>$sha256];
            });
            $lease=new AssignmentOrderOriginalFileLease($lock,new AssignmentOrderOriginalFileContent($id,$sha256,$byteSize),$this->faults);
            $lock=null;
            return new AssignmentOrderOriginalFileOutcome($status,$lease);
        } catch (\Throwable) { return new AssignmentOrderOriginalFileOutcome(AssignmentOrderOriginalStorageStatus::FAILED,null); }
        finally { if(is_resource($lock)) { try {flock($lock,LOCK_UN);} catch(\Throwable) {} try {fclose($lock);} catch(\Throwable) {} } }
    }
    public function abort(): AssignmentOrderOriginalStorageStatus
    {
        try {
            $this->faults->before(AssignmentOrderOriginalFaultPoint::STAGE_ABORT);
            if (is_file($this->file) && !unlink($this->file)) throw new \RuntimeException();
            AssignmentOrderOriginalFileState::mutate($this->root,function(&$state){
                $state['stages']=array_values(array_filter($state['stages'],fn($row)=>$row['opaqueIdentity']!==$this->id));
            });
            $status=AssignmentOrderOriginalStorageStatus::OK;
        } catch (\Throwable) { $status=AssignmentOrderOriginalStorageStatus::FAILED; }
        try {$this->closeOwned();} catch(\Throwable) {$status=AssignmentOrderOriginalStorageStatus::FAILED;}
        return $status;
    }
    public function close(): void
    { $this->closeOwned(); $this->faults->before(AssignmentOrderOriginalFaultPoint::STAGE_CLOSE); }
    public function discardUnpublished(): void
    { try {if(is_file($this->file)) @unlink($this->file);} finally {$this->closeOwned();} }
    private function closeOwned(): void
    {
        $this->closed=true; $handle=$this->handle; $lock=$this->exclusion;
        $this->handle=$this->exclusion=null; $failed=false;
        try { if(is_resource($handle)&&!fclose($handle)) $failed=true; }
        catch(\Throwable) {$failed=true;}
        if(is_resource($lock)) {
            try {if(!flock($lock,LOCK_UN)) $failed=true;} catch(\Throwable) {$failed=true;}
            try {if(!fclose($lock)) $failed=true;} catch(\Throwable) {$failed=true;}
        }
        if($failed) throw new \RuntimeException();
    }
    public function __destruct() {try {$this->closeOwned();} catch(\Throwable) {}}
}
