<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class MariaDbAssignmentOrderApplicationReader implements AssignmentOrderApplicationReader
{
    public function __construct(private MariaDbAssignmentOrderApplicationSql $sql) {}
    public function readCurrent(int $objectId): AssignmentOrderApplicationReadResult
    {
        if($objectId<1)return new AssignmentOrderApplicationReadResult('invalid_argument');
        return $this->read($objectId,0,1,true);
    }
    public function readHistory(int $objectId,int $afterSequence=0,int $limit=50): AssignmentOrderApplicationReadResult
    {
        if($objectId<1||$afterSequence<0||$afterSequence>2147483647||$limit<1||$limit>100)
            return new AssignmentOrderApplicationReadResult('invalid_argument');
        return $this->read($objectId,$afterSequence,$limit,false);
    }
    public function confirmCurrent(int $objectId,int $applicationId): string
    {
        if($objectId<1||$applicationId<1)return'unavailable';
        try{$s=$this->sql;if($s->idle())return'unavailable';
            $case=$s->rows('SELECT id FROM '.$s->table('fm2_installation_cases').' WHERE legacy_installation_object_id=? FOR UPDATE',[$objectId]);
            if(count($case)!==1)return'unavailable';
            $rows=$s->rows('SELECT application_id FROM '.$s->table('fm2_assignment_order_applications').' WHERE installation_case_id=? ORDER BY application_sequence DESC LIMIT 1 FOR UPDATE',[(int)$case[0]['id']]);
            if(count($rows)!==1)return'changed';
            return (int)$rows[0]['application_id']===$applicationId?'matched':'changed';
        }catch(\Throwable){return'unavailable';}
    }
    private function read(int $object,int $after,int $limit,bool $current): AssignmentOrderApplicationReadResult
    {
        $s=$this->sql;if(!$s->idle())return new AssignmentOrderApplicationReadResult('unavailable');
        try{if(!$s->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ')||!$s->db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY|MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT))throw new \RuntimeException();
            $cases=$s->rows('SELECT id FROM '.$s->table('fm2_installation_cases').' WHERE legacy_installation_object_id=?',[$object]);
            if($cases===[]){$s->db->rollback();return new AssignmentOrderApplicationReadResult('not_found');}
            if(count($cases)!==1)throw new \RuntimeException();$case=(int)$cases[0]['id'];$take=$current?1:$limit+1;
            $order=$current?'DESC':'ASC';$cmp=$current?'':' AND application_sequence>?';$args=$current?[$case]:[$case,$after];
            $rows=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_applications').' WHERE installation_case_id=?'.$cmp.' ORDER BY application_sequence '.$order.' LIMIT '.$take,$args);
            if(!$s->db->rollback())throw new \RuntimeException();
            if($current)return $rows===[]?new AssignmentOrderApplicationReadResult('not_found'):new AssignmentOrderApplicationReadResult('found',$this->payload($rows[0]));
            $more=count($rows)>$limit;if($more)array_pop($rows);$items=array_map($this->payload(...),$rows);
            return new AssignmentOrderApplicationReadResult('found',['items'=>$items,'nextSequence'=>$more?(int)end($rows)['application_sequence']:null]);
        }catch(\Throwable){try{if(!$s->idle())$s->db->rollback();}catch(\Throwable){}return new AssignmentOrderApplicationReadResult('unavailable');}
    }
    private function payload(array $row): array
    {
        $selected=json_decode($row['selected_snapshot_json'],true,512,JSON_THROW_ON_ERROR);
        $eligibility=json_decode($row['eligibility_snapshot_json'],true,512,JSON_THROW_ON_ERROR);
        if(array_keys($selected)!==['selectedInstallers','selectedEngineer']||!is_array($eligibility))throw new \RuntimeException();
        return ['application'=>AssignmentOrderApplicationPayload::fromRow($row),'selectedInstallers'=>$selected['selectedInstallers'],
            'selectedEngineer'=>$selected['selectedEngineer'],'eligibility'=>$eligibility];
    }
}
