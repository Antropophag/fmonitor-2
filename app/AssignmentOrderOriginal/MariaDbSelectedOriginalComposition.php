<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Selection write-target policy; historical registered reader stays independent. */
final class MariaDbSelectedOriginalComposition
{
    public static function read(AssignmentOrderOriginalSql $sql,int $case,int $order,
        ?AssignmentOrderOriginalPersistenceObserver $observer=null,bool $lock=false): AssignmentOrderCompositionSnapshot
    {
        $composition=MariaDbRegisteredCompositionQuery::read($sql,$case,$order,null,$lock);
        $observer?->observe(AssignmentOrderOriginalPersistenceEvent::AFTER_COMPOSITION_ORDER_READ);
        if($composition->status!==AssignmentOrderCompositionLookupStatus::FOUND)return $composition;
        $registry=$sql->rows('SELECT source_kind FROM '.$sql->table('fm2_assignment_order_identities').' WHERE assignment_order_id='.$order);
        if(count($registry)!==1||$registry[0]['source_kind']!=='selection')AssignmentOrderOriginalSql::fail();
        $suffix=$lock?' FOR UPDATE':'';
        $latest=$sql->rows('SELECT assignment_order_id,selection_revision FROM '.$sql->table('fm2_assignment_order_selections').' WHERE installation_case_id='.$case.' ORDER BY selection_revision DESC LIMIT 1'.$suffix);
        if(count($latest)!==1||AssignmentOrderOriginalDataScalar::integer($latest[0]['assignment_order_id'])===null
            ||AssignmentOrderOriginalDataScalar::integer($latest[0]['selection_revision'],1,4294967295)===null)AssignmentOrderOriginalSql::fail();
        if((string)$latest[0]['assignment_order_id']===(string)$order)return $composition;
        $roots=$sql->rows('SELECT root_original_id FROM '.$sql->table('fm2_assignment_order_original_roots').' WHERE assignment_order_id='.$order.$suffix);
        if($roots===[])return AssignmentOrderOriginalSqlComposition::empty(AssignmentOrderCompositionLookupStatus::NOT_CURRENT,$case,$order);
        if(count($roots)!==1)AssignmentOrderOriginalSql::fail();
        $line=(new AssignmentOrderOriginalStoredReader($sql))->root($roots[0]['root_original_id']);
        if($line->status()!==AssignmentOrderOriginalLookupStatus::FOUND||$line->installationCaseId()!==$case||$line->assignmentOrderId()!==$order
            ||$line->compositionIdentity()!==$composition->identity||$line->compositionSha256()!==$composition->sha256)AssignmentOrderOriginalSql::fail();
        return $composition;
    }
}
