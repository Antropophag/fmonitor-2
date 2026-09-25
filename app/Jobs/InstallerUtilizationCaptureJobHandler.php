<?php declare(strict_types=1);namespace FMonitor2\Jobs;
final class InstallerUtilizationCaptureJobHandler
{
 public static function handle(array$job,?JobsRuntimeConfiguration$config,?callable$probe=null):array
 {
  if(($job['payloadVersion']??null)!==1||($job['actor']['type']??null)!=='system'||($job['actor']['id']??null)!=='installer-utilization-observation-daily-v1'||count($job['actor']??[])!==2)throw new \InvalidArgumentException();
  try{if($probe!==null)$result=$probe();else{$result=self::capture($job,$config??throw new \InvalidArgumentException());}return['status'=>'completed','result'=>$result];}catch(\InvalidArgumentException$e){throw$e;}catch(\Throwable){return['status'=>'retryable','failureCode'=>'INSTALLER_UTILIZATION_CAPTURE_FAILED'];}
 }
 private static function capture(array$job,JobsRuntimeConfiguration$config):array
 {
  $db=MariaDbJobsConnection::open($config);$yii=new \yii\db\Connection(['dsn'=>'mysql:host='.$config->value('FMONITOR_DB_HOST').';port='.$config->port().';dbname='.$config->value('FMONITOR_DB_NAME'),'username'=>$config->value('FMONITOR_DB_USER'),'password'=>$config->value('FMONITOR_DB_PASSWORD'),'charset'=>'utf8mb4']);$yii->open();
  try{$date=(string)$job['payload']['captureDate'];$q=$db->prepare("SELECT COUNT(*) FROM `{$config->prefix()}fm2_jobs` WHERE job_type='workforce.sync' AND status='completed' AND available_at_utc LIKE ?");$q->execute([$date.'%']);if((int)$q->get_result()->fetch_column()<1)throw new \RuntimeException('WORKFORCE_SYNC_INCOMPLETE');$utc=(string)$db->query("SELECT DATE_FORMAT(UTC_TIMESTAMP(6),'%Y-%m-%dT%H:%i:%s.%fZ')")->fetch_column();$captured=(new \DateTimeImmutable($utc))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d\TH:i:s.uP');return(new \FMonitor2\Workforce\MariaDbInstallerUtilizationObservations($db,$config->prefix(),new \FMonitor2\Workforce\MariaDbInstallerUtilization($yii,$config->prefix(),$config->value('FMONITOR_LEGACY_TABLE_PREFIX'))))->capture($date,$captured);}finally{$yii->close();$db->close();}
 }
}
