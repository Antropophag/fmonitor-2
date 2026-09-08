<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final readonly class MariaDbChecklistProjectionStateReader
{
    public function __construct(private \mysqli $db,private string $prefix){}

    public function revision(int $caseId):int
    {
        $statement=$this->db->prepare("SELECT revision_no FROM `{$this->prefix}fm2_checklist_revisions` WHERE installation_case_id=?");
        $statement->bind_param('i',$caseId);$statement->execute();$row=$statement->get_result()->fetch_assoc();
        return$row===null?0:(int)$row['revision_no'];
    }

    public function operationInstallers(int $caseId):array
    {
        $statement=$this->db->prepare("SELECT oi.client_operation_id,oi.installer_tab_id,oi.fio_snapshot,oi.position_snapshot,oi.employment_status_snapshot,oi.dismissal_effective_at_snapshot,oi.assignment_source FROM `{$this->prefix}fm2_checklist_operation_installers` oi JOIN `{$this->prefix}fm2_checklist_operations` o ON o.client_operation_id=oi.client_operation_id WHERE o.installation_case_id=? ORDER BY oi.installer_tab_id");
        $statement->bind_param('i',$caseId);$statement->execute();$result=[];
        foreach($statement->get_result()->fetch_all(MYSQLI_ASSOC)as$row)$result[$row['client_operation_id']][]=['tabId'=>(string)$row['installer_tab_id'],'fio'=>$row['fio_snapshot'],'position'=>$row['position_snapshot'],'employmentStatus'=>$row['employment_status_snapshot'],'dismissalEffectiveAt'=>$row['dismissal_effective_at_snapshot'],'assignmentSource'=>$row['assignment_source']];
        return$result;
    }
}
