<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalOrphanFixtureAuthority.php';

/** @internal Used only by the declared verifier factory. */
final class AssignmentOrderOriginalFileOrphanFixture implements AssignmentOrderOriginalPrivateOrphanFixture
{
    public function __construct(private string $root,private AssignmentOrderOriginalOrphanFixtureAuthority $authority,
        private AssignmentOrderOriginalClock $clock,private AssignmentOrderOriginalFaultInjector $faults) {}
    public function create(AssignmentOrderOriginalPrivateOrphanFixtureCommand $command):void
    {
        $lock=null;
        try {
            if(!AssignmentOrderOriginalMaintenanceValues::identity($command->opaqueIdentity)
                ||!AssignmentOrderOriginalDataScalar::utc($command->createdOrFinalizedAtUtc)
                ||strlen($command->bytes)<1||strlen($command->bytes)>20971520)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
            $this->authority->validate();$now=$this->clock->nowUtc();
            if(!AssignmentOrderOriginalDataScalar::utc($now)||strcmp($command->createdOrFinalizedAtUtc,$now)>0)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
            if($this->existing(AssignmentOrderOriginalFileState::read($this->root),$command))return;
            $this->faults->before(AssignmentOrderOriginalFaultPoint::STAGE_WRITE);
            $storage=new AssignmentOrderOriginalFileStorage($this->root,$this->clock,$this->faults);
            $lock=$storage->acquireDigestLock($command->opaqueIdentity);
            if($lock->status()!==AssignmentOrderOriginalStorageStatus::OK)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
            $this->authority->validate();
            AssignmentOrderOriginalFileState::mutate($this->root,function(&$state)use($command){
                if($this->existing($state,$command))throw new AssignmentOrderOriginalFixtureReplay();
                $stage=$command->kind===AssignmentOrderOriginalPrivateOrphanFixtureKind::ABANDONED_STAGE;
                $candidate=new AssignmentOrderOriginalOrphanCandidate($stage?AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE:AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,
                    $command->opaqueIdentity,$stage?null:hash('sha256',$command->bytes),strlen($command->bytes),$command->createdOrFinalizedAtUtc);
                $paths=AssignmentOrderOriginalFileOrphans::paths($this->root,$candidate);
                foreach($paths as $path)if(file_exists($path)||is_link($path))throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
                AssignmentOrderOriginalFileState::atomic($paths[0],$command->bytes);
                $row=['byteSize'=>$candidate->byteSize,$stage?'createdAtUtc':'finalizedAtUtc'=>$candidate->createdOrFinalizedAtUtc,'opaqueIdentity'=>$candidate->opaqueIdentity];
                if(!$stage)$row['sha256']=$candidate->sha256;
                $state[$stage?'stages':'finalized'][]=$row;
            });
        } catch(AssignmentOrderOriginalFixtureReplay) {}
        catch(AssignmentOrderOriginalPrivateOrphanFixtureConflict $e){throw $e;}
        catch(\Throwable){throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();}
        finally {if($lock!==null)try{$lock->release();}catch(\Throwable){throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();}}
    }
    private function existing(array $state,AssignmentOrderOriginalPrivateOrphanFixtureCommand $command):bool
    {
        foreach(AssignmentOrderOriginalFileOrphans::inventory($state) as $item){
            if($item->opaqueIdentity!==$command->opaqueIdentity)continue;
            if($item->kind->value!==$command->kind->value||$item->byteSize!==strlen($command->bytes)
                ||$item->createdOrFinalizedAtUtc!==$command->createdOrFinalizedAtUtc
                ||($item->sha256!==null&&$item->sha256!==hash('sha256',$command->bytes)))throw new AssignmentOrderOriginalPrivateOrphanFixtureConflict();
            $found=false;
            foreach(AssignmentOrderOriginalFileOrphans::paths($this->root,$item) as $path){
                if(!is_file($path))continue;$found=true;
                if(@hash_file('sha256',$path)!==hash('sha256',$command->bytes))throw new AssignmentOrderOriginalPrivateOrphanFixtureConflict();
            }
            if(!$found)throw new AssignmentOrderOriginalPrivateOrphanFixtureUnavailable();
            return true;
        }
        return false;
    }
}
final class AssignmentOrderOriginalFixtureReplay extends \RuntimeException {}
