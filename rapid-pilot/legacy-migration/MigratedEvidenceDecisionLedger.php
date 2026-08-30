<?php
declare(strict_types=1);

require_once __DIR__.'/MigratedEvidenceReconciliation.php';
require_once __DIR__.'/MigratedEvidenceProjectionStore.php';

final class MigratedEvidenceDecisionLedger
{
    private const OUTCOMES=['acknowledge','reject_evidence','request_source_correction','map_link'];

    public function __construct(private readonly mysqli $db,private readonly string $prefix)
    {
        if(preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)throw new InvalidArgumentException('Invalid local table prefix');
    }

    public function ensureSchema():void
    {
        $p=$this->prefix;
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_migrated_evidence_decisions`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,operation_id CHAR(36) NOT NULL,request_sha256 CHAR(64) NOT NULL,snapshot_id BIGINT UNSIGNED NOT NULL,snapshot_sha256 CHAR(64) NOT NULL,projection_sha256 CHAR(64) NOT NULL,source_locator VARCHAR(500) NOT NULL,issue_code VARCHAR(80) NOT NULL,outcome VARCHAR(40) NOT NULL,target_locator VARCHAR(500) NULL,reason VARCHAR(1000) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,UNIQUE KEY uq_operation(operation_id),KEY ix_snapshot_issue(snapshot_id,issue_code,id),KEY ix_actor(actor_user_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function decide(array $command):array
    {
        $normalized=$this->validate($command);$decisionRequest=$normalized;unset($decisionRequest['occurredAt']);$requestHash=hash('sha256',json_encode($decisionRequest,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $this->db->begin_transaction();
        try{
            $existing=$this->operation((string)$normalized['operationId'],true);
            if($existing!==null){if(!hash_equals((string)$existing['request_sha256'],$requestHash))throw new DomainException('Operation id was already used for another decision.');$this->db->commit();return ['status'=>'duplicate','decisionId'=>(int)$existing['id']];}
            if(!$this->authorized((int)$normalized['actorUserId']))throw new DomainException('Actor is not authorized for migrated evidence reconciliation.');
            $projection=$this->projection((int)$normalized['snapshotId']);
            if(!hash_equals((string)$normalized['snapshotSha256'],(string)$projection['contentSha256'])||!hash_equals((string)$normalized['projectionSha256'],(string)$projection['projectionHash']))throw new DomainException('Migrated evidence projection is stale or unavailable.');
            if(!hash_equals((string)$normalized['sourceLocator'],(string)$projection['sourceLocator'])||!in_array($normalized['issueCode'],$projection['conflictCodes'],true))throw new DomainException('Migrated evidence issue reference is invalid.');
            $s=$this->db->prepare("INSERT INTO `{$this->prefix}fm2_migrated_evidence_decisions`(operation_id,request_sha256,snapshot_id,snapshot_sha256,projection_sha256,source_locator,issue_code,outcome,target_locator,reason,actor_user_id,occurred_at)VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param('ssisssssssis',$normalized['operationId'],$requestHash,$normalized['snapshotId'],$normalized['snapshotSha256'],$normalized['projectionSha256'],$normalized['sourceLocator'],$normalized['issueCode'],$normalized['outcome'],$normalized['targetLocator'],$normalized['reason'],$normalized['actorUserId'],$normalized['occurredAt']);$s->execute();$id=(int)$s->insert_id;
            $this->db->commit();return ['status'=>'accepted','decisionId'=>$id];
        }catch(mysqli_sql_exception $error){
            $this->db->rollback();
            if($error->getCode()===1062){$existing=$this->operation((string)$normalized['operationId'],false);if($existing!==null&&hash_equals((string)$existing['request_sha256'],$requestHash))return ['status'=>'duplicate','decisionId'=>(int)$existing['id']];if($existing!==null)throw new DomainException('Operation id was already used for another decision.');}
            throw $error;
        }catch(Throwable $error){$this->db->rollback();throw $error;}
    }

    public function decisionsForSnapshot(int $snapshotId):array
    {
        if($snapshotId<1)throw new InvalidArgumentException('Invalid reconciliation decision command.');
        $s=$this->db->prepare("SELECT id,operation_id,snapshot_id,snapshot_sha256,projection_sha256,source_locator,issue_code,outcome,target_locator,reason,actor_user_id,occurred_at FROM `{$this->prefix}fm2_migrated_evidence_decisions` WHERE snapshot_id=? ORDER BY id");$s->bind_param('i',$snapshotId);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function allDecisions():array
    {
        return $this->db->query("SELECT d.id,d.operation_id,d.snapshot_id,d.snapshot_sha256,d.projection_sha256,d.source_locator,d.issue_code,d.outcome,d.target_locator,d.reason,d.actor_user_id,d.occurred_at FROM `{$this->prefix}fm2_migrated_evidence_decisions` d JOIN `{$this->prefix}fm2_history_source_snapshots` s ON s.id=d.snapshot_id WHERE NOT EXISTS(SELECT 1 FROM `{$this->prefix}fm2_history_source_snapshots` newer WHERE newer.legacy_object_id=s.legacy_object_id AND newer.id>s.id) ORDER BY d.snapshot_id,d.id")->fetch_all(MYSQLI_ASSOC);
    }

    private function validate(array $c):array
    {
        $operation=$c['operationId']??null;$snapshotId=$c['snapshotId']??null;$actor=$c['actorUserId']??null;$snapshotHash=$c['snapshotSha256']??null;$projectionHash=$c['projectionSha256']??null;$locator=$c['sourceLocator']??null;$code=$c['issueCode']??null;$outcome=$c['outcome']??null;$target=$c['targetLocator']??null;$reason=$c['reason']??null;$at=$c['occurredAt']??null;
        $valid=is_string($operation)&&preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D',$operation)===1&&is_int($snapshotId)&&$snapshotId>0&&is_int($actor)&&$actor>0&&is_string($snapshotHash)&&preg_match('/^[a-f0-9]{64}$/D',$snapshotHash)===1&&is_string($projectionHash)&&preg_match('/^[a-f0-9]{64}$/D',$projectionHash)===1&&$this->bounded($locator,500)&&$this->bounded($code,80)&&is_string($outcome)&&in_array($outcome,self::OUTCOMES,true)&&$this->bounded($reason,1000)&&is_string($at)&&DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$at)?->format('Y-m-d\TH:i:sP')===$at;
        $validTarget=is_string($target)&&preg_match('/^(?:operational_case|workforce_tab|legacy_object):[1-9][0-9]*$/D',$target)===1;
        if(!$valid||($outcome==='map_link'?!$validTarget:$target!==null))throw new InvalidArgumentException('Invalid reconciliation decision command.');
        return ['operationId'=>$operation,'snapshotId'=>$snapshotId,'snapshotSha256'=>$snapshotHash,'projectionSha256'=>$projectionHash,'sourceLocator'=>$locator,'issueCode'=>$code,'outcome'=>$outcome,'targetLocator'=>$target,'reason'=>$reason,'actorUserId'=>$actor,'occurredAt'=>$at];
    }

    private function bounded(mixed $value,int $max):bool{return is_string($value)&&trim($value)!==''&&mb_strlen($value)<= $max&&preg_match('/[\x00-\x1F\x7F]/u',$value)!==1;}
    private function authorized(int $actor):bool{$s=$this->db->prepare("SELECT COUNT(*) n FROM `{$this->prefix}fm2_pilot_users` u JOIN `{$this->prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.user_id=? AND u.status=1 AND r.status=1 AND (r.name='ОТиЗ' OR LOWER(r.name) LIKE '%администратор%')");$s->bind_param('i',$actor);$s->execute();return (int)$s->get_result()->fetch_assoc()['n']>0;}
    private function operation(string $id,bool $lock):?array{$suffix=$lock?' FOR UPDATE':'';$s=$this->db->prepare("SELECT id,request_sha256 FROM `{$this->prefix}fm2_migrated_evidence_decisions` WHERE operation_id=?{$suffix}");$s->bind_param('s',$id);$s->execute();$row=$s->get_result()->fetch_assoc();return is_array($row)?$row:null;}
    private function projection(int $snapshotId):array{return(new MigratedEvidenceProjectionStore($this->db,$this->prefix))->single($snapshotId);}
}
