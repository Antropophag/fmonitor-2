<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbEquipmentFactsRead
{
    public function __construct(private \mysqli$db,private string$p){MariaDbSchemaInspector::validateTablePrefix($p);}
    public function forObject(int$id):array
    {
        $meta=$this->db->query("SELECT * FROM `{$this->p}fm2_equipment_fact_sync_metadata` WHERE singleton_id=1")->fetch_assoc();$s=$this->db->prepare("SELECT * FROM `{$this->p}fm2_equipment_fact_current` WHERE object_id=?");$s->bind_param('i',$id);$s->execute();$row=$s->get_result()->fetch_assoc();
        $success=$meta['last_successful_observed_at']??null;$failure=$meta['latest_failure_observed_at']??null;$status=$row===null?($success===null&&$failure!==null?'failed_before_success':'never_synced'):($failure!==null&&$failure>$success?'failed_after_success':'fresh');
        return['readinessDate'=>$row['readiness_date']??null,'firstShipmentDate'=>$row['first_shipment_date']??null,'fullShipmentDate'=>$row['full_shipment_date']??null,'source'=>'1c_erp','status'=>$status,'sourceOrderHmac'=>$row['source_order_hmac']??null,'lastSuccessfulRunId'=>$row['last_successful_run_id']??null,'lastSuccessfulSyncAt'=>$row===null?null:$this->utc($row['last_successful_observed_at']),'latestFailureReason'=>$meta['latest_failure_reason']??null,'latestFailureAt'=>$this->utc($failure)];
    }
    public function historyForObjectIds(array$ids):array
    {
        if($ids===[])return[];$ids=array_values(array_unique(array_map('intval',$ids)));$marks=implode(',',array_fill(0,count($ids),'?'));$s=$this->db->prepare("SELECT object_id,fact_type,old_value,new_value,source,source_order_hmac,run_id,observed_at FROM `{$this->p}fm2_equipment_fact_history` WHERE object_id IN($marks) ORDER BY id");$types=str_repeat('i',count($ids));$s->bind_param($types,...$ids);$s->execute();return array_map(fn($r)=>['objectId'=>(int)$r['object_id'],'factType'=>$r['fact_type'],'oldValue'=>$r['old_value'],'newValue'=>$r['new_value'],'source'=>$r['source'],'sourceOrderHmac'=>$r['source_order_hmac'],'runId'=>$r['run_id'],'observedAtUtc'=>$this->utc($r['observed_at'])],$s->get_result()->fetch_all(MYSQLI_ASSOC));
    }
    public function diagnosticsForRun(string$run):array{$s=$this->db->prepare("SELECT source_order_hmac,reason FROM `{$this->p}fm2_equipment_fact_diagnostics` WHERE run_id=? ORDER BY id");$s->bind_param('s',$run);$s->execute();return array_map(static fn($r)=>['sourceOrderHmac'=>$r['source_order_hmac'],'reason'=>$r['reason']],$s->get_result()->fetch_all(MYSQLI_ASSOC));}
    private function utc(?string$v):?string{return$v===null?null:str_replace(' ','T',$v).'Z';}
}
