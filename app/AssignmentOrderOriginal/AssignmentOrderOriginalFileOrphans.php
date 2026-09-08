<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalFileDigestLock.php';

/** @internal Native candidate snapshots and deletion share upload's exclusion domain. */
final class AssignmentOrderOriginalFileOrphans
{
    private object $owner;
    private array $listed=[];
    public function __construct(private string $root,private AssignmentOrderOriginalFaultInjector $faults,
        private ?AssignmentOrderOriginalStorageObserver $observer) {$this->owner=new \stdClass();}
    public function page(string $cutoff,int $limit,?string $cursor):AssignmentOrderOriginalOrphanPage
    {
        try {
            if(!AssignmentOrderOriginalDataScalar::utc($cutoff)||$limit<1||$limit>1000) throw new \UnexpectedValueException();
            $after=AssignmentOrderOriginalMaintenanceValues::cursor($cursor);
            $all=self::inventory(AssignmentOrderOriginalFileState::read($this->root));
            $all=array_values(array_filter($all,fn($x)=>strcmp($x->createdOrFinalizedAtUtc,$cutoff)<=0
                &&($after===null||self::compare([$x->createdOrFinalizedAtUtc,$x->opaqueIdentity],$after)>0)));
            usort($all,fn($a,$b)=>self::compare([$a->createdOrFinalizedAtUtc,$a->opaqueIdentity],[$b->createdOrFinalizedAtUtc,$b->opaqueIdentity]));
            $items=array_slice($all,0,$limit); $next=null;
            if(count($all)>$limit) {$last=$items[array_key_last($items)];$next=AssignmentOrderOriginalMaintenanceValues::encode([$last->createdOrFinalizedAtUtc,$last->opaqueIdentity]);}
            $this->listed=[];
            foreach($items as $item) $this->listed[$item->opaqueIdentity]=$item;
            return new AssignmentOrderOriginalFileOrphanPage(AssignmentOrderOriginalStorageStatus::OK,$items,$next);
        } catch(\Throwable) {return new AssignmentOrderOriginalFileOrphanPage(AssignmentOrderOriginalStorageStatus::FAILED);}
    }
    public function acquire(string $id):AssignmentOrderOriginalDigestLock
    {return AssignmentOrderOriginalFileDigestLock::acquire($this->owner,$this->root,$id,$this->listed[$id]??null,$this->faults,$this->observer);}
    public function delete(AssignmentOrderOriginalDigestLock $lock):AssignmentOrderOriginalStorageStatus
    {
        $candidate=$lock instanceof AssignmentOrderOriginalFileDigestLock?$lock->candidateFor($this->owner):null;
        if($candidate===null) return AssignmentOrderOriginalStorageStatus::FAILED;
        try {
            AssignmentOrderOriginalFileState::mutate($this->root,function(&$state)use($candidate){
                $all=self::inventory($state); $current=null;
                foreach($all as $item) if($item->opaqueIdentity===$candidate->opaqueIdentity) $current=$item;
                if($current!==null && $current!=$candidate) throw new \UnexpectedValueException();
                $this->observer?->observe(AssignmentOrderOriginalStorageEvent::DELETE_BEGIN,$candidate->opaqueIdentity);
                $this->faults->before(AssignmentOrderOriginalFaultPoint::ORPHAN_DELETE);
                // With no metadata there is no authority to derive an upload path.
                if($current!==null) foreach(self::paths($this->root,$current) as $path) {
                    clearstatcache(true,$path);
                    if(is_link($path)|| (file_exists($path)&&!is_file($path))) throw new \RuntimeException();
                    if(is_file($path)&&!unlink($path)) throw new \RuntimeException();
                }
                $key=$candidate->kind===AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE?'stages':'finalized';
                $state[$key]=array_values(array_filter($state[$key],fn($row)=>$row['opaqueIdentity']!==$candidate->opaqueIdentity));
            });
        } catch(\Throwable) {return AssignmentOrderOriginalStorageStatus::FAILED;}
        try {$this->observer?->observe(AssignmentOrderOriginalStorageEvent::DELETE_DONE,$candidate->opaqueIdentity);} catch(\Throwable) {}
        return AssignmentOrderOriginalStorageStatus::OK;
    }
    private static function compare(array $a,array $b):int
    {return strcmp($a[0],$b[0])?:strcmp($a[1],$b[1]);}
    public static function inventory(array $state):array
    {
        $all=[]; $seen=[];
        foreach(['stages','finalized'] as $key) {
            if(!isset($state[$key])||!is_array($state[$key])||!array_is_list($state[$key])) throw new \UnexpectedValueException();
            $stage=$key==='stages'; $time=$stage?'createdAtUtc':'finalizedAtUtc';
            foreach($state[$key] as $row) {
                if(!is_array($row)||!isset($row['opaqueIdentity'],$row[$time],$row['byteSize'])
                    ||!is_string($row['opaqueIdentity'])||!AssignmentOrderOriginalMaintenanceValues::identity($row['opaqueIdentity'])
                    ||isset($seen[$row['opaqueIdentity']])||!is_string($row[$time])||!AssignmentOrderOriginalDataScalar::utc($row[$time])
                    ||!is_int($row['byteSize'])||$row['byteSize']<($stage?0:1)||$row['byteSize']>20971520
                    ||($stage?isset($row['sha256']):!AssignmentOrderOriginalDataScalar::hash($row['sha256']??null))) throw new \UnexpectedValueException();
                $seen[$row['opaqueIdentity']]=true;
                $all[]=new AssignmentOrderOriginalOrphanCandidate($stage?AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE:AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,
                    $row['opaqueIdentity'],$stage?null:$row['sha256'],$row['byteSize'],$row[$time]);
            }
        }
        return $all;
    }
    public static function paths(string $root,AssignmentOrderOriginalOrphanCandidate $item):array
    {
        $stage=$item->kind===AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE;
        $paths=[$root.'/fixture-'.hash('sha256',$item->opaqueIdentity).($stage?'.stage':'.pdf')];
        if($stage&&preg_match('/^stage-[0-9]{4,}$/D',$item->opaqueIdentity)) $paths[]=$root.'/.stage-'.$item->opaqueIdentity;
        if(!$stage&&$item->opaqueIdentity==='content-sha256-'.$item->sha256) $paths[]=$root.'/content-'.$item->sha256.'.pdf';
        return $paths;
    }
}
