<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderComposition as C;

/** In-memory public ports; command rules remain exclusively in the application under test. */
final class SelectionCommandFixture implements C\SelectionAuthorizer, C\SelectionDependencyReader,
    C\SelectionClock, C\SelectionUnitOfWork, C\SelectionTransactionSession,
    C\SelectionFreshTerminalReaderFactory, C\SelectionCloseableTerminalRequestReader,
    C\SelectionAttemptAuditWriter, C\SelectionTerminalAttemptUnitOfWork
{
    public array $trace = []; public array $requests = []; public array $selections = []; public array $audits = [];
    public array $events = []; public int $nextId = 81; public int $clockCalls = 0; public int $allocations = 0;
    public bool $caseExists = true; public C\SelectionAuthorizationStatus $authorization;
    public C\SelectionStateSnapshot $state;
    private bool $inside = false; private mixed $staged = null;
    public function __construct()
    {
        $this->authorization = C\SelectionAuthorizationStatus::ALLOWED;
        $this->state = new C\SelectionStateSnapshot(null,null,null,null,null);
    }
    public function app(): C\AssignmentOrderCompositionApplication
    {
        return C\AssignmentOrderCompositionFactory::create(new C\SelectionDependencies($this,$this,$this,$this,$this,$this,$this,$this));
    }
    public function authorize(C\UserId $actor,C\SelectionCapability $capability): C\SelectionAuthorization
    {
        $this->trace[]='authorize'; \assertSameValue(C\SelectionCapability::SELECT,$capability,'exact selection capability');
        return new C\SelectionAuthorization($this->authorization);
    }
    public function now(): C\SelectionInstantLookup
    {
        $this->trace[]='clock';$this->clockCalls++;
        return C\SelectionInstantLookup::found(new C\SelectionInstant('2026-09-05T09:00:00Z'));
    }
    public function findCaseByObject(C\InstallationObjectId $id): C\SelectionCaseLookup
    {
        $this->trace[]='case';return $this->caseExists ? C\SelectionCaseLookup::found(new C\SelectionCasePayload(4512,$id->value,false,null)) : C\SelectionCaseLookup::notFound();
    }
    public function findInstallers(C\InstallerTabIdSet $ids,C\SelectionInstant $at): C\SelectionInstallerBatchLookup
    {
        $this->trace[]='installers';$rows=[];
        foreach($ids->ascendingUniqueIds as $id){$rows[]=new C\InstallerSnapshot($id->value,'Монтажник теста','Монтажник','employed','2020-01-01',null,'synthetic-hr','2026-09-01T06:00:00Z');}
        return C\SelectionInstallerBatchLookup::found(new C\InstallerBatchPayload($rows,[]));
    }
    public function findEngineer(C\UserId $id,C\SelectionInstant $at): C\SelectionEngineerLookup
    {
        $this->trace[]='engineer';return C\SelectionEngineerLookup::found(new C\EngineerSnapshot($id->value,'Инженер теста','Инженер'));
    }
    public function findTerminalRequest(C\SelectionRequestId $id): C\SelectionTerminalRequestLookup
    {
        $this->trace[]=$this->inside?'locked-request':'request';
        return isset($this->requests[$id->value]) ? C\SelectionTerminalRequestLookup::found($this->requests[$id->value]) : C\SelectionTerminalRequestLookup::notFound();
    }
    public function lockedCase(): C\SelectionCaseLookup
    {
        $this->trace[]='locked-case';return C\SelectionCaseLookup::found(new C\SelectionCasePayload(4512,4512,false,null));
    }
    public function selectionState(): C\SelectionStateLookup
    { $this->trace[]='state';return C\SelectionStateLookup::found($this->state); }
    public function allocateIdentity(C\SelectionSourceKind $kind,C\SelectionInstant $at): C\SelectionIdentityAllocationResult
    {
        $this->trace[]='allocate';$this->allocations++;\assertSameValue(C\SelectionSourceKind::SELECTION,$kind,'selection owns allocated source kind');
        return C\SelectionIdentityAllocationResult::allocated(new C\SelectionIdentityAllocation($this->nextId++,4512,($this->state->latestSelection?->orderVersion??0)+1,$kind,$at));
    }
    public function stageAccepted(C\SelectionAcceptedPersistence $payload): C\SelectionStageResult
    {
        $this->trace[]='stage-accepted';\assertSameValue(null,$this->staged,'single staged operation');$this->staged=$payload;
        return C\SelectionStageResult::accepted(count($this->events)+1,count($this->audits)+1);
    }
    public function stageTerminalAttempt(C\SelectionTerminalAttemptPersistence $payload): C\SelectionStageResult
    {
        $this->trace[]='stage-terminal';\assertSameValue(null,$this->staged,'single staged operation');$this->staged=$payload;
        return C\SelectionStageResult::terminal(count($this->audits)+1);
    }
    public function executeForCase(int $caseId,C\SelectionTransactionalWork $work): C\SelectionUnitOfWorkResult
    {
        $this->trace[]='begin';\assertSameValue(4512,$caseId,'exact existing case serialization');$this->inside=true;$this->staged=null;
        try{
            $decision=$work->run($this);
            if($decision->kind()==='commit'){
                \assertSameValue(true,$this->staged!==null,'commit requires staged terminal');$payload=$this->staged;
                if($payload instanceof C\SelectionAcceptedPersistence){
                    \assertSameValue((new C\SelectionResultSerializer())->serialize($payload->selectedResult),(new C\SelectionResultSerializer())->serialize($decision->result()),'exact staged success committed');
                    $this->selections[]=$payload;$this->events[]=$payload->event;
                    $success=$payload->selectedResult->success();
                    $summary=new C\SelectionIdentitySummary($success->assignmentOrderId,$success->assignmentOrderVersion,$success->selectionRevision,$success->compositionIdentity,$success->compositionSha256,false);
                    $this->state=new C\SelectionStateSnapshot($summary,$summary,$this->state->latestAcceptedSelection,null,$this->state->effectiveOrder);
                    $this->store($payload->selectedResult,$payload->intent,$payload->audit);
                }else{\assertSameValue((new C\SelectionResultSerializer())->serialize($payload->terminalResult),(new C\SelectionResultSerializer())->serialize($decision->result()),'exact staged rejection committed');$this->store($payload->terminalResult,$payload->intent,$payload->audit);}
                $this->trace[]='commit';return C\SelectionUnitOfWorkResult::committed($decision->result());
            }
            if($decision->kind()==='observedTerminal'){\assertSameValue(null,$this->staged,'observed terminal is read-only');return C\SelectionUnitOfWorkResult::observedTerminal($decision->terminalRecord());}
            $this->trace[]='rollback';return $decision->kind()==='requestRace'?C\SelectionUnitOfWorkResult::requestRace():C\SelectionUnitOfWorkResult::rolledBack($decision->rollbackCause());
        }finally{$this->inside=false;$this->staged=null;}
    }
    private function store(C\AssignmentOrderCompositionResult $result,C\SelectionNormalizedIntent $intent,C\SelectionSafeAttemptAudit $audit):void
    {
        $this->requests[$result->requestId()->value]=new C\SelectionTerminalRequestRecord($result->requestId(),$intent,$result);$this->audits[]=$audit;
    }
    public function execute(C\SelectionTerminalAttemptPersistence $payload): C\SelectionUnitOfWorkResult
    {
        $this->trace[]='terminal-without-case';$this->store($payload->terminalResult,$payload->intent,$payload->audit);
        return C\SelectionUnitOfWorkResult::committed($payload->terminalResult);
    }
    public function append(C\SelectionSafeAttemptAudit $audit): C\SelectionAuditWriteResult
    { $this->trace[]='independent-audit';$this->audits[]=$audit;return C\SelectionAuditWriteResult::committed(count($this->audits)); }
    public function open(): C\SelectionCloseableTerminalRequestReader { $this->trace[]='fresh-open';return $this; }
    public function close():void { $this->trace[]='fresh-close'; }
}
