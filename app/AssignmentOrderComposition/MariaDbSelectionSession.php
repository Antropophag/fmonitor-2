<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class MariaDbSelectionSession implements SelectionTransactionSession
{
    public ?SelectionResult $staged=null;public ?SelectionStageStatus $stageStatus=null;public int $stages=0;
    public ?SelectionIdentityAllocation $allocation=null;private bool $allocationAttempted=false;
    public function __construct(private readonly MariaDbSelectionSql $sql,private readonly int $caseId,private readonly int $objectId) {}
    public function lockedCase(): SelectionCaseLookup
    { try{return (new MariaDbSelectionFacts($this->sql))->caseRows($this->objectId,$this->caseId);}catch(\Throwable){return SelectionCaseLookup::unavailable();} }
    public function findTerminalRequest(SelectionRequestId $id): SelectionTerminalRequestLookup
    { try{return (new MariaDbSelectionRequests($this->sql))->lockedFind($id);}catch(\Throwable){return SelectionTerminalRequestLookup::unavailable();} }
    public function selectionState(): SelectionStateLookup { return (new MariaDbSelectionState($this->sql))->read($this->caseId); }
    public function allocateIdentity(SelectionSourceKind $kind,SelectionInstant $at): SelectionIdentityAllocationResult
    {
        try {
            if($this->allocationAttempted||$this->stages!==0||$kind!==SelectionSourceKind::SELECTION)throw new \RuntimeException();$this->allocationAttempted=true;
            $r=$this->sql->rows('SELECT COALESCE(MAX(order_version),0) version FROM '.$this->sql->table('fm2_assignment_order_identities').' WHERE installation_case_id=?',[$this->caseId]);
            $version=MariaDbSelectionSql::number($r[0]['version'],0,65534)+1;
            $frontier=$this->sql->rows('SELECT AUTO_INCREMENT n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$this->sql->prefix.'fm2_assignment_order_identities']);
            if(count($frontier)!==1)throw new \RuntimeException();MariaDbSelectionSql::number($frontier[0]['n']);
            $this->sql->insert('fm2_assignment_order_identities',['installation_case_id'=>$this->caseId,'order_version'=>$version,'source_kind'=>'selection','allocated_at_utc'=>MariaDbSelectionSql::time($at)]);
            $this->allocation=new SelectionIdentityAllocation($this->sql->generatedId(),$this->caseId,$version,$kind,$at);
            return SelectionIdentityAllocationResult::allocated($this->allocation);
        }catch(\OverflowException){return SelectionIdentityAllocationResult::capacityExhausted();}catch(\Throwable){return SelectionIdentityAllocationResult::persistenceError();}
    }
    public function stageAccepted(SelectionAcceptedPersistence $payload): SelectionStageResult
    {
        return $this->stage(function()use($payload){
            if($this->allocation===null||$payload->allocation!=$this->allocation)throw new \RuntimeException();
            $receipt=(new MariaDbSelectionWrites($this->sql))->accepted($payload);$this->staged=$payload->selectedResult;return $receipt;
        });
    }
    public function stageTerminalAttempt(SelectionTerminalAttemptPersistence $payload): SelectionStageResult
    {
        return $this->stage(function()use($payload){
            if($this->allocationAttempted||$payload->requestId->value!==$payload->terminalResult->requestId()->value||$payload->intent->objectId!==$this->objectId)throw new \RuntimeException();
            $w=new MariaDbSelectionWrites($this->sql);$w->request($payload->intent,$payload->terminalResult,$payload->audit);
            $id=$w->audit($payload->audit);$this->staged=$payload->terminalResult;return SelectionStageResult::terminal($id);
        });
    }
    private function stage(callable $write): SelectionStageResult
    {
        $this->stages++;
        try{if($this->stages!==1)throw new \RuntimeException();$r=$write();}
        catch(SelectionNativeRequestRace){$r=SelectionStageResult::requestRace();}
        catch(\OverflowException){$r=SelectionStageResult::capacityExhausted();}
        catch(\Throwable){$r=SelectionStageResult::persistenceError();}
        $this->stageStatus=$r->status;return $r;
    }
    public function canObserve(): bool { return !$this->allocationAttempted&&$this->stages===0; }
}
