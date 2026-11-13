<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final readonly class MariaDbOriginalStartQuery
{
    public function __construct(private MariaDbAssignmentOrderApplicationSql$sql){}
    public function readCurrent(int$objectId,int$orderId):array
    {
        if($objectId<1||$orderId<1)return['status'=>'not_found','value'=>null];
        try{$rows=$this->rows($objectId,$orderId,true);return$rows===[]?['status'=>'not_found','value'=>null]:['status'=>'found','value'=>$rows[0]];}catch(\Throwable){return['status'=>'unavailable','value'=>null];}
    }
    public function readHistory(int$objectId,int$orderId):array
    {
        if($objectId<1||$orderId<1)return['status'=>'not_found','rows'=>[]];
        try{$rows=$this->rows($objectId,$orderId,false);return['status'=>$rows===[]?'not_found':'found','rows'=>$rows];}catch(\Throwable){return['status'=>'unavailable','rows'=>[]];}
    }
    private function rows(int$object,int$order,bool$current):array
    {
        $s=$this->sql;$where=$current?' AND r.current_revision_id=v.revision_id':'';
        $rows=$s->rows('SELECT v.request_id,v.revision_id,v.revision_number,r.current_revision_id,s.appendix_start_date,c.actual_start_date,a.original_revision_id applied_revision FROM '.$s->table('fm2_assignment_order_original_roots').' r JOIN '.$s->table('fm2_assignment_order_original_revisions').' v ON v.root_original_id=r.root_original_id LEFT JOIN '.$s->table('fm2_assignment_order_original_starts').' s ON s.request_id=v.request_id JOIN '.$s->table('fm2_installation_cases').' c ON c.id=r.installation_case_id LEFT JOIN '.$s->table('fm2_assignment_order_applications').' a ON a.installation_case_id=c.id AND a.application_sequence=(SELECT MAX(x.application_sequence) FROM '.$s->table('fm2_assignment_order_applications').' x WHERE x.installation_case_id=c.id) WHERE c.legacy_installation_object_id=? AND r.assignment_order_id=?'.$where.' ORDER BY v.revision_number '.($current?'DESC':'ASC'),[$object,$order]);
        return array_map(static function(array$r):array{$complete=$r['actual_start_date']!==null;$pending=$r['current_revision_id']===$r['revision_id']&&$r['applied_revision']!==$r['revision_id'];return['requestId'=>$r['request_id'],'revisionId'=>$r['revision_id'],'revisionNumber'=>(int)$r['revision_number'],'appendixStartDate'=>$r['appendix_start_date'],'openingComplete'=>$complete,'pending'=>$pending];},$rows);
    }
}
