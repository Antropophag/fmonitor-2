<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\AssignmentOrderOriginal as O;

/** Owns the durable signed-start intent, original submission, application and immediate opening. */
final readonly class MariaDbOriginalStartOwner
{
    public function __construct(private MariaDbAssignmentOrderApplicationSql$sql,private O\AssignmentOrderOriginalApplication$original,private SelectionClock$clock){}

    public function submit(O\SubmitAssignmentOrderOriginalCommand$c,int$objectId,string$appendixStartDate):array
    {
        $out=static fn(string$status,?string$reason,bool$retry,?O\AssignmentOrderOriginalResult$original=null,?array$opening=null):array=>['status'=>$status,'reasonCode'=>$reason,'retryable'=>$retry,'original'=>$original,'opening'=>$opening];
        if($objectId<1||!self::date($appendixStartDate))return$out('rejected','invalid_command',false);
        $s=$this->sql;
        try{
            if(!$s->idle()||!\FMonitor2\InstallationProcess\AssignmentOrderOriginalStartSchemaMigration::isCompleteCompatible($s->db,$s->prefix))return$out('failed','dependency_unavailable',true);
            $permission=$c->mode===O\AssignmentOrderOriginalMode::INITIAL?'assignment_order.original.upload':'assignment_order.original.correct';
            if(!$this->authorized($c->actorUserId,$permission)||!$this->authorized($c->actorUserId,'installation.open'))return$out('rejected','authorization_denied',false);
            $cases=$s->rows('SELECT id FROM '.$s->table('fm2_installation_cases').' WHERE legacy_installation_object_id=?',[$objectId]);
            if(count($cases)!==1||(int)$cases[0]['id']!==$c->installationCaseId)return$out('rejected','object_not_found',false);
            if($c->mode===O\AssignmentOrderOriginalMode::CORRECTION&&$this->opened($objectId)){
                $prior=$this->startForRevision((string)$c->targetRevisionId);
                if($prior===null||$prior!==$appendixStartDate)return$out('rejected','appendix_start_changed',false);
            }
            $bytes=$this->bytes($c->upload->stream);if($bytes===null)return$out('failed','dependency_unavailable',true);
            $copy=new O\SubmitAssignmentOrderOriginalCommand($c->requestId,$c->mode,$c->installationCaseId,$c->assignmentOrderId,$c->actorUserId,$c->documentDate,$c->compositionConfirmed,$c->rootOriginalId,$c->targetRevisionId,$c->expectedCurrentRevisionId,$c->correctionReason,new O\AssignmentOrderOriginalUpload(new O\AssignmentOrderOriginalMemoryStream($bytes),$c->upload->originalFilename,$c->upload->declaredMediaType));
            $sha=hash('sha256',$bytes);$size=strlen($bytes);$fingerprint=$this->fingerprint($c,$objectId,$appendixStartDate,$sha,$size);
            $intent=$this->intent($c->requestId);
            if($intent!==null&&!hash_equals($intent['semantic_fingerprint'],$fingerprint))return$out('conflict','request_id_conflict',false);
            if($intent===null&&$size>=5){
                $instant=$this->clock->now();if($instant->status!==SelectionLookupStatus::FOUND||$instant->payload===null)return$out('failed','dependency_unavailable',true);
                try{$s->execute('INSERT INTO '.$s->table('fm2_assignment_order_original_starts').' (request_id,installation_case_id,object_id,assignment_order_id,actor_user_id,mode,document_date,appendix_start_date,pdf_sha256,byte_size,semantic_fingerprint,created_at_utc) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',[$c->requestId,$c->installationCaseId,$objectId,$c->assignmentOrderId,$c->actorUserId,$c->mode->value,$c->documentDate,$appendixStartDate,$sha,$size,$fingerprint,self::dbTime($instant->payload->utcRfc3339Seconds)]);}catch(\Throwable){$intent=$this->intent($c->requestId);if($intent===null||!hash_equals($intent['semantic_fingerprint'],$fingerprint))return$out('conflict','request_id_conflict',false);}
            }
            $receipt=$this->original->submitAssignmentOrderOriginal($copy);
            if(!in_array($receipt->status(),[O\AssignmentOrderOriginalStatus::ACCEPTED,O\AssignmentOrderOriginalStatus::REPLAYED],true))return$out($receipt->status()->value,$receipt->reasonCode()?->value,$receipt->retryable(),$receipt);
            $canonical=$this->canonical((string)$receipt->currentRevisionId());
            if($canonical===null||$canonical['appendix_start_date']!==$appendixStartDate||!hash_equals($canonical['semantic_fingerprint'],$fingerprint))return$out('conflict','canonical_start_conflict',false,$receipt);
            $operationRequest=(string)$canonical['request_id'];
            $opening=$this->opened($objectId)?$this->applyOnly($c,$objectId,$operationRequest,$receipt):$this->open($c,$objectId,$operationRequest,$receipt,$appendixStartDate);
            if(!($opening['accepted']??false))return$out('pending',(string)($opening['reasonCode']??'dependency_unavailable'),true,$receipt,$opening);
            $status=$receipt->status()===O\AssignmentOrderOriginalStatus::REPLAYED||($opening['replayed']??false)?'replayed':'accepted';
            return$out($status,null,false,$receipt,$opening);
        }catch(\Throwable){return$out('failed','dependency_unavailable',true);}
    }

    private function open(O\SubmitAssignmentOrderOriginalCommand$c,int$objectId,string$operationRequest,O\AssignmentOrderOriginalResult$r,string$date):array
    {
        $sequence=$this->sequence($objectId);
        $command=new OpenConfirmedOriginalCommand($operationRequest,$objectId,$c->assignmentOrderId,(string)$r->currentRevisionId(),$sequence,$date,$c->actorUserId);
        return(new MariaDbConfirmedOriginalOpening($this->sql,$this->clock))->openPersistedOriginal($command);
    }

    private function applyOnly(O\SubmitAssignmentOrderOriginalCommand$c,int$objectId,string$operationRequest,O\AssignmentOrderOriginalResult$r):array
    {
        $s=$this->sql;$owned=false;$committing=false;
        try{
            $reader=O\AssignmentOrderOriginalApplicationReferenceFactory::create($s->db,$s->prefix);$lookup=$reader->readCurrent($objectId,$c->assignmentOrderId);
            if($lookup->status!==O\AssignmentOrderOriginalApplicationReferenceStatus::FOUND||$lookup->reference===null)throw new \RuntimeException('original_unavailable');
            $instant=$this->clock->now();if($instant->status!==SelectionLookupStatus::FOUND||$instant->payload===null)throw new \RuntimeException('dependency_unavailable');
            $s->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');$s->db->begin_transaction();$owned=true;
            $rows=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_applications').' WHERE installation_case_id=? ORDER BY application_sequence DESC LIMIT 1 FOR UPDATE',[(int)$lookup->reference->metadata()['caseId']]);
            if($rows!==[]&&(int)$rows[0]['assignment_order_id']===$c->assignmentOrderId&&$rows[0]['original_revision_id']===$r->currentRevisionId()){$s->db->rollback();$owned=false;return['accepted'=>true,'replayed'=>true,'processState'=>'working','applicationId'=>(int)$rows[0]['application_id']];}
            $sequence=$rows===[]?0:(int)$rows[0]['application_sequence'];$operation=new MariaDbAssignmentOrderApplicationOperation($s,$reader);
            $result=$operation->perform(new ApplyAssignmentOrderOriginalCommand($operationRequest,$objectId,$c->assignmentOrderId,(string)$r->currentRevisionId(),$sequence,$c->actorUserId),$lookup->reference,$instant->payload->utcRfc3339Seconds,'installation.open',true);
            if(!in_array($result->status,['applied','replayed'],true)||$result->application===null)throw new \RuntimeException($result->reason??'dependency_unavailable');
            $committing=true;$s->db->commit();$owned=false;return['accepted'=>true,'replayed'=>$result->status==='replayed','processState'=>'working','applicationId'=>$result->application['applicationId']];
        }catch(\Throwable$e){if($owned)try{$s->db->rollback();}catch(\Throwable){$committing=true;}return['accepted'=>false,'reasonCode'=>$committing?'persistence_outcome_unknown':(in_array($e->getMessage(),['authorization_denied','application_changed','original_changed','target_not_current','reapplication_after_opening','effective_date_before_current','effective_date_before_opening'],true)?$e->getMessage():'dependency_unavailable')];}
    }

    private function authorized(int$actor,string$permission):bool
    {
        $s=$this->sql;$local=(int)$s->rows('SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$s->prefix.'fm2_pilot_users'])[0]['n']===1;
        if($local)return$s->rows('SELECT u.user_id FROM '.$s->table('fm2_pilot_users').' u JOIN '.$s->table('fm2_pilot_user_roles').' ur ON ur.user_id=u.user_id JOIN '.$s->table('fm2_pilot_roles').' r ON r.role_id=ur.role_id JOIN '.$s->table('fm2_pilot_role_permissions').' p ON p.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND BINARY u.activation_state=\'active\' AND r.status=1 AND BINARY p.permission=?',[$actor,$permission])!==[];
        return$s->rows('SELECT u.id FROM '.$s->table('users').' u JOIN '.$s->table('users_roles').' r ON r.id=u.role_id JOIN '.$s->table('fm2_process_user_capabilities').' c ON c.user_id=u.id WHERE u.id=? AND u.status=1 AND r.status=1 AND BINARY c.capability=?',[$actor,$permission])!==[];
    }
    private function bytes(O\AssignmentOrderOriginalByteStream$stream):?string{$bytes='';while(true){$r=$stream->read(65536);if($r->status===O\AssignmentOrderOriginalStreamReadStatus::EOF)break;if($r->status!==O\AssignmentOrderOriginalStreamReadStatus::BYTES||$r->bytes==='')return null;$bytes.=$r->bytes;if(strlen($bytes)>20971520)return null;}return$bytes;}
    private function intent(string$request):?array{$r=$this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_original_starts').' WHERE request_id=?',[$request]);return count($r)===1?$r[0]:null;}
    private function canonical(string$revision):?array{$r=$this->sql->rows('SELECT s.* FROM '.$this->sql->table('fm2_assignment_order_original_revisions').' v JOIN '.$this->sql->table('fm2_assignment_order_original_starts').' s ON s.request_id=v.request_id WHERE v.revision_id=?',[$revision]);return count($r)===1?$r[0]:null;}
    private function startForRevision(string$revision):?string{return$this->canonical($revision)['appendix_start_date']??null;}
    private function opened(int$object):bool{$r=$this->sql->rows('SELECT actual_start_date,opened_at FROM '.$this->sql->table('fm2_installation_cases').' WHERE legacy_installation_object_id=?',[$object]);return count($r)===1&&$r[0]['actual_start_date']!==null&&$r[0]['opened_at']!==null;}
    private function sequence(int$object):int{$r=$this->sql->rows('SELECT a.application_sequence FROM '.$this->sql->table('fm2_installation_cases').' c LEFT JOIN '.$this->sql->table('fm2_assignment_order_applications').' a ON a.installation_case_id=c.id WHERE c.legacy_installation_object_id=? ORDER BY a.application_sequence DESC LIMIT 1',[$object]);return$r===[]||$r[0]['application_sequence']===null?0:(int)$r[0]['application_sequence'];}
    private function fingerprint(O\SubmitAssignmentOrderOriginalCommand$c,int$object,string$date,string$sha,int$size):string{return hash('sha256',SelectionScalar::json([$c->mode->value,$c->installationCaseId,$object,$c->assignmentOrderId,$c->actorUserId,$c->documentDate,$c->compositionConfirmed,$c->rootOriginalId,$c->targetRevisionId,$c->expectedCurrentRevisionId,$c->correctionReason,$c->upload->originalFilename,$c->upload->declaredMediaType,$date,$sha,$size]));}
    private static function date(string$v):bool{if(preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)!==1)return false;$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);return$d!==false&&$d->format('Y-m-d')===$v;}
    private static function dbTime(string$v):string{return str_replace(['T','Z'],[' ','.000000'],$v);}
}
