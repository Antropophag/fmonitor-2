<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/ObjectQueueFixture.php';
$f=null;
try{
 $f=new ObjectQueueFixture(dirname(__DIR__,2));$db=$f->db;$p=$f->p;FMonitor2\InstallationProcess\LegacyControlEngineerMigrationSchema::apply($db,$p);FMonitor2\InstallationProcess\LegacyControlEngineerImportSchemaMigration::apply($db,$p);$db->query("DELETE FROM {$p}fm2_control_engineer_assignments");
 $page=$f->queue()->read(9101,'451201','',1);assertSameValue(null,$page['objects'][0]['controlEngineer'],'explicit canonical unassigned read');
 $f->insert($p.'fm2_pilot_users',['user_id'=>9500,'full_name'=>'Инженер Импорт','email'=>'engineer.read@shlz.ru','status'=>1,'activation_state'=>'pending_invitation','session_version'=>1,'source_updated_at'=>'2026-09-18T09:00:00Z']);$request='20260918-0000-4000-8000-000000009500';$f->insert($p.'fm2_control_engineer_assignments',['installation_case_id'=>6101,'object_id'=>451201,'assignment_sequence'=>1,'engineer_user_id'=>9500,'engineer_fio_snapshot'=>'Инженер Импорт','engineer_position_snapshot'=>'Инженер строительного контроля','assigned_by_user_id'=>9101,'assigned_at_utc'=>'2026-09-18 09:00:00','request_id'=>$request,'request_fingerprint'=>str_repeat('c',64),'assignment_source'=>'legacy_fmonitor','source_operation_id'=>$request,'source_legacy_object_id'=>451201,'source_legacy_user_id'=>77]);
 $engineer=$f->queue()->read(9101,'451201','',1)['objects'][0]['controlEngineer'];assertSameValue(['userId'=>9500,'fullName'=>'Инженер Импорт','status'=>'Ожидает приглашения'],$engineer,'queue canonical pending engineer');
 $reader=file_get_contents(dirname(__DIR__,2).'/app/InstallationProcess/MariaDbControlEngineerAssignmentReader.php');assertSameValue(true,str_contains($reader,'activation_state')&&str_contains($reader,'Ожидает приглашения')&&str_contains($reader,'Приглашён'),'card read model owns canonical engineer status');assertSameValue(false,str_contains($reader,'responsstroicontrol'),'card read model has no live legacy fallback');echo "PASS legacy control engineer canonical reads\n";
}finally{if($f instanceof ObjectQueueFixture)$f->close();}
