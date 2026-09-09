<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;
use yii\db\Connection;
/** Read-only settlement projection; all monetary writes remain in OtizSettlement. */
final class MariaDbOtizSettlementView
{
 public function __construct(private Connection$db,private string$prefix){if(preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new \InvalidArgumentException();}
 public function snapshot(int$id):array|false
 {
  $snapshot=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshots` WHERE id=:id",[':id'=>$id])->queryOne();if($snapshot===false)return false;
  $objects=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id=:id ORDER BY object_id",[':id'=>$id])->queryAll();
  foreach($objects as&$object){$oid=(int)$object['object_id'];$object['global_closed_cents']=(int)$this->db->createCommand("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id=:o",[':o'=>$oid])->queryScalar();$object['issues']=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_issues` WHERE snapshot_id=:s AND object_id=:o ORDER BY severity",[':s'=>$id,':o'=>$oid])->queryAll();$object['allocations']=$this->db->createCommand("SELECT * FROM `{$this->prefix}fm2_pilot_otiz_snapshot_allocations` WHERE snapshot_id=:s AND object_id=:o ORDER BY amount_cents DESC,full_name",[':s'=>$id,':o'=>$oid])->queryAll();}unset($object);
  $closures=$this->db->createCommand("SELECT c.*,o.regnumber FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` c JOIN `{$this->prefix}fm2_pilot_otiz_snapshot_objects` o ON o.snapshot_id=c.snapshot_id AND o.object_id=c.object_id WHERE c.snapshot_id=:id ORDER BY c.id DESC",[':id'=>$id])->queryAll();
  return['snapshot'=>$snapshot,'objects'=>$objects,'closures'=>$closures];
 }
 public function snapshotForClosure(int$id):int|false{$v=$this->db->createCommand("SELECT snapshot_id FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE id=:id",[':id'=>$id])->queryScalar();return$v===false?false:(int)$v;}
}
