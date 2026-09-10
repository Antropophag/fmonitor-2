<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/ObjectQueueFixture.php';require __DIR__.'/QueueReadinessFaults.php';
// YII2-OBJECT-QUEUE-001: real canonical DB, old public readiness oracle versus Yii public owners.
$f=null;
try {
 $f=new ObjectQueueFixture(dirname(__DIR__,2));$f->planning();$h=$f->http;$p=$f->p;$h->start();$cookies=[];assertSameValue(303,$h->login($cookies)['status'],'native login');
 $ready=static fn()=>FMonitor2\InstallationProcess\InspectionPlanningSchemaMigration::isCompleteCompatible($f->db,$p);
 assertSameValue(true,$ready(),'canonical planning predicate ready');
 $table=$p.'fm2_pilot_inspection_schedules';$events=$p.'fm2_pilot_inspection_schedule_events';
 $checkName=(string)$f->db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$events' AND CONSTRAINT_TYPE='CHECK'")->fetch_column();
 $faults=[
  ["RENAME TABLE $table TO {$table}_missing","RENAME TABLE {$table}_missing TO $table",'missing'],
  ["ALTER TABLE $table ADD COLUMN unexpected INT NULL","ALTER TABLE $table DROP COLUMN unexpected",'extra column'],
  ["ALTER TABLE $table MODIFY inspection_date VARCHAR(20) NOT NULL","ALTER TABLE $table MODIFY inspection_date DATE NOT NULL",'column type'],
  ["ALTER TABLE $table DROP INDEX unique_planned_inspection","ALTER TABLE $table ADD UNIQUE KEY unique_planned_inspection(installation_case_id,control_engineer_user_id,inspection_date)",'unique index'],
  ["ALTER TABLE $events DROP CONSTRAINT `$checkName`","ALTER TABLE $events ADD CONSTRAINT `$checkName` CHECK(JSON_VALID(payload_json))",'json check'],
  ["ALTER TABLE $table ADD CONSTRAINT {$p}extra_plan_fk FOREIGN KEY(installation_case_id) REFERENCES {$p}fm2_installation_cases(id)","ALTER TABLE $table DROP FOREIGN KEY {$p}extra_plan_fk",'extra foreign key'],
 ];
 foreach($faults as[$break,$restore,$label]){
  $f->db->query($break);
  try {
   assertSameValue(false,$ready(),'old predicate detects '.$label);$before=$f->facts();$failed=false;
   try{$f->planning()->scheduleInspection(9101,451201,'2026-09-12');}catch(Throwable){$failed=true;}
   assertSameValue(true,$failed,'Yii owner same drift rejection '.$label);assertSameValue($before,$f->facts(),'owner no repair '.$label);
   $r=$h->request('GET','/pilot/objects',[],$cookies);assertSameValue(503,$r['status'],'queue same drift rejection '.$label);assertSameValue($before,$f->facts(),'HTTP no repair '.$label);
  } finally {$f->db->query($restore);}
  assertSameValue(true,$ready(),'restored planning ready '.$label);
 }
 foreach(['fm2_pilot_completion_facts','fm2_checklist_operations']as$suffix){
  $table=$p.$suffix;$f->db->query("ALTER TABLE $table ADD COLUMN unexpected INT NULL");
  try{$before=$f->facts();assertSameValue(503,$h->request('GET','/pilot/objects',[],$cookies)['status'],'queue family drift '.$suffix);assertSameValue($before,$f->facts(),'family drift not repaired');}
  finally{$f->db->query("ALTER TABLE $table DROP COLUMN unexpected");}
 }
 QueueReadinessFaults::verify($f,$cookies);
 assertSameValue(200,$h->request('GET','/pilot/objects',[],$cookies)['status'],'restored canonical schema usable');
 echo "PASS: YII2-OBJECT-QUEUE-001 Yii readiness parity and zero repair\n";
}finally{if($f instanceof ObjectQueueFixture)$f->close();}
