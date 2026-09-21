<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class MariaDbObjectDetailsEditStore
{
    private function __construct(private \mysqli $db,private string $prefix,private MariaDbEffectiveObjectDetails $effective){}

    public static function create(\mysqli $db,string $prefix):self
    {
        return new self($db,$prefix,MariaDbEffectiveObjectDetails::create($db,$prefix,$prefix));
    }

    public function apply(ObjectDetailsEditCommand $command,array $patch,string $fingerprint,ObjectDetailsFieldRegistry $registry,callable $clock):array
    {
        try {
            $this->db->begin_transaction();
            $actor=$this->authorization($command->actorId,$command->objectId);
            if($actor===null){$this->db->rollback();return['status'=>'rejected','reasonCode'=>'authorization_denied'];}
            $statement=$this->db->prepare("SELECT id FROM `{$this->prefix}fm2_installation_cases` WHERE legacy_installation_object_id=? FOR UPDATE");
            $statement->execute([$command->objectId]);$cases=$statement->get_result()->fetch_all(MYSQLI_ASSOC);
            if(count($cases)!==1){$this->db->rollback();return['status'=>'rejected','reasonCode'=>'authorization_denied'];}
            $caseId=(int)$cases[0]['id'];
            $statement=$this->db->prepare("SELECT request_fingerprint,outcome_json FROM `{$this->prefix}fm2_object_detail_edit_requests` WHERE request_id=? FOR UPDATE");
            $statement->execute([$command->requestId]);$previous=$statement->get_result()->fetch_assoc();
            if($previous){$this->db->rollback();if(!hash_equals($previous['request_fingerprint'],$fingerprint))return['status'=>'conflict','reasonCode'=>'request_id_conflict'];$out=json_decode($previous['outcome_json'],true,flags:JSON_THROW_ON_ERROR);$out['status']='replayed';return$out;}
            $statement=$this->db->prepare("SELECT revision,values_json FROM `{$this->prefix}fm2_object_detail_edits` WHERE object_id=? FOR UPDATE");
            $statement->execute([$command->objectId]);$current=$statement->get_result()->fetch_assoc();$revision=(int)($current['revision']??0);
            if($revision!==$command->expectedRevision){$this->db->rollback();return['status'=>'conflict','reasonCode'=>'object_details_changed','revision'=>$revision];}
            $values=$current?json_decode($current['values_json'],true,flags:JSON_THROW_ON_ERROR):[];$effective=$this->effective->read($command->objectId);$changes=[];
            foreach($patch as$field=>$value){$old=$effective[$field]['value']??null;if($this->same($old,$value))continue;$definition=$registry->definitions()[$field];$oldSnapshot=$registry->snapshot($field,$old,false);if(($effective[$field]['display']??null)!==null)$oldSnapshot['display']=$effective[$field]['display'];$changes[]=['field'=>$field,'label'=>$definition['label'],'old'=>$oldSnapshot,'new'=>$registry->snapshot($field,$value)];$values[$field]=$value;}
            if($changes===[]){$this->db->rollback();return['status'=>'noop','revision'=>$revision];}
            $next=$revision+1;$at=$clock();$mysql=(new \DateTimeImmutable($at))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');$json=json_encode($values,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            if($current){$statement=$this->db->prepare("UPDATE `{$this->prefix}fm2_object_detail_edits` SET revision=?,values_json=?,updated_at_utc=?,updated_by_user_id=? WHERE object_id=? AND revision=?");$statement->execute([$next,$json,$mysql,$command->actorId,$command->objectId,$revision]);if($statement->affected_rows!==1)throw new \RuntimeException();}
            else{$statement=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_object_detail_edits`(object_id,revision,values_json,updated_at_utc,updated_by_user_id)VALUES(?,?,?,?,?)");$statement->execute([$command->objectId,$next,$json,$mysql,$command->actorId]);}
            $changesJson=json_encode($changes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$statement=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_object_detail_edit_events`(installation_case_id,object_id,revision,event_type,changes_json,actor_user_id,actor_display_snapshot,occurred_at_utc,request_id)VALUES(?,?,?,'object_details_changed',?,?,?,?,?)");$statement->execute([$caseId,$command->objectId,$next,$changesJson,$command->actorId,$actor['full_name'],$mysql,$command->requestId]);$eventId=$this->db->insert_id;$out=['status'=>'applied','revision'=>$next,'eventId'=>$eventId];
            $statement=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_object_detail_edit_requests`(request_id,request_fingerprint,object_id,outcome_json,created_at_utc)VALUES(?,?,?,?,?)");$statement->execute([$command->requestId,$fingerprint,$command->objectId,json_encode($out,JSON_THROW_ON_ERROR),$mysql]);$this->db->commit();return$out;
        } catch(\Throwable) {
            try{$this->db->rollback();}catch(\Throwable){}
            return['status'=>'failed','reasonCode'=>'persistence_unavailable'];
        }
    }

    private function authorization(int $actor,int $objectId):?array
    {
        $statement=$this->db->prepare("SELECT u.full_name,MAX(BINARY r.code IN('fkr_operator','manager') AND BINARY rp.permission='objects.details.edit') can_edit,MAX(BINARY rp.permission='objects.read') can_read_object FROM `{$this->prefix}fm2_pilot_users` u JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id AND r.status=1 JOIN `{$this->prefix}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id JOIN `{$this->prefix}fm2_installation_cases` scope_case ON scope_case.legacy_installation_object_id=? WHERE u.user_id=? AND u.status=1 AND BINARY u.activation_state='active' GROUP BY u.user_id,u.full_name,scope_case.id");
        $statement->execute([$objectId,$actor]);$row=$statement->get_result()->fetch_assoc();
        return$row&&(int)$row['can_edit']===1&&(int)$row['can_read_object']===1?$row:null;
    }

    private function same(mixed $left,mixed $right):bool{return get_debug_type($left)===get_debug_type($right)?$left===$right:(is_scalar($left)&&is_scalar($right)&&(string)$left===(string)$right);}
}
