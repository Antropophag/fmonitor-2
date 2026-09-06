<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** One invocation owns one lazily acquired instant, including all recovery audits. */
final class SelectionAttempt
{
    private bool $read=false; private ?SelectionInstant $instant=null;
    public function __construct(public readonly SelectionDependencies $ports,public readonly SelectAssignmentOrderCompositionCommand $command,public readonly SelectionNormalizedIntent $intent) {}
    public function at(): ?SelectionInstant
    {
        if($this->read)return $this->instant;$this->read=true;
        try{$lookup=$this->ports->clock->now();if($lookup->status===SelectionLookupStatus::FOUND && $lookup->payload!==null && SelectionScalar::utc($lookup->payload->utcRfc3339Seconds))$this->instant=$lookup->payload;}catch(\Throwable){}
        return $this->instant;
    }
    public function failed(AssignmentOrderCompositionReason $reason): SelectionResult { return SelectionResult::failed($this->command->requestId,$reason); }
    public function rejected(AssignmentOrderCompositionReason $reason): SelectionResult { return SelectionResult::rejected($this->command->requestId,$reason); }
    public function conflict(AssignmentOrderCompositionReason $reason): SelectionResult { return SelectionResult::conflict($this->command->requestId,$reason); }
    public function audit(SelectionResult $result): SelectionSafeAttemptAudit
    {
        $at=$this->at();if($at===null)throw new \LogicException('Selection attempt instant unavailable.');
        return new SelectionSafeAttemptAudit($this->command->requestId,$this->command->actorUserId,$this->command->installationObjectId,$this->command->mode,$result->status(),$result->reasonCode(),$at);
    }
    public function independentAudit(SelectionResult $result): SelectionResult
    {
        if($this->at()===null)return $this->failed(AssignmentOrderCompositionReason::DEPENDENCY_UNAVAILABLE);
        try{$written=$this->ports->audits->append($this->audit($result));return match($written->status){
            SelectionAuditWriteStatus::COMMITTED=>$result,
            SelectionAuditWriteStatus::ROLLED_BACK=>$this->failed(AssignmentOrderCompositionReason::PERSISTENCE_FAILURE),
            SelectionAuditWriteStatus::CAPACITY_EXHAUSTED=>$this->failed(AssignmentOrderCompositionReason::ALLOCATION_CAPACITY_EXHAUSTED),
            default=>$this->failed(AssignmentOrderCompositionReason::PERSISTENCE_OUTCOME_UNKNOWN)};
        }catch(\Throwable){return $this->failed(AssignmentOrderCompositionReason::PERSISTENCE_OUTCOME_UNKNOWN);}
    }
}
