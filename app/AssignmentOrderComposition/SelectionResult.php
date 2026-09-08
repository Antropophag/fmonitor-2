<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionResult implements AssignmentOrderCompositionResult
{
    private function __construct(private AssignmentOrderCompositionStatus $s,private SelectionRequestId $id,private ?AssignmentOrderCompositionReason $reason,private ?SelectionSuccessPayload $payload) {}
    public static function selected(SelectionRequestId $requestId,SelectionSuccessPayload $payload): self { return self::successResult(AssignmentOrderCompositionStatus::SELECTED,$requestId,$payload); }
    public static function replayed(SelectionRequestId $requestId,SelectionSuccessPayload $payload): self { return self::successResult(AssignmentOrderCompositionStatus::REPLAYED,$requestId,$payload); }
    public static function rejected(SelectionRequestId $requestId,AssignmentOrderCompositionReason $reason): self { return self::failure(AssignmentOrderCompositionStatus::REJECTED,$requestId,$reason); }
    public static function conflict(SelectionRequestId $requestId,AssignmentOrderCompositionReason $reason): self { return self::failure(AssignmentOrderCompositionStatus::CONFLICT,$requestId,$reason); }
    public static function failed(SelectionRequestId $requestId,AssignmentOrderCompositionReason $reason): self { return self::failure(AssignmentOrderCompositionStatus::FAILED,$requestId,$reason); }
    private static function successResult(AssignmentOrderCompositionStatus $status,SelectionRequestId $id,SelectionSuccessPayload $p): self
    {
        if($p->caseId<1 || $p->assignmentOrderId<1 || $p->assignmentOrderVersion<1 || $p->assignmentOrderVersion>65535 || $p->selectionRevision<1 || $p->selectionRevision>4294967295
            || $p->compositionIdentity!=='composition-'.$p->assignmentOrderId.'-v'.$p->assignmentOrderVersion || !SelectionScalar::hash($p->compositionSha256) || !SelectionScalar::date($p->selectionDate) || !SelectionScalar::utc($p->selectedAt))throw new \InvalidArgumentException('Invalid selection result.');
        return new self($status,$id,null,$p);
    }
    private static function failure(AssignmentOrderCompositionStatus $status,SelectionRequestId $id,AssignmentOrderCompositionReason $reason): self
    {
        $allowed=match($status){
            AssignmentOrderCompositionStatus::REJECTED=>['invalid_command','authorization_denied','object_not_found','installer_required','control_engineer_required','installer_not_in_catalog','installer_not_employed','control_engineer_not_eligible','object_has_pto_act','object_completed','no_changes'],
            AssignmentOrderCompositionStatus::CONFLICT=>['request_id_conflict','stale_selection','pending_selection_exists','selection_not_found','original_already_accepted'],
            AssignmentOrderCompositionStatus::FAILED=>['dependency_unavailable','persistence_failure','persistence_outcome_unknown','allocation_capacity_exhausted'],default=>[]};
        if(!in_array($reason->value,$allowed,true))throw new \InvalidArgumentException('Invalid selection result.');return new self($status,$id,$reason,null);
    }
    public function status(): AssignmentOrderCompositionStatus { return $this->s; }
    public function reasonCode(): ?AssignmentOrderCompositionReason { return $this->reason; }
    public function retryable(): bool { return $this->s===AssignmentOrderCompositionStatus::FAILED && $this->reason!==AssignmentOrderCompositionReason::ALLOCATION_CAPACITY_EXHAUSTED; }
    public function requestId(): SelectionRequestId { return $this->id; }
    public function success(): ?SelectionSuccessPayload { return $this->payload; }
}
