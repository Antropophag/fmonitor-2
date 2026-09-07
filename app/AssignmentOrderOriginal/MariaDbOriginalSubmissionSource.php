<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Immutable selection and backing-validated original leaf in the caller's snapshot. */
final readonly class MariaDbOriginalSubmissionSource
{
    public function __construct(private AssignmentOrderOriginalSql $sql) {}
    public function read(int $object,int $order):?array
    {
        $s=$this->sql;$cases=$s->rows('SELECT id FROM '.$s->table('fm2_installation_cases').' WHERE legacy_installation_object_id='.$object);
        if($cases===[])return null;if(count($cases)!==1)AssignmentOrderOriginalSql::fail();
        $case=AssignmentOrderOriginalStoredRows::integer($cases[0]['id']);
        $composition=MariaDbRegisteredCompositionQuery::read($s,$case,$order);
        if($composition->status===AssignmentOrderCompositionLookupStatus::NOT_FOUND)return null;
        if($composition->status!==AssignmentOrderCompositionLookupStatus::FOUND||$composition->identity===null)AssignmentOrderOriginalSql::fail();
        $headers=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_selections').' WHERE assignment_order_id='.$order);
        if($headers===[])return null;if(count($headers)!==1)AssignmentOrderOriginalSql::fail();$h=$headers[0];
        $members=$s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_selection_members').' WHERE assignment_order_id='.$order.' ORDER BY installer_tab_id');
        $reader=new AssignmentOrderOriginalStoredReader($s);$line=$reader->assignment($case,$order);$current=null;
        if($line->status()===AssignmentOrderOriginalLookupStatus::FOUND){
            if($line->installationCaseId()!==$case||$line->assignmentOrderId()!==$order||$line->compositionIdentity()!==$composition->identity||$line->compositionSha256()!==$composition->sha256)AssignmentOrderOriginalSql::fail();
            $rows=$s->rows('SELECT request_id FROM '.$s->table('fm2_assignment_order_original_revisions').' WHERE revision_id='.$s->quote($line->currentRevisionId()));
            if(count($rows)!==1)AssignmentOrderOriginalSql::fail();$lookup=$reader->request($rows[0]['request_id']);$r=$lookup->result();
            if($lookup->status()!==AssignmentOrderOriginalLookupStatus::FOUND||$r===null||$r->currentRevisionId()!==$line->currentRevisionId())AssignmentOrderOriginalSql::fail();
            $current=['rootId'=>$r->rootOriginalId(),'revisionId'=>$r->currentRevisionId(),'revisionNumber'=>$r->revisionNumber(),'documentDate'=>$r->documentDate(),
                'sha256'=>$r->sha256(),'byteSize'=>$r->byteSize(),'uploadedAt'=>$r->uploadedAt()];
        }elseif($line->status()!==AssignmentOrderOriginalLookupStatus::NOT_FOUND)AssignmentOrderOriginalSql::fail();
        return ['objectId'=>$object,'caseId'=>$case,'orderId'=>$order,'orderVersion'=>AssignmentOrderOriginalStoredRows::integer($h['order_version']),
            'composition'=>['installers'=>array_map(static fn($m)=>['tabId'=>(int)$m['installer_tab_id'],'fullName'=>$m['fio_snapshot'],'position'=>$m['position_snapshot']],$members),
                'engineer'=>['userId'=>$composition->controlEngineerUserId,'fullName'=>$h['control_engineer_fio_snapshot'],'position'=>$h['control_engineer_position_snapshot']]],'current'=>$current];
    }
}
