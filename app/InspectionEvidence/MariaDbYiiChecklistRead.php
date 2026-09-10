<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

trait MariaDbYiiChecklistRead
{
        public function access(int $actorId,int $objectId):array
        {
            $case=$this->case($objectId,false);
    if($case===null)return['exists'=>false];
            $profile=$this->one("SELECT u.status,u.activation_state,m.ordadr_address address,m.entrance,m.regnumber registration_number FROM {$this->t('fm2_pilot_users')} u JOIN {$this->t('fm2_installation_cases')} c ON c.legacy_installation_object_id=? JOIN {$this->tLegacy('fm_maintable')} m ON m.id=c.legacy_installation_object_id WHERE u.user_id=?",[$objectId,$actorId]);
            if($profile===null||!in_array($profile['status'],[1,'1'],true)||$profile['activation_state']!=='active')return['exists'=>true,'read'=>false,'active'=>false];
    $permissions=$this->permissions($actorId);
    $role=$this->roleAccess($actorId);
    $read=$role||in_array('checklist.read',$permissions,true)||in_array('inspection.item.complete',$permissions,true);
    $engineer=$this->engineer((int)$case['id']);
            return['exists'=>true,'read'=>$read,'active'=>true,'opened'=>$case['process_state']==='working','roleAccess'=>$role,'itemComplete'=>in_array('inspection.item.complete',$permissions,true),'assigned'=>(int)($engineer['userId']??0)===$actorId,'photoRevoke'=>in_array('inspection.photo.revoke',$permissions,true),'address'=>$profile['address'],'entrance'=>$profile['entrance'],'registrationNumber'=>$profile['registration_number'],'engineer'=>$engineer];
        }

        public function projection(int $objectId):array
        {
            $case=$this->case($objectId,false);
    if($case===null)throw new \OutOfBoundsException();
    $id=(int)$case['id'];
    $revision=(int)($this->one("SELECT revision_no FROM {$this->t('fm2_checklist_revisions')} WHERE installation_case_id=?",[$id])['revision_no']??0);
            $crew=$this->crew($id,$objectId);
    $current=[];
    foreach($crew as$x)$current[(string)$x['tabId']]=$x;
    $assigned=[];
    foreach($this->all("SELECT oi.* FROM {$this->t('fm2_checklist_operation_installers')} oi JOIN {$this->t('fm2_checklist_operations')} o ON o.client_operation_id=oi.client_operation_id WHERE o.installation_case_id=? ORDER BY oi.installer_tab_id",[$id])as$r){
        $currentInstaller=$current[(string)$r['installer_tab_id']]??null;
        $assigned[$r['client_operation_id']][]=[
            'tabId'=>(string)$r['installer_tab_id'],'fio'=>$r['fio_snapshot'],'position'=>$r['position_snapshot'],
            'employmentStatus'=>$currentInstaller!==null?$currentInstaller['employmentStatus']:$r['employment_status_snapshot'],
            'employmentStatusSnapshot'=>$r['employment_status_snapshot'],
            'dismissalEffectiveAt'=>$currentInstaller!==null?$currentInstaller['dismissalEffectiveAt']:$r['dismissal_effective_at_snapshot'],
            'sourceUpdatedAt'=>$r['workforce_source_updated_at_snapshot'],
            'currentlyAssigned'=>$currentInstaller!==null,'assignmentSource'=>$r['assignment_source'],
        ];
    }
            $items=[];
    $sections=[];
    foreach($this->all("SELECT * FROM {$this->t('fm2_checklist_operations')} WHERE installation_case_id=? ORDER BY id",[$id])as$r)
        {$template=['snapshotId'=>$r['template_snapshot_id']===null?null:(int)$r['template_snapshot_id'],'version'=>$r['template_snapshot_version'],'contentSha256'=>$r['template_content_sha256']];
    if(in_array($r['operation_type'],['item_completed','item_installers_changed'],true))$items[(string)$r['item_id']]=['clientOperationId'=>$r['client_operation_id'],'actorUserId'=>(int)$r['actor_user_id'],'deviceTime'=>$r['device_time'],'serverReceivedAt'=>$r['server_received_at'],'revision'=>(int)$r['accepted_revision'],'template'=>$template,'installers'=>$assigned[$r['client_operation_id']]??[]];
    if($r['operation_type']==='completion_retracted')
        {unset($items[(string)$r['item_id']],$sections[(string)$r['section_id']]);
    }if($r['operation_type']==='section_completed')$sections[(string)$r['section_id']]=['clientOperationId'=>$r['client_operation_id'],'serverReceivedAt'=>$r['server_received_at'],'revision'=>(int)$r['accepted_revision'],'template'=>$template];
    }
            $photos=[];
    foreach($this->all("SELECT id,section_id,upload_operation_id,sha256,mime_type,byte_size,original_name,actor_user_id,device_time,server_received_at FROM {$this->t('fm2_checklist_photos')} WHERE installation_case_id=? AND revoked_at IS NULL ORDER BY id",[$id])as$r)$photos[]=['id'=>(int)$r['id'],'sectionId'=>(int)$r['section_id'],'clientOperationId'=>$r['upload_operation_id'],'sha256'=>$r['sha256'],'mime'=>$r['mime_type'],'size'=>(int)$r['byte_size'],'originalName'=>$r['original_name'],'actorUserId'=>(int)$r['actor_user_id'],'deviceTime'=>$r['device_time'],'serverReceivedAt'=>$r['server_received_at']];
            return['revision'=>$revision,'crew'=>$crew,'items'=>$items,'photos'=>$photos,'completedSections'=>$sections];
        }

        public function queue(int $actorId,int $page=1,int $size=50):array
        {
            if(!in_array('construction_control.read',$this->permissions($actorId),true))throw new \DomainException();
    $offset=($page-1)*$size;
    $total=(int)$this->one("SELECT COUNT(*) n FROM {$this->t('fm2_installation_cases')} WHERE process_state='working'")['n'];
    $pages=max(1,(int)ceil($total/$size));
    if($page>$pages)throw new \OutOfBoundsException();
    $sql="SELECT c.id case_id,c.legacy_installation_object_id object_id,m.ordadr_address address,m.entrance,m.regnumber registration_number,(EXISTS(SELECT 1 FROM {$this->t('fm2_pilot_completion_facts')} f WHERE f.installation_case_id=c.id AND f.fact_type='pto_act') AND EXISTS(SELECT 1 FROM {$this->t('fm2_pilot_completion_facts')} f WHERE f.installation_case_id=c.id AND f.fact_type='declaration')) completed,(SELECT MAX(device_time) FROM {$this->t('fm2_checklist_operations')} o WHERE o.installation_case_id=c.id) last_activity_at FROM {$this->t('fm2_installation_cases')} c JOIN {$this->tLegacy('fm_maintable')} m ON m.id=c.legacy_installation_object_id WHERE c.process_state='working' ORDER BY last_activity_at IS NOT NULL,last_activity_at,c.legacy_installation_object_id LIMIT $size OFFSET $offset";
    $rows=$this->all($sql);
    foreach($rows as&$r)
        {$r['id']=(int)$r['object_id'];
    $r['registrationNumber']=$r['registration_number'];
    $r['completed']=(bool)$r['completed'];
    $r['lastChecklistActivityAt']=$r['last_activity_at'];
    $r['controlEngineer']=$this->engineer((int)$r['case_id']);
    $r['_pagination']=['page'=>$page,'pages'=>$pages,'total'=>$total,'pageSize'=>$size];
    }return$rows;
        }

        public function completion(int $objectId):array{$case=$this->case($objectId,false);
    if(!$case)return['cap'=>85,'complete'=>false];
    $facts=$this->all("SELECT fact_type FROM {$this->t('fm2_pilot_completion_facts')} WHERE installation_case_id=?",[(int)$case['id']]);
    $types=array_column($facts,'fact_type');
    $complete=in_array('pto_act',$types,true)&&in_array('declaration',$types,true);
    return['cap'=>$complete?100:85,'complete'=>$complete];
    }

        private function application(int$case):?array{try{$row=$this->one("SELECT application_id,application_sequence,control_engineer_user_id,selected_snapshot_json,eligibility_snapshot_json FROM {$this->t('fm2_assignment_order_applications')} WHERE installation_case_id=? ORDER BY application_sequence DESC LIMIT 1",[$case]);
    if(!$row)return null;
    $selected=json_decode($row['selected_snapshot_json'],true,32,JSON_THROW_ON_ERROR);
    $eligibility=json_decode($row['eligibility_snapshot_json'],true,32,JSON_THROW_ON_ERROR);
    $ids=array_column($selected['selectedInstallers'],'tabId');
    return['application'=>['caseId'=>$case,'applicationId'=>(int)$row['application_id'],'sequence'=>(int)$row['application_sequence'],'engineerUserId'=>(int)$row['control_engineer_user_id'],'installerTabIds'=>array_map('intval',$ids)],'selectedInstallers'=>$selected['selectedInstallers'],'selectedEngineer'=>$selected['selectedEngineer'],'eligibility'=>$eligibility];
    } catch(\mysqli_sql_exception)
        {return null;
    }}

        private function case(int$id,bool$lock):?array{return$this->one("SELECT id,process_state FROM {$this->t('fm2_installation_cases')} WHERE legacy_installation_object_id=? LIMIT 2".($lock?' FOR UPDATE':''),[$id]);
    }

        private function permissions(int$id):array{return array_column($this->all("SELECT DISTINCT rp.permission FROM {$this->t('fm2_pilot_users')} u JOIN {$this->t('fm2_pilot_user_roles')} ur ON ur.user_id=u.user_id JOIN {$this->t('fm2_pilot_roles')} r ON r.role_id=ur.role_id JOIN {$this->t('fm2_pilot_role_permissions')} rp ON rp.role_id=r.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1",[$id]),'permission');
    }

        private function roleAccess(int$id):bool{return$this->one("SELECT 1 FROM {$this->t('fm2_pilot_users')} u JOIN {$this->t('fm2_pilot_user_roles')} ur ON ur.user_id=u.user_id JOIN {$this->t('fm2_pilot_roles')} r ON r.role_id=ur.role_id WHERE u.user_id=? AND u.status=1 AND u.activation_state='active' AND r.status=1 AND r.code IN('construction_control_engineer','manager') LIMIT 1",[$id])!==null;
    }

        private function engineer(int$case):?array{$r=$this->one("SELECT COALESCE(aa.control_engineer_user_id,CAST(JSON_UNQUOTE(JSON_EXTRACT(e.payload_json,'$.engineer.userId')) AS UNSIGNED),NULLIF(m.responsstroicontrol,0)) id,COALESCE(JSON_UNQUOTE(JSON_EXTRACT(aa.selected_snapshot_json,'$.selectedEngineer.fullName')),JSON_UNQUOTE(JSON_EXTRACT(e.payload_json,'$.engineer.fullName')),pu.full_name) name FROM {$this->t('fm2_installation_cases')} c JOIN {$this->tLegacy('fm_maintable')} m ON m.id=c.legacy_installation_object_id LEFT JOIN {$this->t('fm2_assignment_order_applications')} aa ON aa.application_id=(SELECT latest.application_id FROM {$this->t('fm2_assignment_order_applications')} latest WHERE latest.installation_case_id=c.id ORDER BY latest.application_sequence DESC LIMIT 1) LEFT JOIN {$this->t('fm2_process_events')} e ON e.id=(SELECT MAX(latest.id) FROM {$this->t('fm2_process_events')} latest WHERE latest.installation_case_id=c.id AND latest.event_type='control_engineer_changed') LEFT JOIN {$this->t('fm2_pilot_users')} pu ON pu.user_id=COALESCE(aa.control_engineer_user_id,CAST(JSON_UNQUOTE(JSON_EXTRACT(e.payload_json,'$.engineer.userId')) AS UNSIGNED),NULLIF(m.responsstroicontrol,0)) WHERE c.id=?",[$case]);
    if(!$r||!(int)$r['id'])return null;
    return['userId'=>(int)$r['id'],'fullName'=>(string)$r['name']];
    }

        private function crew(int$case,?int$object=null):array{if($application=$this->application($case))
        {$proof=[];
    foreach($application["eligibility"]as$x)$proof[(string)$x["tabId"]]=$x;
    $rows=[];
    foreach($application["selectedInstallers"]as$x)
        {$p=$proof[(string)$x["tabId"]]??[];
    $rows[]=["tabId"=>(string)$x["tabId"],"fio"=>$x["fullName"],"position"=>$x["position"],"employmentStatus"=>$p["employmentStatus"]??"unknown","dismissalEffectiveAt"=>$p["employedTo"]??null,"sourceUpdatedAt"=>$p["sourceUpdatedAt"]??"","currentlyAssigned"=>true];
    }return$rows;
    }$rows=$this->all("SELECT oi.installer_tab_id tabId,COALESCE(w.fio,oi.fio_snapshot) fio,COALESCE(w.position,oi.position_snapshot) position,COALESCE(w.employment_status,oi.employment_status_snapshot) employmentStatus,w.dismissal_effective_at dismissalEffectiveAt,COALESCE(w.workforce_source_updated_at,oi.workforce_source_updated_at_snapshot) sourceUpdatedAt FROM {$this->t('fm2_assignment_orders')} o JOIN {$this->t('fm2_order_installers')} oi ON oi.assignment_order_id=o.id LEFT JOIN {$this->t('fm2_workforce_catalog')} w ON w.installer_tab_id=oi.installer_tab_id WHERE o.installation_case_id=? AND o.status='registered' AND o.version_no=(SELECT MAX(x.version_no) FROM {$this->t('fm2_assignment_orders')} x WHERE x.installation_case_id=o.installation_case_id AND x.status='registered') ORDER BY oi.installer_tab_id",[$case]);
    foreach($rows as&$r)
        {$r['tabId']=(string)$r['tabId'];
    $r['currentlyAssigned']=true;
    }return$rows;
    }

        private function selected(int$case,mixed$ids):?array{if(!is_array($ids)||$ids===[]||count($ids)!==count(array_unique(array_map('strval',$ids))))return null;
    $crew=[];
    foreach($this->crew($case)as$x)$crew[(string)$x['tabId']]=$x;
    $out=[];
    foreach($ids as$id)
        {if(!isset($crew[(string)$id]))return null;
    $out[]=$crew[(string)$id];
    }return$out;
    }

}
