<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** Proposes exactly one stage/decision; only the supplied UoW owns commit/rollback. */
final readonly class SelectionWork implements SelectionTransactionalWork
{
    public function __construct(private SelectionAttempt $attempt,private int $caseId,private ?SelectionResult $refusal,private array $installers,private ?EngineerSnapshot $engineer) {}
    public function run(SelectionTransactionSession $transaction): SelectionTransactionDecision
    {
        $a=$this->attempt;
        try{
            $request=$transaction->findTerminalRequest($a->command->requestId);
            if($request->status===SelectionLookupStatus::FOUND)return SelectionTransactionDecision::observedTerminal($request->payload);
            if($request->status!==SelectionLookupStatus::NOT_FOUND)return self::dependency();
            $case=$transaction->lockedCase();
            if($case->status!==SelectionLookupStatus::FOUND || $case->payload===null || !SelectionEligibility::caseValid($case->payload,$a->intent->objectId) || $case->payload->caseId!==$this->caseId)return self::dependency();
            $blocked=SelectionEligibility::caseReason($case->payload);$refusal=$blocked!==null?$a->rejected($blocked):$this->refusal;
            if($refusal!==null)return $this->terminal($transaction,$refusal);
            $lookup=$transaction->selectionState();
            if($lookup->status!==SelectionLookupStatus::FOUND || $lookup->payload===null || !SelectionStatePolicy::valid($lookup->payload))return self::dependency();
            $state=$lookup->payload;$refusal=SelectionStatePolicy::refusal($a,$state,$this->caseId);
            if($refusal!==null)return $this->terminal($transaction,$refusal);
            $latest=$state->latestSelection;$revision=$latest?->selectionRevision??0;$version=$latest?->orderVersion??0;
            if($revision>=4294967295 || $version>=65535)return SelectionTransactionDecision::rollback(SelectionRollbackCause::ALLOCATION_CAPACITY_EXHAUSTED);
            $allocation=$transaction->allocateIdentity(SelectionSourceKind::SELECTION,$a->at());
            if($allocation->status===SelectionIdentityAllocationStatus::CAPACITY_EXHAUSTED)return SelectionTransactionDecision::rollback(SelectionRollbackCause::ALLOCATION_CAPACITY_EXHAUSTED);
            $identity=$allocation->allocation;
            if($allocation->status!==SelectionIdentityAllocationStatus::ALLOCATED || $identity===null || $identity->caseId!==$this->caseId || $identity->orderVersion!==$version+1
                || $identity->sourceKind!==SelectionSourceKind::SELECTION || $identity->allocatedAt->utcRfc3339Seconds!==$a->at()->utcRfc3339Seconds)return self::persistence();
            [$composition,$hash]=SelectionIntent::composition($this->caseId,$identity->assignmentOrderId,$identity->orderVersion,$this->engineer->userId,$a->intent->installers);
            $date=SelectionScalar::selectionDate($a->at());$revision++;
            $result=SelectionResult::selected($a->command->requestId,new SelectionSuccessPayload($this->caseId,$identity->assignmentOrderId,$identity->orderVersion,$revision,$composition,$hash,$date,$a->at()->utcRfc3339Seconds));
            $previous=$latest?->assignmentOrderId;$replaces=$a->command->mode===AssignmentOrderCompositionMode::REPLACE_PENDING?$previous:null;
            $event=new SelectionSelectedEvent($a->command->requestId,$this->caseId,$identity->assignmentOrderId,$identity->orderVersion,$revision,$previous,$replaces,$hash,$a->at(),$a->command->actorUserId);
            $payload=new SelectionAcceptedPersistence($identity,$revision,$a->command->mode,$previous,$replaces,$this->engineer,$this->installers,$date,$a->at(),$a->command->actorUserId,$a->intent,$result,$event,$a->audit($result));
            return self::stage($transaction->stageAccepted($payload),$result,true);
        }catch(\Throwable){return self::persistence();}
    }
    private function terminal(SelectionTransactionSession $transaction,SelectionResult $result): SelectionTransactionDecision
    {
        $a=$this->attempt;return self::stage($transaction->stageTerminalAttempt(new SelectionTerminalAttemptPersistence($a->command->requestId,$a->intent,$result,$a->audit($result))),$result,false);
    }
    private static function stage(SelectionStageResult $stage,SelectionResult $result,bool $accepted): SelectionTransactionDecision
    {
        return match($stage->status){SelectionStageStatus::REQUEST_RACE=>SelectionTransactionDecision::requestRace(),
            SelectionStageStatus::CAPACITY_EXHAUSTED=>SelectionTransactionDecision::rollback(SelectionRollbackCause::ALLOCATION_CAPACITY_EXHAUSTED),
            SelectionStageStatus::STAGED=>($stage->auditId!==null && ($accepted?($stage->eventId!==null):($stage->eventId===null)))?SelectionTransactionDecision::commit($result):self::persistence(),
            default=>self::persistence()};
    }
    private static function dependency(): SelectionTransactionDecision { return SelectionTransactionDecision::rollback(SelectionRollbackCause::DEPENDENCY_UNAVAILABLE); }
    private static function persistence(): SelectionTransactionDecision { return SelectionTransactionDecision::rollback(SelectionRollbackCause::PERSISTENCE_FAILURE); }
}
