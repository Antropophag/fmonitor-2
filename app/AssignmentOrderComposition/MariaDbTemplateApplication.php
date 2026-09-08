<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** One public owner for generation and its append-only audit; never stores PDF bytes. */
final readonly class MariaDbTemplateApplication implements AssignmentOrderTemplateApplication
{
    public function __construct(private MariaDbSelectionSql $sql,private SelectionClock $clock,private \Closure $render) {}
    public function generateAssignmentOrderTemplate(int $caseId,int $orderId,int $actorId):array
    {
        if(min($caseId,$orderId,$actorId)<1)return AssignmentOrderTemplateResult::failure('rejected','invalid_command');
        $authority=(new MariaDbSelectionFacts($this->sql))->authorize(new UserId($actorId),SelectionCapability::SELECT);
        if($authority->status!==SelectionAuthorizationStatus::ALLOWED)return AssignmentOrderTemplateResult::failure($authority->status===SelectionAuthorizationStatus::DENIED?'rejected':'failed',$authority->status===SelectionAuthorizationStatus::DENIED?'authorization_denied':'dependency_unavailable');
        $owned=false;$commitAttempted=false;$status='failed';$reason='dependency_unavailable';
        try {
            if(!$this->sql->idle())throw new \RuntimeException();
            if(!$this->sql->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED')||!$this->sql->db->begin_transaction())throw new \RuntimeException();$owned=true;
            $cases=$this->sql->rows('SELECT id FROM '.$this->sql->table('fm2_installation_cases').' WHERE id=? FOR UPDATE',[$caseId]);
            if($cases===[]){$status='rejected';$reason='order_not_found';throw new \RuntimeException();}
            $reader=new MariaDbTemplateSource($this->sql);$source=$reader->load($caseId,$orderId);
            if($source===null){$status='rejected';$reason='order_not_found';throw new \RuntimeException();}
            $latest=$this->sql->rows('SELECT assignment_order_id FROM '.$this->sql->table('fm2_assignment_order_selections').' WHERE installation_case_id=? ORDER BY selection_revision DESC LIMIT 1',[$caseId]);
            if(count($latest)!==1)throw new \RuntimeException();
            if(MariaDbSelectionSql::number($latest[0]['assignment_order_id'])!==$orderId){$status='conflict';$reason='target_not_current';throw new \RuntimeException();}
            $case=(new MariaDbSelectionFacts($this->sql))->caseRows($source['objectId'],$caseId);
            if($case->status!==SelectionLookupStatus::FOUND||$case->payload===null)throw new \RuntimeException();
            $blocked=SelectionEligibility::caseReason($case->payload);if($blocked!==null){$status='rejected';$reason=$blocked->value;throw new \RuntimeException();}
            $instant=$this->clock->now();
            if($instant->status!==SelectionLookupStatus::FOUND||$instant->payload===null||!SelectionScalar::utc($instant->payload->utcRfc3339Seconds))throw new \RuntimeException();
            $at=$instant->payload;$date=SelectionScalar::selectionDate($at);$input=$reader->input($source,$date);
            $reason='render_failure';$bytes=AssignmentOrderTemplateResult::pdf(($this->render)($input));if($bytes===null)throw new \RuntimeException();
            $reason='persistence_failure';$h=$source['header'];
            $payload=['assignmentOrderId'=>$orderId,'assignmentOrderVersion'=>$source['version'],'compositionIdentity'=>$h['composition_identity'],'compositionSha256'=>$h['composition_sha256'],'templateDate'=>$date];
            $this->sql->insert('fm2_process_events',['installation_case_id'=>$caseId,'event_type'=>'assignment_order_template_generated','occurred_at'=>$at->utcRfc3339Seconds,'actor_user_id'=>$actorId,'payload_json'=>SelectionScalar::json($payload)]);
            try{$this->sql->generatedId();}catch(\OverflowException){$reason='allocation_capacity_exhausted';throw new \RuntimeException();}
            $commitAttempted=true;if(!$this->sql->db->commit())throw new \RuntimeException();$owned=false;
            return AssignmentOrderTemplateResult::generated($orderId,$date,$bytes);
        }catch(\Throwable){
            $released=true;if($owned)try{$released=$this->sql->db->rollback();}catch(\Throwable){$released=false;}
            if($commitAttempted||!$released)return AssignmentOrderTemplateResult::failure('failed','persistence_outcome_unknown');
            return AssignmentOrderTemplateResult::failure($status,$reason);
        }
    }
}
