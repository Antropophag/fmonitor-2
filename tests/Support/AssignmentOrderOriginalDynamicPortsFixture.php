<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalDynamicIds implements O\AssignmentOrderOriginalIdSource
{
    public int $rootCalls=0; public int $revisionCalls=0;
    public function __construct(private array $roots,private array $revisions) {}
    private function take(array $values,int $index): O\AssignmentOrderOriginalIdResult
    {
        if(!array_key_exists($index,$values))throw new \LogicException('unexpected extra ID request');
        $value=$values[$index]; if($value instanceof \Throwable)throw $value; return $value;
    }
    public function nextRootId(): O\AssignmentOrderOriginalIdResult { return $this->take($this->roots,$this->rootCalls++); }
    public function nextRevisionId(): O\AssignmentOrderOriginalIdResult { return $this->take($this->revisions,$this->revisionCalls++); }
}
final class OriginalDynamicClock implements O\AssignmentOrderOriginalClock
{
    public int $calls=0;
    public function __construct(private string|\Throwable $value) {}
    public function nowUtc(): string { ++$this->calls; if($this->value instanceof \Throwable)throw $this->value; return $this->value; }
}
final class OriginalDynamicRepository implements O\AssignmentOrderOriginalRepository,O\AssignmentOrderOriginalAssignmentLineageRepository
{
    public array $fingerprints=[]; public array $accepted=[]; public array $attempts=[];
    public int $lineageCalls=0;
    public function __construct(public bool $realFingerprintUnavailable=false,public bool $correction=false) {}
    public function findTerminalRequest(string $requestId): O\AssignmentOrderOriginalResultLookup { return new AssignmentOrderOriginalInitialResultLookup(); }
    public function findAcceptedFingerprint(string $fingerprint): O\AssignmentOrderOriginalResultLookup
    {
        $this->fingerprints[]=$fingerprint;
        return new O\AssignmentOrderOriginalResultLookupValue($fingerprint!==''&&$this->realFingerprintUnavailable ? O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE : O\AssignmentOrderOriginalLookupStatus::NOT_FOUND);
    }
    public function findLineage(string $rootOriginalId): O\AssignmentOrderOriginalLineageLookup
    {
        ++$this->lineageCalls;
        if(!$this->correction||$rootOriginalId!=='original-0001')throw new \LogicException('unexpected lineage');
        return new O\AssignmentOrderOriginalMariaDbLineage(O\AssignmentOrderOriginalLookupStatus::FOUND,[
            'root_original_id'=>'original-0001','current_revision_id'=>'revision-0001','current_revision_number'=>1,'installation_case_id'=>4512,'assignment_order_id'=>81,
            'composition_identity'=>'composition-81-v1','composition_sha256'=>'388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',
            'current_document_date'=>'2026-09-01','current_pdf_sha256'=>'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',
        ],['revision-0001']);
    }
    public function findLineageForAssignmentOrder(int $installationCaseId,int $assignmentOrderId):O\AssignmentOrderOriginalLineageLookup
    {if($installationCaseId!==4512||$assignmentOrderId!==81||$this->accepted!==[])throw new \LogicException('Unexpected initial fixture precheck');return new AssignmentOrderOriginalInitialLineageLookup();}
    public function commitAccepted(O\AssignmentOrderOriginalAcceptedCommit $commit): O\AssignmentOrderOriginalCommitStatus { $this->accepted[]=$commit; return O\AssignmentOrderOriginalCommitStatus::COMMITTED; }
    public function commitAttempt(O\AssignmentOrderOriginalAttemptCommit $commit): O\AssignmentOrderOriginalCommitStatus { $this->attempts[]=$commit; return O\AssignmentOrderOriginalCommitStatus::COMMITTED; }
    public function hasCommittedContent(string $opaqueIdentity): O\AssignmentOrderOriginalReferenceLookup { throw new \LogicException('unexpected maintenance'); }
    public function evidenceCanonicalJson(int $caseId,int $orderId):string
    { return json_encode(['existing'=>$this->correction?['original-0001','revision-0001',1,'2026-09-01']:null,'new'=>$this->accepted,'attempts'=>$this->attempts],JSON_THROW_ON_ERROR); }
}
final class OriginalDynamicStage implements O\AssignmentOrderOriginalPrivateStage
{
    private AssignmentOrderOriginalInitialStage $inner;
    public int $finalizeCalls=0; public int $abortCalls=0;public int $closeCalls=0;
    public ?AssignmentOrderOriginalInitialLease $lease=null;
    public function __construct(){ $this->inner=new AssignmentOrderOriginalInitialStage(); }
    public function write(string $chunk):O\AssignmentOrderOriginalStorageStatus {return $this->inner->write($chunk);}
    public function completedBytesForInspection():string{return $this->inner->completedBytesForInspection();}
    public function finalize(string $sha256,int $byteSize):O\AssignmentOrderOriginalStorageOutcome
    {++$this->finalizeCalls;$result=$this->inner->finalize($sha256,$byteSize);$this->lease=$this->inner->lease;return $result;}
    public function abort():O\AssignmentOrderOriginalStorageStatus{++$this->abortCalls;return $this->inner->abort();}
    public function close():void{++$this->closeCalls;$this->inner->close();}
}
final class OriginalDynamicStorage implements O\AssignmentOrderOriginalPrivateStorage
{
    public int $beginCalls=0; public ?OriginalDynamicStage $stage=null;
    public function beginStage():O\AssignmentOrderOriginalPrivateStage{++$this->beginCalls;return $this->stage=new OriginalDynamicStage();}
    public function listOrphans(string $cutoffUtc,int $limit,?string $cursor):O\AssignmentOrderOriginalOrphanPage{throw new \LogicException('unexpected maintenance');}
    public function acquireDigestLock(string $opaqueIdentity):O\AssignmentOrderOriginalDigestLock{throw new \LogicException('unexpected maintenance');}
    public function deleteLocked(O\AssignmentOrderOriginalDigestLock $lock):O\AssignmentOrderOriginalStorageStatus{throw new \LogicException('unexpected maintenance');}
    public function inventoryCanonicalJson():string{return '{"stages":[],"finalized":[]}';}
}
final class OriginalDynamicStream implements O\AssignmentOrderOriginalByteStream
{
    private AssignmentOrderOriginalInitialStream $inner;
    public int $readCalls=0;public int $closeCalls=0;
    public function __construct(string $bytes){$this->inner=new AssignmentOrderOriginalInitialStream($bytes);}
    public function read(int $maximumBytes):O\AssignmentOrderOriginalStreamRead{++$this->readCalls;return $this->inner->read($maximumBytes);}
    public function close():void{++$this->closeCalls;$this->inner->close();}
}
