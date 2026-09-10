<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

trait MariaDbYiiChecklistAdmission
{
        private function commandAccess(int$actorId,int$objectId,string$type):array
        {
            $case=$this->case($objectId,false);
            if($case===null)return['exists'=>false];
            $account=$this->one("SELECT status,activation_state FROM {$this->t('fm2_pilot_users')} WHERE user_id=?",[$actorId]);
            if($account===null||!in_array($account['status'],[1,'1'],true)||$account['activation_state']!=='active')return['exists'=>true,'active'=>false];
            $permissions=$this->permissions($actorId);
            $needsAssignment=in_array($type,['completion_retracted','photo_revoked'],true);
            return['exists'=>true,'active'=>true,'opened'=>$case['process_state']==='working','roleAccess'=>$this->roleAccess($actorId),'itemComplete'=>in_array('inspection.item.complete',$permissions,true),'photoRevoke'=>in_array('inspection.photo.revoke',$permissions,true),'assigned'=>$needsAssignment?$this->formallyAssignedEngineer((int)$case['id'],$actorId):false];
        }

        private function formallyAssignedEngineer(int$caseId,int$actorId):bool
        {
            try{$application=$this->one("SELECT control_engineer_user_id FROM {$this->t('fm2_assignment_order_applications')} WHERE installation_case_id=? ORDER BY application_sequence DESC LIMIT 1",[$caseId]);}
            catch(\mysqli_sql_exception|\yii\db\Exception){$application=null;}
            if($application!==null)return(int)$application['control_engineer_user_id']===$actorId;
            $order=$this->one("SELECT control_engineer_user_id FROM {$this->t('fm2_assignment_orders')} WHERE installation_case_id=? AND status='registered' ORDER BY version_no DESC,id DESC LIMIT 1",[$caseId]);
            return$order!==null&&(int)$order['control_engineer_user_id']===$actorId;
        }

}
