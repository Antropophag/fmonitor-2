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
        $completed="EXISTS(SELECT 1 FROM ".$this->table($this->prefix.'fm2_pilot_completion_facts')." pto WHERE pto.installation_case_id=c.id AND pto.fact_type='pto_act') AND EXISTS(SELECT 1 FROM ".$this->table($this->prefix.'fm2_pilot_completion_facts')." declaration WHERE declaration.installation_case_id=c.id AND declaration.fact_type='declaration')";
        $total=(int)$this->db->query('SELECT COUNT(*) n FROM '.$this->table($this->prefix.'fm2_installation_cases')." WHERE process_state='working'")->fetch_assoc()['n'];
        $pages=\max(1,(int)\ceil($total/$size));if($page>$pages)throw new PilotHttpInfrastructureUnavailable();
        $activityJoin=$hasOperations?' LEFT JOIN (SELECT installation_case_id,MAX(device_time) last_activity_at FROM '.$this->table($this->prefix.'fm2_checklist_operations').' GROUP BY installation_case_id) a ON a.installation_case_id=c.id':'';
        $activitySelect=$hasOperations?'a.last_activity_at':'NULL';
        $sql='SELECT c.legacy_installation_object_id object_id,m.ordadr_address address,m.entrance,m.regnumber registration_number,('.$completed.') completed,'.$activitySelect.' last_activity_at FROM '.$this->table($this->prefix.'fm2_installation_cases').' c JOIN '.$this->table($this->legacyPrefix.'fm_maintable').' m ON m.id=c.legacy_installation_object_id'.$activityJoin." WHERE c.process_state='working' ORDER BY ".$activitySelect.' IS NOT NULL,'.$activitySelect.',c.legacy_installation_object_id LIMIT '.$size.' OFFSET '.$offset;
        $selected=$this->db->query($sql)->fetch_all(MYSQLI_ASSOC);$objects=[];$seen=[];
        $reader=new \FMonitor2\InstallationProcess\MariaDbControlEngineerAssignmentReader($this->db,$this->prefix);
        foreach($selected as$row){$id=\filter_var($row['object_id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false||isset($seen[$id])||\trim((string)$row['address'])===''||\trim((string)$row['entrance'])===''||\trim((string)$row['registration_number'])===''||!\in_array($row['completed'],[0,1,'0','1'],true))throw new PilotHttpInfrastructureUnavailable();$seen[$id]=true;$assignment=$reader->read($id);if($assignment['status']==='unavailable')throw new PilotHttpInfrastructureUnavailable();$engineer=$assignment['status']==='found'?['userId'=>$assignment['engineer']['userId'],'fullName'=>$assignment['engineer']['fullName']]:null;$objects[]=['id'=>$id,'address'=>(string)$row['address'],'entrance'=>(string)$row['entrance'],'registrationNumber'=>(string)$row['registration_number'],'controlEngineer'=>$engineer,'completed'=>(bool)$row['completed'],'lastChecklistActivityAt'=>$row['last_activity_at'],'_pagination'=>['page'=>$page,'pages'=>$pages,'total'=>$total,'pageSize'=>$size]];}
        return$objects;
    }
    private function tableExists(string$name):bool{$s=$this->db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$name]);try{return$s->get_result()->fetch_row()!==null;}finally{$s->close();}}
    private function table(string$name):string{return'`'.$name.'`';}
}
