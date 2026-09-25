<?php declare(strict_types=1);namespace FMonitor2\Jobs;
final readonly class MariaDbInstallerUtilizationScheduler
{
 public function __construct(private \mysqli$db,private string$p){JobValues::text($p,'/^[A-Za-z0-9_]{0,25}$/D');}
 public function tick(string$nowUtc):array
 {
  JobValues::date($nowUtc);$local=(new \DateTimeImmutable($nowUtc))->setTimezone(new \DateTimeZone('Europe/Moscow'));$date=$local->format('Y-m-d');
  if($local->format('H:i')!=='03:17')return['date'=>$date,'created'=>false,'jobId'=>null];
  $check=$this->db->prepare("SELECT COUNT(*) FROM `{$this->p}fm2_jobs` WHERE job_type='workforce.sync' AND status='completed' AND available_at_utc LIKE ?");$check->execute([$date.'%']);if((int)$check->get_result()->fetch_column()<1)return['date'=>$date,'created'=>false,'jobId'=>null,'reason'=>'workforce_sync_incomplete'];
  $due=$local->setTime(3,17)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');$identity=$this->identity($date);$s=new MariaDbJobsSession($this->db,$this->p,static fn():string=>$nowUtc);
  return$s->transaction(function()use($s,$date,$due,$identity):array{$enqueue=(new MariaDbJobEnqueue($s,['installer-utilization.capture'=>[1]]))->command(['jobType'=>'installer-utilization.capture','payloadVersion'=>1,'payload'=>['captureDate'=>$date,'dueAtUtc'=>$due],'availableAtUtc'=>$due,'idempotencyKey'=>$identity,'actor'=>['type'=>'system','id'=>'installer-utilization-observation-daily-v1']]);$job=$enqueue();return['date'=>$date,'created'=>$job['status']==='created','jobId'=>$job['jobId']];});
 }
 private function identity(string$d):string{$h=substr(hash('sha256',"installer-utilization-observation-daily-v1\0$d"),0,32);$h[12]='4';$h[16]=dechex((hexdec($h[16])&3)|8);return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20,12);}
}
