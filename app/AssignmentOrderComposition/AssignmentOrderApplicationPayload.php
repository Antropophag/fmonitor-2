<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final class AssignmentOrderApplicationPayload
{
    public static function fromRow(array $r): array
    {
        $ids=array_column(json_decode($r['selected_snapshot_json'],true,512,JSON_THROW_ON_ERROR)['selectedInstallers'],'tabId');
        return ['applicationId'=>(int)$r['application_id'],'caseId'=>(int)$r['installation_case_id'],'objectId'=>(int)$r['object_id'],
            'sequence'=>(int)$r['application_sequence'],'orderId'=>(int)$r['assignment_order_id'],'orderVersion'=>(int)$r['order_version'],
            'originalRevisionId'=>$r['original_revision_id'],'originalRevisionNumber'=>(int)$r['original_revision_number'],'documentDate'=>$r['document_date'],
            'compositionIdentity'=>$r['composition_identity'],'compositionSha256'=>$r['composition_sha256'],'engineerUserId'=>(int)$r['control_engineer_user_id'],
            'installerTabIds'=>array_map('intval',$ids),'previousApplicationId'=>$r['previous_application_id']===null?null:(int)$r['previous_application_id'],
            'kind'=>$r['kind'],'appliedAt'=>MariaDbAssignmentOrderApplicationSql::instant($r['applied_at_utc']),'appliedBy'=>(int)$r['applied_by_user_id']];
    }
}
