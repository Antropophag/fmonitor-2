<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalMaintenanceOwnerFixture
{
    public array $trace=[],$commits=[],$rows=[],$locks=[],$deleted=[],$logs=[],$pageGets=[];
    public string $authorization='allowed',$lookup='not_found',$commit='committed',$page='ok';
    public string|\Throwable $time='2026-09-02T09:00:00Z';
    public array $items=[],$lockStates=[],$references=[],$deleteStates=[];
    public bool $releaseThrows=false,$logThrows=false;public ?string $cursor=null;
    public ?O\AssignmentOrderOriginalMaintenanceResult $stored=null;
    public O\AssignmentOrderOriginalMaintenanceApplication $application;
    public function __construct()
    {
        if(!class_exists(O\AssignmentOrderOriginalMaintenanceDependencies::class)||!class_exists(O\AssignmentOrderOriginalOrphanCandidate::class))throw new \TestFailure('INTENDED_RED: promised typed maintenance owner API absent');
        $f=$this;
        $auth=new class($f) implements O\AssignmentOrderOriginalMaintenanceAuthorizer {
            public function __construct(private OriginalMaintenanceOwnerFixture $f){}
            public function authorize(string $systemPrincipalId,string $exactCapability):O\AssignmentOrderOriginalAuthorizationStatus
            {$this->f->trace[]='authorize:'.$systemPrincipalId.':'.$exactCapability;return O\AssignmentOrderOriginalAuthorizationStatus::from($this->f->authorization);}
        };
        $clock=new class($f) implements O\AssignmentOrderOriginalClock {
            public function __construct(private OriginalMaintenanceOwnerFixture $f){}
            public function nowUtc():string{$this->f->trace[]='clock';if($this->f->time instanceof \Throwable)throw $this->f->time;return $this->f->time;}
        };
        $storage=new class($f) implements O\AssignmentOrderOriginalPrivateStorage {
            public function __construct(private OriginalMaintenanceOwnerFixture $f){}
            public function beginStage():O\AssignmentOrderOriginalPrivateStage{throw new \LogicException('No upload in maintenance');}
            public function inventoryCanonicalJson():string{return '{}';}
            public function listOrphans(string $cutoffUtc,int $limit,?string $cursor):O\AssignmentOrderOriginalOrphanPage {
                $this->f->trace[]='page';return new class($this->f) implements O\AssignmentOrderOriginalOrphanPage {
                    public function __construct(private OriginalMaintenanceOwnerFixture $f){}
                    public function status():O\AssignmentOrderOriginalStorageStatus{$this->f->pageGets[]='status';return O\AssignmentOrderOriginalStorageStatus::from($this->f->page);}
                    public function candidates():array{$this->f->pageGets[]='items';return $this->f->items;}
                    public function nextCursor():?string{$this->f->pageGets[]='cursor';return $this->f->cursor;}
                };
            }
            public function acquireDigestLock(string $opaqueIdentity):O\AssignmentOrderOriginalDigestLock {
                $this->f->trace[]='lock:'.$opaqueIdentity;return $this->f->locks[$opaqueIdentity]=new class($this->f,$opaqueIdentity) implements O\AssignmentOrderOriginalDigestLock {
                    public int $releases=0;public function __construct(private OriginalMaintenanceOwnerFixture $f,private string $id){}
                    public function status():O\AssignmentOrderOriginalStorageStatus{return O\AssignmentOrderOriginalStorageStatus::from($this->f->lockStates[$this->id]??'ok');}
                    public function opaqueIdentity():string{return $this->id;}
                    public function release():void{$this->releases++;$this->f->trace[]='release:'.$this->id;if($this->f->releaseThrows)throw new \RuntimeException('Synthetic release');}
                };
            }
            public function deleteLocked(O\AssignmentOrderOriginalDigestLock $lock):O\AssignmentOrderOriginalStorageStatus
            {$id=$lock->opaqueIdentity();$this->f->trace[]='delete:'.$id;$this->f->deleted[]=$id;return O\AssignmentOrderOriginalStorageStatus::from($this->f->deleteStates[$id]??'ok');}
        };
        $references=new class($f) implements O\AssignmentOrderOriginalRepository {
            public function __construct(private OriginalMaintenanceOwnerFixture $f){}
            public function hasCommittedContent(string $opaqueIdentity):O\AssignmentOrderOriginalReferenceLookup{$this->f->trace[]='reference:'.$opaqueIdentity;$value=array_key_exists($opaqueIdentity,$this->f->references)?$this->f->references[$opaqueIdentity]:false;return new O\AssignmentOrderOriginalReferenceValue($value===null?O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE:O\AssignmentOrderOriginalLookupStatus::FOUND,$value);}
            public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup{throw new \LogicException();}
            public function findAcceptedFingerprint(string $fingerprint):O\AssignmentOrderOriginalResultLookup{throw new \LogicException();}
            public function findLineage(string $rootOriginalId):O\AssignmentOrderOriginalLineageLookup{throw new \LogicException();}
            public function commitAccepted(O\AssignmentOrderOriginalAcceptedCommit $commit):O\AssignmentOrderOriginalCommitStatus{throw new \LogicException();}
            public function commitAttempt(O\AssignmentOrderOriginalAttemptCommit $commit):O\AssignmentOrderOriginalCommitStatus{throw new \LogicException();}
            public function evidenceCanonicalJson(int $installationCaseId,int $assignmentOrderId):string{throw new \LogicException();}
        };
        $repo=new class($f) implements O\AssignmentOrderOriginalMaintenanceRepository {
            public function __construct(private OriginalMaintenanceOwnerFixture $f){}
            public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalMaintenanceResultLookup {
                $this->f->trace[]='lookup';return new class($this->f) implements O\AssignmentOrderOriginalMaintenanceResultLookup {
                    public function __construct(private OriginalMaintenanceOwnerFixture $f){}
                    public function status():O\AssignmentOrderOriginalLookupStatus{return O\AssignmentOrderOriginalLookupStatus::from($this->f->lookup);}
                    public function result():?O\AssignmentOrderOriginalMaintenanceResult{return $this->f->stored;}
                };
            }
            public function commitResultAndAudit(O\AssignmentOrderOriginalMaintenanceCommit $commit):O\AssignmentOrderOriginalCommitStatus{$this->f->trace[]='commit';$this->f->commits[]=$commit;if($this->f->commit==='committed')$this->f->rows[]=$commit;return O\AssignmentOrderOriginalCommitStatus::from($this->f->commit);}
        };
        $observer=new class implements O\AssignmentOrderOriginalStorageObserver{public function observe(O\AssignmentOrderOriginalStorageEvent $event,?string $opaqueIdentity):void{}};
        $faults=new class($f) implements O\AssignmentOrderOriginalFaultInjector {public function __construct(private OriginalMaintenanceOwnerFixture $f){}public function before(O\AssignmentOrderOriginalFaultPoint $point):void{$this->f->trace[]='fault:'.$point->value;}};
        $log=new class($f) implements O\AssignmentOrderOriginalSafeLogObserver {public function __construct(private OriginalMaintenanceOwnerFixture $f){}public function record(string $event,array $safeFields):void{$this->f->logs[]=[$event,$safeFields];$this->f->trace[]='log:'.$safeFields['phase'];if($this->f->logThrows)throw new \RuntimeException('Synthetic logger');}};
        $this->application=O\AssignmentOrderOriginalMaintenanceVerificationFactory::create(new O\AssignmentOrderOriginalMaintenanceDependencies($auth,$clock,$storage,$references,$repo,$observer,$faults,$log));
    }
    public static function command(array $changes=[]):O\ReconcileAssignmentOrderOriginalPrivateOrphansCommand
    {return new O\ReconcileAssignmentOrderOriginalPrivateOrphansCommand(...($changes+['requestId'=>'00000000-0000-4000-8000-000000000801','systemPrincipalId'=>'ops-worker-02','cutoffUtc'=>'2026-09-02T07:30:00Z','batchLimit'=>10,'cursor'=>null]));}
    public function run(array $changes=[]):O\AssignmentOrderOriginalMaintenanceResult{return $this->application->reconcileAssignmentOrderOriginalPrivateOrphans(self::command($changes));}
    public function two():void
    {$this->items=[new O\AssignmentOrderOriginalOrphanCandidate(O\AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT,'orphan-content-0001',hash('sha256','finalized-orphan-v1'),19,'2026-09-02T07:00:00Z'),new O\AssignmentOrderOriginalOrphanCandidate(O\AssignmentOrderOriginalOrphanKind::ABANDONED_STAGE,'orphan-stage-0001',null,15,'2026-09-02T07:00:00Z')];}
    public static function result(array $values):O\AssignmentOrderOriginalMaintenanceResult
    {return new class($values) implements O\AssignmentOrderOriginalMaintenanceResult {
        public function __construct(private array $v){}public function status():O\AssignmentOrderOriginalMaintenanceStatus{return O\AssignmentOrderOriginalMaintenanceStatus::from($this->v[0]);}public function reason():?O\AssignmentOrderOriginalMaintenanceReason{return $this->v[1]===null?null:O\AssignmentOrderOriginalMaintenanceReason::from($this->v[1]);}public function retryable():bool{return $this->v[2];}public function scanned():int{return $this->v[3];}public function deleted():int{return $this->v[4];}public function retained():int{return $this->v[5];}public function failed():int{return $this->v[6];}public function nextCursor():?string{return $this->v[7];}
    };}
}
