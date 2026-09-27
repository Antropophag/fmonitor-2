<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;
use yii\db\Connection;
final class MariaDbOtizSettlementV2Access
{
 public function __construct(private Connection$db,private string$p){}
 public function calculationForPayment(int$id):?int{$v=$this->db->createCommand("SELECT calculation_id FROM `{$this->p}fm2_otiz_payment_facts` WHERE id=:id",[':id'=>$id])->queryScalar();return$v===false?null:(int)$v;}
 public function admission(int$object):array
 {
  $tx=$this->db->getTransaction()===null?$this->db->beginTransaction():null;
  try{
   $this->db->createCommand("SELECT object_id FROM `{$this->p}fm2_otiz_settlement_locks` WHERE object_id=:o FOR UPDATE",[':o'=>$object])->queryScalar();
   $v=$this->db->createCommand("SELECT generation,decision,source_revision,incident_id,observed_at,reason_code FROM `{$this->p}fm2_otiz_admission_events` WHERE object_id=:o ORDER BY generation DESC LIMIT 1 FOR UPDATE",[':o'=>$object])->queryOne();
   if($v!==false)$result=['decision'=>(string)$v['decision'],'sourceRevision'=>(string)$v['source_revision'],'incidentId'=>$v['incident_id'],'observedAt'=>(string)$v['observed_at'],'generation'=>(int)$v['generation'],'reasonCode'=>$v['reason_code']];
   else{$v=$this->db->createCommand("SELECT decision,source_revision,incident_id,observed_at,reason_code FROM `{$this->p}fm2_otiz_admission_inputs` WHERE object_id=:o ORDER BY observed_at DESC,source_revision DESC LIMIT 1 FOR UPDATE",[':o'=>$object])->queryOne();$result=$v===false?(new MariaDbNativeOtizAdmission($this->db,$this->p))->read($object):['decision'=>(string)$v['decision'],'sourceRevision'=>(string)$v['source_revision'],'incidentId'=>$v['incident_id'],'observedAt'=>(string)$v['observed_at'],'generation'=>0,'reasonCode'=>$v['reason_code']];}
   if($tx!==null)$tx->commit();return$result;
  }catch(\Throwable$e){if($tx!==null&&$tx->isActive)$tx->rollBack();throw$e;}
 }
}
