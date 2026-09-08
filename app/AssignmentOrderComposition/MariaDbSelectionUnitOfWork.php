<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class MariaDbSelectionUnitOfWork implements SelectionUnitOfWork
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function executeForCase(int $caseId,SelectionTransactionalWork $work): SelectionUnitOfWorkResult
    {
        $owned=false;
        try {
            if(!$this->sql->ready())return SelectionUnitOfWorkResult::rolledBack(SelectionRollbackCause::DEPENDENCY_UNAVAILABLE);
            if(!$this->sql->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED')||!$this->sql->db->begin_transaction())throw new \RuntimeException();$owned=true;
            $rows=$this->sql->rows('SELECT legacy_installation_object_id FROM '.$this->sql->table('fm2_installation_cases').' WHERE id=? FOR UPDATE',[$caseId]);
            if(count($rows)!==1){$decision=SelectionTransactionDecision::rollback(SelectionRollbackCause::DEPENDENCY_UNAVAILABLE);$session=null;}
            else{$session=new MariaDbSelectionSession($this->sql,$caseId,MariaDbSelectionSql::number($rows[0]['legacy_installation_object_id']));$decision=$work->run($session);}
            $kind=$decision->kind();
            if($kind==='commit') {
                $serializer=new SelectionResultSerializer();
                if($session===null||$session->stages!==1||$session->stageStatus!==SelectionStageStatus::STAGED||$session->staged===null||$serializer->serialize($session->staged)!==$serializer->serialize($decision->result()))throw new \RuntimeException();
                if(!$this->sql->db->commit())return SelectionUnitOfWorkResult::outcomeUnknown();$owned=false;
                return SelectionUnitOfWorkResult::committed($decision->result());
            }
            if($kind==='observedTerminal'&&($session===null||!$session->canObserve()))throw new \RuntimeException();
            if($kind==='requestRace'&&($session===null||$session->stageStatus!==SelectionStageStatus::REQUEST_RACE))throw new \RuntimeException();
            if(!$this->sql->db->rollback())return SelectionUnitOfWorkResult::outcomeUnknown();$owned=false;
            return match($kind){'observedTerminal'=>SelectionUnitOfWorkResult::observedTerminal($decision->terminalRecord()),'requestRace'=>SelectionUnitOfWorkResult::requestRace(),default=>SelectionUnitOfWorkResult::rolledBack($decision->rollbackCause())};
        }catch(\Throwable){
            if(!$owned)return SelectionUnitOfWorkResult::rolledBack(SelectionRollbackCause::PERSISTENCE_FAILURE);
            try{if($this->sql->db->rollback())return SelectionUnitOfWorkResult::rolledBack(SelectionRollbackCause::PERSISTENCE_FAILURE);}catch(\Throwable){}
            return SelectionUnitOfWorkResult::outcomeUnknown();
        }
    }
}
