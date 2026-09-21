<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbEquipmentFacts
{
    public static function create(\mysqli$db,string$prefix):self{return new self($db,$prefix);}
    private function __construct(private \mysqli$db,private string$p){MariaDbSchemaInspector::validateTablePrefix($p);}
    public function apply(array$c,string$hash,string$key):array
    {
        $lock='fm2-erp-facts-'.substr(hash('sha256',$this->p),0,32);$locked=false;
        try{$q=$this->db->prepare('SELECT GET_LOCK(?,10) ok');$q->bind_param('s',$lock);$q->execute();if((int)$q->get_result()->fetch_assoc()['ok']!==1)throw new EquipmentFactsPersistenceFailure();$locked=true;$this->db->begin_transaction();try{$prior=$this->run($c['runId']);if($prior!==null){$this->db->rollback();return hash_equals($prior['command_hash'],$hash)?json_decode($prior['receipt_json'],true,32,JSON_THROW_ON_ERROR):['status'=>'conflict','reason'=>'RUN_ID_CONFLICT'];}
            $result=$c['kind']==='failed'?$this->failed($c,$hash):$this->complete($c,$hash,$key);$this->db->commit();return$result;
        }catch(\Throwable$e){$this->db->rollback();if($e instanceof EquipmentFactsPersistenceFailure)throw$e;throw new EquipmentFactsPersistenceFailure();}}
        catch(EquipmentFactsPersistenceFailure$e){throw$e;}catch(\Throwable){throw new EquipmentFactsPersistenceFailure();}
        finally{if($locked)try{$q=$this->db->prepare('SELECT RELEASE_LOCK(?)');$q->bind_param('s',$lock);$q->execute();}catch(\Throwable){}}
    }
    private function run(string$id):?array{$s=$this->db->prepare("SELECT command_hash,receipt_json FROM `{$this->p}fm2_equipment_fact_runs` WHERE run_id=? FOR UPDATE");$s->bind_param('s',$id);$s->execute();return$s->get_result()->fetch_assoc()?:null;}
    private function failed(array$c,string$hash):array{$receipt=['status'=>'failed','runId'=>$c['runId'],'reason'=>$c['reason']];$this->storeRun($c,$hash,$receipt);$s=$this->db->prepare("UPDATE `{$this->p}fm2_equipment_fact_sync_metadata` SET latest_failure_reason=?,latest_failure_observed_at=? WHERE singleton_id=1");$at=$this->sqlTime($c['observedAtUtc']);$s->bind_param('ss',$c['reason'],$at);$s->execute();return$receipt;}
    private function complete(array$c,string$hash,string$key):array
    {
        $counts=['matched'=>0,'changed'=>0,'unchanged'=>0,'unmatched'=>0,'ambiguous'=>0];
        foreach($c['records']as$r){$h=hash_hmac('sha256',$r['sourceOrderNumber'],$key);$s=$this->db->prepare("SELECT id FROM `{$this->p}fm_maintable` WHERE BINARY zavnumber=BINARY ? ORDER BY id FOR UPDATE");$s->bind_param('s',$r['sourceOrderNumber']);$s->execute();$ids=array_map('intval',array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'id'));
            if(count($ids)!==1){$reason=count($ids)===0?'OBJECT_NOT_FOUND':'OBJECT_AMBIGUOUS';$counts[count($ids)===0?'unmatched':'ambiguous']++;$s=$this->db->prepare("INSERT INTO `{$this->p}fm2_equipment_fact_diagnostics`(run_id,source_order_hmac,reason,created_at)VALUES(?,?,?,?)");$at=$this->sqlTime($c['observedAtUtc']);$s->bind_param('ssss',$c['runId'],$h,$reason,$at);$s->execute();continue;}
            $counts['matched']++;$id=$ids[0];$s=$this->db->prepare("SELECT readiness_date,first_shipment_date,full_shipment_date FROM `{$this->p}fm2_equipment_fact_current` WHERE object_id=? FOR UPDATE");$s->bind_param('i',$id);$s->execute();$old=$s->get_result()->fetch_assoc()?:['readiness_date'=>null,'first_shipment_date'=>null,'full_shipment_date'=>null];$map=['readiness'=>['readiness_date','readinessDate'],'first_shipment'=>['first_shipment_date','firstShipmentDate'],'full_shipment'=>['full_shipment_date','fullShipmentDate']];$changed=false;$at=$this->sqlTime($c['observedAtUtc']);foreach($map as$type=>[$column,$input])if($old[$column]!==$r[$input]){$changed=true;$s=$this->db->prepare("INSERT INTO `{$this->p}fm2_equipment_fact_history`(object_id,fact_type,old_value,new_value,source,source_order_hmac,run_id,observed_at)VALUES(?,?,?,?,'1c_erp',?,?,?)");$s->bind_param('issssss',$id,$type,$old[$column],$r[$input],$h,$c['runId'],$at);$s->execute();}
            $s=$this->db->prepare("INSERT INTO `{$this->p}fm2_equipment_fact_current`(object_id,readiness_date,first_shipment_date,full_shipment_date,source,source_order_hmac,last_successful_run_id,last_successful_observed_at)VALUES(?,?,?,?,'1c_erp',?,?,?) ON DUPLICATE KEY UPDATE readiness_date=VALUES(readiness_date),first_shipment_date=VALUES(first_shipment_date),full_shipment_date=VALUES(full_shipment_date),source=VALUES(source),source_order_hmac=VALUES(source_order_hmac),last_successful_run_id=VALUES(last_successful_run_id),last_successful_observed_at=VALUES(last_successful_observed_at)");$s->bind_param('issssss',$id,$r['readinessDate'],$r['firstShipmentDate'],$r['fullShipmentDate'],$h,$c['runId'],$at);$s->execute();$counts[$changed?'changed':'unchanged']++;
        }
        $receipt=['status'=>'completed','runId'=>$c['runId']]+$counts;$this->storeRun($c,$hash,$receipt);$at=$this->sqlTime($c['observedAtUtc']);$s=$this->db->prepare("UPDATE `{$this->p}fm2_equipment_fact_sync_metadata` SET last_successful_run_id=?,last_successful_observed_at=?,latest_failure_reason=NULL,latest_failure_observed_at=NULL WHERE singleton_id=1");$s->bind_param('ss',$c['runId'],$at);$s->execute();return$receipt;
    }
    private function storeRun(array$c,string$hash,array$receipt):void{$json=json_encode($receipt,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$at=$this->sqlTime($c['observedAtUtc']);$s=$this->db->prepare("INSERT INTO `{$this->p}fm2_equipment_fact_runs`(run_id,command_hash,kind,status,reason,observed_at,receipt_json,created_at)VALUES(?,?,?,?,?,?,?,?)");$status=$receipt['status'];$reason=$c['reason']??null;$s->bind_param('ssssssss',$c['runId'],$hash,$c['kind'],$status,$reason,$at,$json,$at);$s->execute();}
    private function sqlTime(string$v):string{return substr(str_replace('T',' ',$v),0,-1);}
}
