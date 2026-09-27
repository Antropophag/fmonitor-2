<?php declare(strict_types=1);
namespace FMonitor2\Otiz;
use yii\db\Connection;
final class MariaDbOtizCalculationRegister
{
 public function __construct(private Connection$db,private string$p){}
 public function read(string$filter,string$year,int$page,int$size=25):array
 {
  $obligations="(SELECT COALESCE(SUM(o.amount_cents),0) FROM `{$this->p}fm2_otiz_recipient_obligations` o WHERE o.calculation_id=c.id)";
  $activePaid="(SELECT COALESCE(SUM(p.amount_cents),0) FROM `{$this->p}fm2_otiz_payment_facts` p WHERE p.calculation_id=c.id AND NOT EXISTS(SELECT 1 FROM `{$this->p}fm2_otiz_payment_reversals` r WHERE r.payment_id=p.id))";
  $where=["c.status<>'deleted'"];$params=[];
  if($filter==='drafts')$where[]="c.status='draft'";
  elseif($filter==='waiting')$where[]="c.status='accepted' AND {$obligations}>0 AND {$activePaid}<{$obligations}";
  elseif($filter==='history')$where[]="c.status='cancelled' OR (c.status='accepted' AND ({$obligations}=0 OR {$activePaid}>={$obligations}))";
  if(preg_match('/^\d{4}$/D',$year)){$where[]='YEAR(c.report_date)=:year';$params[':year']=(int)$year;}
  $predicate='('.implode(') AND (',$where).')';$total=(int)$this->db->createCommand("SELECT COUNT(*) FROM `{$this->p}fm2_otiz_calculation_revisions` c WHERE {$predicate}",$params)->queryScalar();$pages=max(1,(int)ceil($total/$size));if($page<1||$page>$pages)throw new \DomainException('REGISTER_PAGE_NOT_FOUND');$offset=($page-1)*$size;
  $rows=$this->db->createCommand("SELECT c.id,c.revision,c.status,c.report_date,c.actor_user_id,c.projection_json,{$activePaid} paid_cents,{$obligations} obligation_cents,(SELECT e.payload_json FROM `{$this->p}fm2_otiz_v2_events` e WHERE e.calculation_id=c.id AND e.event_type IN('cancelled','replaced','draft_deleted') ORDER BY e.id DESC LIMIT 1) stop_payload FROM `{$this->p}fm2_otiz_calculation_revisions` c WHERE {$predicate} ORDER BY c.id DESC LIMIT {$size} OFFSET {$offset}",$params)->queryAll();
  foreach($rows as&$row){$row['actor_user_id']=(int)$row['actor_user_id'];$row['stop_reason']=null;if(is_string($row['stop_payload'])&&json_validate($row['stop_payload'])){$payload=json_decode($row['stop_payload'],true);$row['stop_reason']=$payload['reason']??null;}}unset($row);
  return compact('rows','page','pages','total')+['pageSize'=>$size];
 }
}
