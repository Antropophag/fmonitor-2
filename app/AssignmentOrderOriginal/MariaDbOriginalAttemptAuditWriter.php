<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalAttemptAuditContracts.php';
require_once __DIR__.'/AssignmentOrderOriginalDataScalar.php';
require_once __DIR__.'/MariaDbOriginalSql.php';
require_once __DIR__.'/MariaDbOriginalSqlFacts.php';

final readonly class AssignmentOrderOriginalMariaDbAttemptAuditWriter implements AssignmentOrderOriginalAttemptAuditWriter
{
    public function __construct(private \mysqli $connection,private string $tablePrefix='',private ?AssignmentOrderOriginalPersistenceObserver $observer=null){}
    public function recordDenied(AssignmentOrderOriginalSafeAttemptAudit $audit):AssignmentOrderOriginalAuditWriteStatus
    {return $this->write($audit,true);}
    public function appendFailure(AssignmentOrderOriginalSafeAttemptAudit $audit):AssignmentOrderOriginalAuditWriteStatus
    {return $this->write($audit,false);}
    private function valid(AssignmentOrderOriginalSafeAttemptAudit $a,bool $denied):bool
    {
        return strlen($this->tablePrefix)<=25&&preg_match('/^[A-Za-z0-9_]*$/D',$this->tablePrefix)===1
            &&AssignmentOrderOriginalDataScalar::uuid($a->requestId)&&AssignmentOrderOriginalDataScalar::utc($a->attemptedAtUtc)
            &&$a->actorUserId>0&&$a->installationCaseId>0&&$a->assignmentOrderId>0
            &&($denied?($a->status===AssignmentOrderOriginalStatus::REJECTED&&$a->reason===AssignmentOrderOriginalReason::AUTHORIZATION_DENIED)
                :($a->status===AssignmentOrderOriginalStatus::FAILED&&in_array($a->reason,[AssignmentOrderOriginalReason::STREAM_FAILURE,AssignmentOrderOriginalReason::STORAGE_FAILURE],true)));
    }
    private function write(AssignmentOrderOriginalSafeAttemptAudit $a,bool $denied):AssignmentOrderOriginalAuditWriteStatus
    {
        if(!$this->valid($a,$denied))return AssignmentOrderOriginalAuditWriteStatus::ROLLED_BACK;
        $owned=false;$commitAttempted=false;$committed=false;
        try {
            $sql=new AssignmentOrderOriginalSql($this->connection,$this->tablePrefix);
            if(!$sql->idle())return AssignmentOrderOriginalAuditWriteStatus::ROLLED_BACK;
            $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_BEGIN);
            $sql->execute('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            if(!$this->connection->begin_transaction(MYSQLI_TRANS_START_READ_WRITE))AssignmentOrderOriginalSql::fail();$owned=true;
            if($denied)$this->terminal($sql,$a);
            $values=[$a->requestId,(string)$a->actorUserId,$a->mode->value,(string)$a->installationCaseId,(string)$a->assignmentOrderId,
                $a->status->value,$a->reason->value,str_replace(['T','Z'],[' ','.000000'],$a->attemptedAtUtc)];
            $sql->execute('INSERT INTO '.$sql->table('fm2_assignment_order_original_audits')
                .' (request_id,actor_identity,mode,installation_case_id,assignment_order_id,status,reason_code,attempted_at_utc) VALUES('
                .implode(',',array_map($sql->quote(...),$values)).')');
            $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_NATIVE_COMMIT);$commitAttempted=true;
            if(!$this->connection->commit())AssignmentOrderOriginalSql::fail();$committed=true;
            $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::AFTER_NATIVE_COMMIT);
            return AssignmentOrderOriginalAuditWriteStatus::COMMITTED;
        } catch(\Throwable) {
            if($committed)return AssignmentOrderOriginalAuditWriteStatus::OUTCOME_UNKNOWN;
            $rolledBack=!$owned||$this->rollback();
            return $commitAttempted||!$rolledBack?AssignmentOrderOriginalAuditWriteStatus::OUTCOME_UNKNOWN:AssignmentOrderOriginalAuditWriteStatus::ROLLED_BACK;
        }
    }
    private function terminal(AssignmentOrderOriginalSql $sql,AssignmentOrderOriginalSafeAttemptAudit $a):void
    {
        try{(new AssignmentOrderOriginalSqlFacts($sql))->attempt(new AssignmentOrderOriginalAttemptCommit($a->requestId,$a->actorUserId,$a->mode,
            $a->installationCaseId,$a->assignmentOrderId,$a->status,$a->reason,false,$a->attemptedAtUtc),false);}
        catch(\mysqli_sql_exception $error){
            if($error->getCode()!==1062)throw $error;
            // Prove the request-key collision without reading confidential terminal payload.
            $rows=$sql->rows('SELECT request_id FROM '.$sql->table('fm2_assignment_order_original_requests').' WHERE request_id='.$sql->quote($a->requestId).' FOR UPDATE');
            if(count($rows)!==1||$rows[0]['request_id']!==$a->requestId)throw $error;
        }
    }
    private function rollback():bool
    {
        $ok=true;try{$this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_ROLLBACK);}catch(\Throwable){$ok=false;}
        try{if(!$this->connection->rollback())$ok=false;}catch(\Throwable){$ok=false;}
        return $ok;
    }
}
