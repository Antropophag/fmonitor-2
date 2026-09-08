<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class MariaDbSelectionAttempts implements SelectionTerminalAttemptUnitOfWork,SelectionAttemptAuditWriter
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function execute(SelectionTerminalAttemptPersistence $p): SelectionUnitOfWorkResult
    {
        $owned=false;
        try {
            if(!$p->terminalResult instanceof SelectionResult||$p->terminalResult->reasonCode()!==AssignmentOrderCompositionReason::OBJECT_NOT_FOUND||$p->terminalResult->status()!==AssignmentOrderCompositionStatus::REJECTED||$p->requestId->value!==$p->terminalResult->requestId()->value)throw new \RuntimeException();
            MariaDbSelectionWrites::validateTerminal($p->intent,$p->terminalResult,$p->audit);
            if(!$this->sql->ready())throw new \RuntimeException();
            if(!$this->sql->db->begin_transaction())throw new \RuntimeException();$owned=true;
            $existing=(new MariaDbSelectionRequests($this->sql))->lockedFind($p->requestId);
            if($existing->status===SelectionLookupStatus::FOUND){if(!$this->sql->db->rollback())return SelectionUnitOfWorkResult::outcomeUnknown();$owned=false;return SelectionUnitOfWorkResult::observedTerminal($existing->payload);}
            $w=new MariaDbSelectionWrites($this->sql);$w->request($p->intent,$p->terminalResult,$p->audit);$w->audit($p->audit);
            if(!$this->sql->db->commit())return SelectionUnitOfWorkResult::outcomeUnknown();$owned=false;
            return SelectionUnitOfWorkResult::committed($p->terminalResult);
        }catch(\Throwable $e){
            if($owned){try{if(!$this->sql->db->rollback())return SelectionUnitOfWorkResult::outcomeUnknown();}catch(\Throwable){return SelectionUnitOfWorkResult::outcomeUnknown();}}
            if($e instanceof SelectionNativeRequestRace)return SelectionUnitOfWorkResult::requestRace();
            return SelectionUnitOfWorkResult::rolledBack($e instanceof \OverflowException?SelectionRollbackCause::ALLOCATION_CAPACITY_EXHAUSTED:SelectionRollbackCause::PERSISTENCE_FAILURE);
        }
    }
    public function append(SelectionSafeAttemptAudit $a): SelectionAuditWriteResult
    {
        $owned=false;
        try {
            if(!$this->sql->readyAudit())return SelectionAuditWriteResult::rolledBack();
            if(!$this->sql->db->begin_transaction())return SelectionAuditWriteResult::rolledBack();$owned=true;
            $id=(new MariaDbSelectionWrites($this->sql))->audit($a);
            if(!$this->sql->db->commit())return SelectionAuditWriteResult::outcomeUnknown();$owned=false;return SelectionAuditWriteResult::committed($id);
        }catch(\Throwable $e){
            if($owned){try{if(!$this->sql->db->rollback())return SelectionAuditWriteResult::outcomeUnknown();}catch(\Throwable){return SelectionAuditWriteResult::outcomeUnknown();}}
            return $e instanceof \OverflowException?SelectionAuditWriteResult::capacityExhausted():SelectionAuditWriteResult::rolledBack();
        }
    }
}
