<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once __DIR__.'/SelectionHttpFixture.php';

use FMonitor2\Tests\Support\SelectionHttpFixture;
use FMonitor2\InstallationProcess\{PilotOtizSchemaMigration,OtizPublicationSchemaMigration,OtizEvidenceSchemaMigration};

$prefix='otiz_browser_'.bin2hex(random_bytes(5)).'_';
$dmlUser='otiz_ui_'.bin2hex(random_bytes(5));$dmlPassword=bin2hex(random_bytes(18));$dmlHost='%';$admin=null;$fixture=null;
$artifacts=sys_get_temp_dir().'/fmonitor-otiz-browser-'.bin2hex(random_bytes(6));
try {
$fixture=new SelectionHttpFixture(true,static function($original)use($prefix,$dmlUser,$dmlPassword,$dmlHost,&$admin):array{
    $db=$original->selection->db;
    $migration=FMonitor2\InstallationProcess\CanonicalMigrationApplication::run($db,$prefix,FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue::migrations());
    if(($migration['exitCode']??1)!==0)throw new RuntimeException('canonical fixture migration failed');
    OtizPublicationSchemaMigration::apply($db,$prefix);OtizEvidenceSchemaMigration::apply($db,$prefix);
    $admin=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local','',(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    $escaped=$admin->real_escape_string($dmlPassword);$admin->query("CREATE USER '{$dmlUser}'@'{$dmlHost}' IDENTIFIED BY '{$escaped}'");
    $database=str_replace('`','``',$original->selection->schema->source->name);$admin->query("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$database}`.* TO '{$dmlUser}'@'{$dmlHost}'");
    return['FMONITOR_NOW'=>'2026-09-08T15:00:00+03:00','FMONITOR_DB_USER'=>$dmlUser,'FMONITOR_DB_PASSWORD'=>$dmlPassword];
},$prefix);
    $db=$fixture->original->selection->db;
    $db->query("INSERT IGNORE INTO `{$prefix}fm2_pilot_role_permissions`(role_id,permission)VALUES(3,'otiz.manage'),(3,'objects.read')");
    $password='Synthetic-Otiz-Browser-2026!';$hash=password_hash($password,PASSWORD_ARGON2ID);
    $s=$db->prepare("UPDATE `{$prefix}fm2_pilot_auth_credentials` SET password_hash=? WHERE user_id=31");$s->bind_param('s',$hash);$s->execute();

    $db->query("UPDATE `{$prefix}fm2_installation_cases` SET process_state='working',actual_start_date='2026-08-01',opened_at='2026-08-01T09:00:00Z',opened_by_user_id=31 WHERE id=4512");
    $db->query("INSERT INTO `{$prefix}fm2_migration_classification_provenance`(legacy_object_id,output_kind,output_id,source_cutoff_at,classification_version,category,reason_codes_json,classification_sha256,created_at) VALUES(4512,'operational_case',4512,'2026-08-01 09:00:00','synthetic-v1','native_candidate','[]','".str_repeat('a',64)."','2026-08-01 09:00:00')");
    $db->query("INSERT INTO `{$prefix}fm2_assignment_orders`(id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id) VALUES(81,4512,1,'initial','registered','2026-08-01',73,'Инженер теста','Инженер стройконтроля','contract','Москва, Тестовая улица, 1','2','77-000123','2026-08-01','2026-09-30','2026-08-01T09:00:00Z',31)");
    $db->query("INSERT INTO `{$prefix}fm2_order_installers`(assignment_order_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,change_action) VALUES(81,7001,'Монтажник 7001','Монтажник','employed','2020-01-01','synthetic-hr','2026-09-01T06:00:00Z','2026-08-01','assigned')");
    $payload=json_encode(['snapshotVersion'=>'otiz-browser-v1','definitions'=>[['id'=>1,'share'=>85]]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    $templateHash=hash('sha256',$payload);
    $s=$db->prepare("INSERT INTO `{$prefix}fm2_checklist_template_snapshots`(id,snapshot_version,captured_at,valid_from,validity_scope,source_label,content_sha256,payload_json,created_at) VALUES(3,'otiz-browser-v1','2026-08-01 00:00:00','2026-08-01 00:00:00','synthetic','Synthetic browser fixture',?,?,'2026-08-01 00:00:00')");$s->bind_param('ss',$templateHash,$payload);$s->execute();
    $db->query("INSERT INTO `{$prefix}fm2_checklist_operations`(installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json,template_snapshot_id,template_snapshot_version,template_content_sha256) VALUES(4512,'11111111-1111-4111-8111-111111111111','22222222-2222-4222-8222-222222222222','item_completed',1,1,73,'2026-08-20T10:00:00+03:00','2026-08-20T10:00:01+03:00',0,1,'{}',3,'otiz-browser-v1','{$templateHash}')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_operation_installers`(client_operation_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,workforce_source_updated_at_snapshot,assignment_source) VALUES('11111111-1111-4111-8111-111111111111',7001,'Монтажник 7001','Монтажник','employed','2026-09-01T06:00:00Z','completion')");
    $detail=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4512,'fields'=>['floors'=>['raw'=>9],'weight'=>['raw'=>1000],'pitmaterial'=>['display'=>'Железобетон'],'lift_type'=>['display'=>'Пассажирский']]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    $s=$db->prepare("INSERT INTO `{$prefix}fm2_pilot_object_details`(object_id,schema_version,content_sha256,payload_json,captured_at) VALUES(4512,'technical-object-detail-v1',?,?, '2026-08-01T09:00:00Z')");$detailHash=hash('sha256',$detail);$s->bind_param('ss',$detailHash,$detail);$s->execute();
    if(!mkdir($artifacts,0700))throw new RuntimeException('artifact directory');
    $node=getenv('FMONITOR_TEST_NODE_BINARY')?:trim((string)shell_exec('command -v node'));
    $playwright=getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname(__DIR__,3).'/shlz-ui/node_modules/playwright';
    $result=$artifacts.'/result.json';
    $process=proc_open([$node,dirname(__DIR__).'/Otiz/snapshot_publication_browser_001_test.mjs',(string)$fixture->port,$playwright,$artifacts,$result],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if(!is_resource($process))throw new RuntimeException('browser process');
    $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
    assertSameValue(0,$exit,'browser exit stderr='.$stderr.' stdout='.$stdout);
    $observed=json_decode((string)file_get_contents($result),true,flags:JSON_THROW_ON_ERROR);
    $snapshot=$db->query("SELECT id,status,total_pool_cents FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc();
    assertSameValue(true,(int)$snapshot['total_pool_cents']>0,'nonempty native synthetic input produced a payable total');
    assertSameValue('accepted',$snapshot['status'],'clicked acceptance persisted');
    assertSameValue(1,(int)$db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_otiz_publications`")->fetch_assoc()['n'],'response-loss replay kept one publication receipt');
    assertSameValue(1,(int)$db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_events` WHERE event_type='draft_calculated'")->fetch_assoc()['n'],'response-loss replay kept one publication event');
    assertSameValue(true,($observed['xlsxBytes']??0)>1000,'clicked XLSX download has bytes');
    echo json_encode(['status'=>'OTIZ_BROWSER_OK','artifacts'=>$artifacts,'observed'=>$observed,'snapshotId'=>(int)$snapshot['id'],'totalPoolCents'=>(int)$snapshot['total_pool_cents']],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
} finally {
    if($fixture instanceof SelectionHttpFixture)$fixture->close();
    if($admin instanceof mysqli){try{$admin->query("DROP USER IF EXISTS '{$dmlUser}'@'{$dmlHost}'");}finally{$admin->close();}}
}
