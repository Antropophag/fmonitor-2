<?php
declare(strict_types=1);namespace FMonitor2\DeadlineTransferCertificate;
final readonly class MariaDbDeadlineCertificatePageQuery
{
 public function __construct(private \yii\db\Connection$db,private \mysqli$mysqli,private string$p,private string$l){}
 public function object(int$id):?array{$r=$this->db->createCommand("SELECT c.id case_id,l.id object_id,COALESCE(l.workdateendadjusted,l.plan_finish_date) deadline FROM `{$this->p}fm2_installation_cases`c JOIN `{$this->l}fm_maintable`l ON l.id=c.legacy_installation_object_id WHERE l.id=:id",[':id'=>$id])->queryOne();return$r?['caseId'=>(int)$r['case_id'],'objectId'=>(int)$r['object_id'],'deadline'=>(string)$r['deadline']]:null;}
 public function actorNames(array$ids):array{$out=[];foreach(array_unique($ids)as$id){$q=$this->mysqli->prepare("SELECT full_name FROM `{$this->p}fm2_pilot_users` WHERE user_id=?");$q->execute([$id]);$out[$id]=(string)$q->get_result()->fetch_column();}return$out;}
}
