<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** Inserts only within the caller-owned transaction. */
final readonly class MariaDbSelectionWrites
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function request(SelectionNormalizedIntent $i,SelectionResult $r,SelectionSafeAttemptAudit $audit): void
    {
        self::validateTerminal($i,$r,$audit);$p=$r->success();
        $row=['request_id'=>$r->requestId()->value,'operation_fingerprint'=>$i->fingerprint,'actor_user_id'=>$i->actorUserId,'control_engineer_user_id'=>$i->engineerUserId,'expected_selection_revision'=>$i->expectedRevision,'installation_object_id'=>$i->objectId,
            'installer_tab_ids_json'=>SelectionScalar::json(array_map(static fn($id)=>$id->value,$i->installers->ascendingUniqueIds)),'mode'=>$i->mode->value,'status'=>$r->status()->value,'reason_code'=>$r->reasonCode()?->value,'retryable'=>0,
            'case_id'=>$p?->caseId,'assignment_order_id'=>$p?->assignmentOrderId,'assignment_order_version'=>$p?->assignmentOrderVersion,'selection_revision'=>$p?->selectionRevision,'composition_identity'=>$p?->compositionIdentity,'composition_sha256'=>$p?->compositionSha256,'selection_date'=>$p?->selectionDate,'selected_at_utc'=>$p===null?null:MariaDbSelectionSql::time(new SelectionInstant($p->selectedAt)),'terminal_at_utc'=>MariaDbSelectionSql::time($audit->attemptedAt)];
        try{$this->sql->insert('fm2_assignment_order_selection_requests',$row);}catch(\mysqli_sql_exception $e){if($e->getCode()===1062)throw new SelectionNativeRequestRace();throw $e;}
    }
    public static function validateTerminal(SelectionNormalizedIntent $i,SelectionResult $r,SelectionSafeAttemptAudit $a): void
    {
        if(!SelectionIntent::valid($i)||!SelectionScalar::uuid($r->requestId()->value)||$r->requestId()->value!==$a->requestId->value||$a->actor->value!==$i->actorUserId||$a->objectId->value!==$i->objectId||$a->mode!==$i->mode||$a->status!==$r->status()||$a->reason!==$r->reasonCode()||!SelectionScalar::utc($a->attemptedAt->utcRfc3339Seconds))throw new \RuntimeException('Selection payload mismatch.');
    }
    public function audit(SelectionSafeAttemptAudit $a): int
    {
        $this->sql->insert('fm2_assignment_order_selection_audits',['request_id'=>$a->requestId->value,'actor_user_id'=>$a->actor->value,'installation_object_id'=>$a->objectId->value,'mode'=>$a->mode->value,'status'=>$a->status->value,'reason_code'=>$a->reason?->value,'attempted_at_utc'=>MariaDbSelectionSql::time($a->attemptedAt)]);
        return $this->sql->generatedId();
    }
    public function accepted(SelectionAcceptedPersistence $p): SelectionStageResult
    {
        $r=$p->selectedResult;if(!$r instanceof SelectionResult||$r->status()!==AssignmentOrderCompositionStatus::SELECTED)throw new \RuntimeException();
        self::validateSnapshots($p);
        self::validateTerminal($p->intent,$r,$p->audit);$s=$r->success();$a=$p->allocation;$e=$p->event;
        [$identity,$hash]=SelectionIntent::composition($a->caseId,$a->assignmentOrderId,$a->orderVersion,$p->engineer->userId,$p->intent->installers);
        if([$s->caseId,$s->assignmentOrderId,$s->assignmentOrderVersion,$s->selectionRevision,$s->compositionIdentity,$s->compositionSha256,$s->selectedAt,$s->selectionDate]!==[$a->caseId,$a->assignmentOrderId,$a->orderVersion,$p->selectionRevision,$identity,$hash,$p->selectedAt->utcRfc3339Seconds,$p->selectionDate]
            ||$p->selectionRevision!==$p->intent->expectedRevision+1||$p->mode!==$p->intent->mode||$p->actor->value!==$p->intent->actorUserId||$p->engineer->userId!==$p->intent->engineerUserId||$a->allocatedAt!=$p->selectedAt||$p->audit->attemptedAt!=$p->selectedAt
            ||[$e->requestId->value,$e->caseId,$e->orderId,$e->orderVersion,$e->selectionRevision,$e->previousSelectionOrderId,$e->replacesSelectionOrderId,$e->compositionSha256,$e->occurredAt->utcRfc3339Seconds,$e->actor->value]!==[$r->requestId()->value,$a->caseId,$a->assignmentOrderId,$a->orderVersion,$p->selectionRevision,$p->previousSelectionOrderId,$p->replacesSelectionOrderId,$hash,$s->selectedAt,$p->actor->value])throw new \RuntimeException('Selection accepted echoes mismatch.');
        if(array_map(static fn($x)=>$x->tabId,$p->installers)!==array_map(static fn($x)=>$x->value,$p->intent->installers->ascendingUniqueIds)||$p->installers===[])throw new \RuntimeException();
        $this->sql->insert('fm2_assignment_order_selections',['assignment_order_id'=>$a->assignmentOrderId,'installation_case_id'=>$a->caseId,'order_version'=>$a->orderVersion,'selection_revision'=>$p->selectionRevision,'mode'=>$p->mode->value,'previous_selection_order_id'=>$p->previousSelectionOrderId,'replaces_selection_order_id'=>$p->replacesSelectionOrderId,'composition_identity'=>$identity,'composition_sha256'=>$hash,'control_engineer_user_id'=>$p->engineer->userId,'control_engineer_fio_snapshot'=>$p->engineer->fio,'control_engineer_position_snapshot'=>$p->engineer->position,'selection_date'=>$p->selectionDate,'selected_at_utc'=>MariaDbSelectionSql::time($p->selectedAt),'selected_by_user_id'=>$p->actor->value]);
        $proofStorage=\FMonitor2\InstallationProcess\AssignmentOrderSelectionUnknownEmploymentSchemaMigration::isReady($this->sql->db,$this->sql->prefix);
        foreach($p->installers as $m){$member=['assignment_order_id'=>$a->assignmentOrderId,'installer_tab_id'=>$m->tabId,'fio_snapshot'=>$m->fio,'position_snapshot'=>$m->position,'employment_status_snapshot'=>$m->employmentStatus,'employed_from_snapshot'=>$m->employedFrom,'employed_to_snapshot'=>$m->employedTo,'workforce_source_snapshot'=>$m->workforceSource,'workforce_source_updated_at_snapshot'=>$m->workforceSourceUpdatedAt];if($proofStorage)$member+=['employment_proof_kind'=>$m->employedFrom===null?'full_current':'known_period','authority_system_snapshot'=>$m->authoritySystem,'delivery_system_snapshot'=>$m->deliverySystem,'delivery_person_id_snapshot'=>$m->deliveryPersonId,'reconciliation_state_snapshot'=>$m->reconciliationState,'full_snapshot_json'=>$m->fullSnapshot===null?null:SelectionScalar::json($m->fullSnapshot)];elseif($m->employedFrom===null)throw new \RuntimeException('Unknown employment proof storage unavailable.');$this->sql->insert('fm2_assignment_order_selection_members',$member);}
        $this->request($p->intent,$r,$p->audit);
        $this->sql->insert('fm2_assignment_order_selection_events',['event_type'=>'assignment_order_composition_selected','request_id'=>$e->requestId->value,'installation_case_id'=>$e->caseId,'assignment_order_id'=>$e->orderId,'assignment_order_version'=>$e->orderVersion,'selection_revision'=>$e->selectionRevision,'previous_selection_order_id'=>$e->previousSelectionOrderId,'replaces_selection_order_id'=>$e->replacesSelectionOrderId,'composition_sha256'=>$e->compositionSha256,'occurred_at_utc'=>MariaDbSelectionSql::time($e->occurredAt),'actor_user_id'=>$e->actor->value]);
        $event=$this->sql->generatedId();return SelectionStageResult::accepted($event,$this->audit($p->audit));
    }
    private static function validateSnapshots(SelectionAcceptedPersistence $p): void
    {
        if(!SelectionScalar::utc($p->selectedAt->utcRfc3339Seconds)
            ||$p->selectionDate!==SelectionScalar::selectionDate($p->selectedAt)
            ||!SelectionScalar::text($p->engineer->fio,300)||!SelectionScalar::text($p->engineer->position,300)
            ||!array_is_list($p->installers)||$p->installers===[])throw new \RuntimeException('Invalid accepted selection snapshot.');
        foreach($p->installers as $m) {
            if(!$m instanceof InstallerSnapshot||$m->tabId<1||!SelectionScalar::text($m->fio,300)||!SelectionScalar::text($m->position,300)
                ||$m->employmentStatus!=='employed'||($m->employedFrom!==null&&(!SelectionScalar::date($m->employedFrom)||$m->employedFrom>$p->selectionDate))
                ||($m->employedTo!==null&&(!SelectionScalar::date($m->employedTo)||($m->employedFrom!==null&&$m->employedTo<$m->employedFrom)||$m->employedTo<$p->selectionDate))
                ||($m->employedFrom===null&&!SelectionEmploymentProof::full($m))
                ||!SelectionScalar::text($m->workforceSource,80)||!SelectionScalar::sourceInstant($m->workforceSourceUpdatedAt))throw new \RuntimeException('Invalid accepted installer snapshot.');
        }
    }

}
