<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\AssignmentOrderOriginal\{AssignmentOrderOriginalApplicationReferenceReader,AssignmentOrderOriginalApplicationReferenceStatus};

final readonly class MariaDbAssignmentOrderApplication implements AssignmentOrderApplication
{
    private MariaDbAssignmentOrderApplicationOperation $operation;
    public function __construct(private MariaDbAssignmentOrderApplicationSql $sql,private AssignmentOrderOriginalApplicationReferenceReader $originals,private SelectionClock $clock)
    { $this->operation=new MariaDbAssignmentOrderApplicationOperation($sql,$originals); }
    public function applyAssignmentOrderOriginal(ApplyAssignmentOrderOriginalCommand $c):AssignmentOrderApplicationResult
    {
        if(!$this->valid($c))return$this->out('rejected','invalid_command');$s=$this->sql;
        try {
            if(!$s->idle())return$this->out('failed','dependency_unavailable');$at=$this->instant();
            if(!$this->operation->authorized($c->actorId))return$this->out('rejected','authorization_denied');
            $lookup=$this->originals->readCurrent($c->objectId,$c->orderId);
            if($lookup->status===AssignmentOrderOriginalApplicationReferenceStatus::NOT_FOUND)return$this->out('rejected','original_not_found');
            if($lookup->status!==AssignmentOrderOriginalApplicationReferenceStatus::FOUND)return$this->out('failed','dependency_unavailable');
            $reference=$lookup->reference;if($reference->metadata()['revisionId']!==$c->originalRevisionId)return$this->out('conflict','original_changed');
            if(!$s->db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED')||!$s->db->begin_transaction())throw new \RuntimeException();
            $result=$this->operation->perform($c,$reference,$at,'assignment_order.composition.apply');
            if($result->reason==='object_not_found')$s->db->rollback();elseif(!$s->db->commit())throw new \RuntimeException();
            return$result;
        }catch(\Throwable){try{if(!$s->idle())$s->db->rollback();}catch(\Throwable){}return$this->out('failed','persistence_failure');}
    }
    private function instant():string{$x=$this->clock->now();$v=$x->payload?->utcRfc3339Seconds;if($x->status!==SelectionLookupStatus::FOUND||!is_string($v)||!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D',$v))throw new \RuntimeException();return$v;}
    private function valid(ApplyAssignmentOrderOriginalCommand$c):bool{return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$c->requestId)===1&&$c->objectId>0&&$c->orderId>0&&$c->actorId>0&&$c->expectedApplicationSequence>=0&&$c->expectedApplicationSequence<=2147483647&&preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,79}$/D',$c->originalRevisionId)===1;}
    private function out(string $status,?string $reason):AssignmentOrderApplicationResult{return new AssignmentOrderApplicationResult($status,$reason,null);}
}
