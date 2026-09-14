<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\AssignmentOrderOriginal as O;

/** One explicit opening command owns composition application and opening atomically. */
final readonly class MariaDbConfirmedOriginalOpening
{
    public function __construct(private MariaDbAssignmentOrderApplicationSql $sql,private SelectionClock $clock) {}
    public function openConfirmedOriginal(OpenConfirmedOriginalCommand $c):array
    {
        $failure=static fn(string $reason):array=>['accepted'=>false,'reasonCode'=>$reason];
        if(!$this->valid($c))return$failure('invalid_command');$s=$this->sql;$owned=false;$committing=false;
        try {
            if(!$s->idle())return$failure('dependency_unavailable');
            $originals=O\AssignmentOrderOriginalApplicationReferenceFactory::create($s->db,$s->prefix);
            $applicationOperation=new MariaDbAssignmentOrderApplicationOperation($s,$originals);
            if(!$applicationOperation->authorized($c->actorId,false,'installation.open'))return$failure('authorization_denied');
            $lookup=$originals->readCurrent($c->objectId,$c->orderId);
            if($lookup->status!==O\AssignmentOrderOriginalApplicationReferenceStatus::FOUND||$lookup->reference===null)return$failure('original_unavailable');
            $reference=$lookup->reference;$metadata=$reference->metadata();
            if($metadata['revisionId']!==$c->originalRevisionId)return$failure('original_changed');
            $instant=$this->clock->now();if($instant->status!==SelectionLookupStatus::FOUND||$instant->payload===null)return$failure('dependency_unavailable');$at=$instant->payload;
            $today=SelectionScalar::selectionDate($at);if($c->actualStartDate>$today||$c->actualStartDate<$metadata['documentDate'])return$failure('actual_start_before_order_or_future');
            $fingerprint=hash('sha256',SelectionScalar::json([$c->requestId,$c->objectId,$c->orderId,$c->originalRevisionId,$c->expectedApplicationSequence,$c->actualStartDate,$c->actorId]));
            if(!$s->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED')||!$s->db->begin_transaction())throw new \RuntimeException('dependency_unavailable');$owned=true;
            $cases=$s->rows('SELECT * FROM '.$s->table('fm2_installation_cases').' WHERE legacy_installation_object_id=? FOR UPDATE',[$c->objectId]);
            if(count($cases)!==1||(int)$cases[0]['id']!==$metadata['caseId'])throw new \RuntimeException('object_not_found');$case=$cases[0];$caseId=(int)$case['id'];
            if(!$applicationOperation->authorized($c->actorId,true,'installation.open'))throw new \RuntimeException('authorization_denied');
            if($case['actual_start_date']!==null||$case['opened_at']!==null){
                $events=$s->rows('SELECT payload_json FROM '.$s->table('fm2_process_events')." WHERE installation_case_id=? AND event_type='installation_opened_from_original' ORDER BY id DESC LIMIT 1",[$caseId]);
                $event=$events===[]?[]:json_decode($events[0]['payload_json'],true,512,JSON_THROW_ON_ERROR);
                if(($event['requestId']??null)!==$c->requestId)throw new \RuntimeException('already_open');
                if(!is_string($event['fingerprint']??null)||!hash_equals($event['fingerprint'],$fingerprint))throw new \RuntimeException('request_id_conflict');
                if($case['actual_start_date']!==$c->actualStartDate||(int)$case['opened_by_user_id']!==$c->actorId)throw new \RuntimeException('dependency_unavailable');
                $id=(int)($event['application']['applicationId']??0);if($id<1)throw new \RuntimeException('dependency_unavailable');
                if(!$s->db->rollback())throw new \RuntimeException('dependency_unavailable');$owned=false;return['accepted'=>true,'processState'=>'working','actualStartDate'=>$c->actualStartDate,'applicationId'=>$id];
            }
            $selection=$s->rows('SELECT assignment_order_id FROM '.$s->table('fm2_assignment_order_selections').' WHERE installation_case_id=? ORDER BY selection_revision DESC LIMIT 1 FOR UPDATE',[$caseId]);
            if(count($selection)!==1||(int)$selection[0]['assignment_order_id']!==$c->orderId)throw new \RuntimeException('target_not_current');
            $rows=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_applications').' WHERE installation_case_id=? ORDER BY application_sequence DESC LIMIT 1 FOR UPDATE',[$caseId]);
            $sequence=$rows===[]?0:(int)$rows[0]['application_sequence'];if($sequence!==$c->expectedApplicationSequence)throw new \RuntimeException('application_changed');
            if($rows!==[]&&(int)$rows[0]['assignment_order_id']===$c->orderId&&$rows[0]['original_revision_id']===$c->originalRevisionId){
                $application=AssignmentOrderApplicationPayload::fromRow($rows[0]);
            }else{
                $apply=new ApplyAssignmentOrderOriginalCommand($c->requestId,$c->objectId,$c->orderId,$c->originalRevisionId,$c->expectedApplicationSequence,$c->actorId);
                $result=$applicationOperation->perform($apply,$reference,$at->utcRfc3339Seconds,'installation.open');
                if(!in_array($result->status,['applied','replayed'],true)||$result->application===null)throw new \RuntimeException($result->reason??'dependency_unavailable');
                $application=$result->application;
            }
            $opening=new MariaDbOriginalOpening($s->db,$s->prefix,$this->clock);
            $result=$opening->openPreparedWithinTransaction($c->objectId,$c->actualStartDate,$application['applicationId'],$c->actorId,$application,$originals,$reference,$at,['requestId'=>$c->requestId,'fingerprint'=>$fingerprint]);
            $committing=true;if(!$s->db->commit())throw new \RuntimeException();$owned=false;return$result;
        }catch(\Throwable $e){
            if($owned)try{$s->db->rollback();}catch(\Throwable){$committing=true;}
            $allowed=['invalid_command','authorization_denied','object_not_found','original_changed','original_requires_reapplication','application_changed','target_not_current','already_open','request_id_conflict','actual_start_before_order_or_future','completion_document_exists','object_completed','object_has_pto_act','installer_not_employed','control_engineer_required','control_engineer_not_eligible','document_date_in_future'];
            return$failure($committing?'persistence_outcome_unknown':(in_array($e->getMessage(),$allowed,true)?$e->getMessage():'dependency_unavailable'));
        }
    }
    private function valid(OpenConfirmedOriginalCommand $c):bool
    {
        return$c->objectId>0&&$c->orderId>0&&$c->actorId>0&&$c->expectedApplicationSequence>=0&&$c->expectedApplicationSequence<=2147483647&&SelectionScalar::date($c->actualStartDate)
            &&preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$c->requestId)===1
            &&preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,79}$/D',$c->originalRevisionId)===1;
    }
}
