<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final readonly class MariaDbConstructionControlQueue
{
    public function __construct(private \mysqli $db,private string $prefix,private string $legacyPrefix){}
    public function read(int $page,int $size=50):array
    {
        if($page<1||$size<1)throw new PilotHttpInfrastructureUnavailable();$offset=($page-1)*$size;
        $hasOperations=$this->tableExists($this->prefix.'fm2_checklist_operations');
        $hasApplications=$this->tableExists($this->prefix.'fm2_assignment_order_applications');
        $completed="EXISTS(SELECT 1 FROM ".$this->table($this->prefix.'fm2_pilot_completion_facts')." pto WHERE pto.installation_case_id=c.id AND pto.fact_type='pto_act') AND EXISTS(SELECT 1 FROM ".$this->table($this->prefix.'fm2_pilot_completion_facts')." declaration WHERE declaration.installation_case_id=c.id AND declaration.fact_type='declaration')";
        $total=(int)$this->db->query('SELECT COUNT(*) n FROM '.$this->table($this->prefix.'fm2_installation_cases')." WHERE process_state='working'")->fetch_assoc()['n'];
        $pages=\max(1,(int)\ceil($total/$size));if($page>$pages)throw new PilotHttpInfrastructureUnavailable();
        $activityJoin=$hasOperations?' LEFT JOIN (SELECT installation_case_id,MAX(device_time) last_activity_at FROM '.$this->table($this->prefix.'fm2_checklist_operations').' GROUP BY installation_case_id) a ON a.installation_case_id=c.id':'';
        $activitySelect=$hasOperations?'a.last_activity_at':'NULL';
        $applicationJoin=$hasApplications?' LEFT JOIN '.$this->table($this->prefix.'fm2_assignment_order_applications').' aa ON aa.application_id=(SELECT latest.application_id FROM '.$this->table($this->prefix.'fm2_assignment_order_applications').' latest WHERE latest.installation_case_id=c.id ORDER BY latest.application_sequence DESC LIMIT 1)':'';
        $applicationId=$hasApplications?'aa.control_engineer_user_id':'NULL';
        $applicationName=$hasApplications?"JSON_UNQUOTE(JSON_EXTRACT(aa.selected_snapshot_json,'$.selectedEngineer.fullName'))":'NULL';
        $sql='SELECT c.legacy_installation_object_id object_id,m.ordadr_address address,m.entrance,m.regnumber registration_number,('.$completed.') completed,'.$activitySelect.' last_activity_at,'
            .'COALESCE('.$applicationId.",CAST(JSON_UNQUOTE(JSON_EXTRACT(e.payload_json,'$.engineer.userId')) AS UNSIGNED),NULLIF(m.responsstroicontrol,0)) engineer_id,"
            .'COALESCE('.$applicationName.",JSON_UNQUOTE(JSON_EXTRACT(e.payload_json,'$.engineer.fullName')),pu.full_name) engineer_name FROM ".$this->table($this->prefix.'fm2_installation_cases').' c JOIN '.$this->table($this->legacyPrefix.'fm_maintable').' m ON m.id=c.legacy_installation_object_id'.$activityJoin.$applicationJoin
            .' LEFT JOIN '.$this->table($this->prefix.'fm2_process_events')." e ON e.id=(SELECT MAX(latest.id) FROM ".$this->table($this->prefix.'fm2_process_events')." latest WHERE latest.installation_case_id=c.id AND latest.event_type='control_engineer_changed')"
            .' LEFT JOIN '.$this->table($this->prefix.'fm2_pilot_users').' pu ON pu.user_id=COALESCE('.$applicationId.",CAST(JSON_UNQUOTE(JSON_EXTRACT(e.payload_json,'$.engineer.userId')) AS UNSIGNED),NULLIF(m.responsstroicontrol,0)) WHERE c.process_state='working' ORDER BY ".$activitySelect.' IS NOT NULL,'.$activitySelect.',c.legacy_installation_object_id LIMIT '.$size.' OFFSET '.$offset;
        $selected=$this->db->query($sql)->fetch_all(MYSQLI_ASSOC);$objects=[];$seen=[];
        foreach($selected as$row){$id=\filter_var($row['object_id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false||isset($seen[$id])||\trim((string)$row['address'])===''||\trim((string)$row['entrance'])===''||\trim((string)$row['registration_number'])===''||!\in_array($row['completed'],[0,1,'0','1'],true))throw new PilotHttpInfrastructureUnavailable();$seen[$id]=true;$engineerId=\filter_var($row['engineer_id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$engineerName=\trim((string)($row['engineer_name']??''));$objects[]=['id'=>$id,'address'=>(string)$row['address'],'entrance'=>(string)$row['entrance'],'registrationNumber'=>(string)$row['registration_number'],'controlEngineer'=>$engineerId!==false&&$engineerName!==''?['userId'=>$engineerId,'fullName'=>$engineerName]:null,'completed'=>(bool)$row['completed'],'lastChecklistActivityAt'=>$row['last_activity_at'],'_pagination'=>['page'=>$page,'pages'=>$pages,'total'=>$total,'pageSize'=>$size]];}
        return$objects;
    }
    private function tableExists(string$name):bool{$s=$this->db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$name]);try{return$s->get_result()->fetch_row()!==null;}finally{$s->close();}}
    private function table(string$name):string{return'`'.$name.'`';}
}
