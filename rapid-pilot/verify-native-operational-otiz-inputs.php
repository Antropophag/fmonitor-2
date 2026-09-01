<?php
declare(strict_types=1);
require_once __DIR__.'/NativeOperationalPremiumInputs.php';
require_once dirname(__DIR__).'/app/InstallationProcess/DatabaseUnavailable.php';
require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbSchemaInspector.php';
require_once dirname(__DIR__).'/app/InstallationProcess/IdentityAccessDefinitionSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbExactSchemaFingerprint.php';
require_once dirname(__DIR__).'/app/InstallationProcess/InspectionEvidenceOperationDefinitionSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/InspectionEvidenceDefinitionSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/InspectionEvidenceSchemaMigration.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('FMONITOR_VERIFY_DB_HOST')?:'mariadb',getenv('FMONITOR_VERIFY_DB_USER')?:'fmonitor2_demo',getenv('FMONITOR_VERIFY_DB_PASSWORD')?:'fmonitor2_demo_local',getenv('FMONITOR_VERIFY_DB_NAME')?:'fmonitor2_demo',(int)(getenv('FMONITOR_VERIFY_DB_PORT')?:3306));$db->set_charset('utf8mb4');$p='native_otiz_'.bin2hex(random_bytes(4)).'_';
$tables=['fm2_pilot_object_details','fm2_checklist_photos','fm2_checklist_operation_installers','fm2_checklist_operations','fm2_checklist_revisions','fm2_checklist_template_snapshots','fm2_order_installers','fm2_assignment_orders','fm_maintable','fm2_migration_classification_provenance','fm2_installation_cases'];
try{
 $db->query("CREATE TABLE `{$p}fm2_installation_cases`(id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT,process_state VARCHAR(40),actual_start_date DATE)");
 $db->query("CREATE TABLE `{$p}fm2_migration_classification_provenance`(output_kind VARCHAR(40),output_id BIGINT,legacy_object_id BIGINT,category VARCHAR(40))");
 $db->query("CREATE TABLE `{$p}fm_maintable`(id BIGINT PRIMARY KEY,regnumber VARCHAR(40),ordadr_address VARCHAR(100))");
 $db->query("CREATE TABLE `{$p}fm2_assignment_orders`(id BIGINT PRIMARY KEY,installation_case_id BIGINT,status VARCHAR(40),version_no INT,order_date DATE,planned_finish_date_snapshot DATE,pto_act_date_snapshot DATE NULL)");
 $db->query("CREATE TABLE `{$p}fm2_order_installers`(assignment_order_id BIGINT,installer_tab_id BIGINT,fio_snapshot VARCHAR(80),position_snapshot VARCHAR(80))");
 $db->query("CREATE TABLE `{$p}fm2_checklist_template_snapshots`(id BIGINT PRIMARY KEY,content_sha256 CHAR(64),payload_json LONGTEXT)");
 \FMonitor2\InstallationProcess\InspectionEvidenceSchemaMigration::apply($db,$p);
 $db->query("CREATE TABLE `{$p}fm2_pilot_object_details`(object_id BIGINT PRIMARY KEY,content_sha256 CHAR(64),payload_json LONGTEXT,captured_at VARCHAR(40))");
 $hash=hash('sha256','template');$payload=$db->real_escape_string(json_encode(['definitions'=>[['id'=>1,'share'=>25],['id'=>2,'share'=>35]]],JSON_THROW_ON_ERROR));
 $db->query("INSERT INTO `{$p}fm2_installation_cases` VALUES(7,7007,'working','2026-08-01'),(8,7008,'working','2026-08-01')");$db->query("INSERT INTO `{$p}fm2_migration_classification_provenance` VALUES('operational_case',7,7007,'native_candidate'),('operational_case',8,7008,'legacy_active')");$db->query("INSERT INTO `{$p}fm_maintable` VALUES(7007,'N-7007','Native address'),(7008,'L-7008','Legacy address')");$db->query("INSERT INTO `{$p}fm2_assignment_orders` VALUES(10,7,'registered',1,'2026-07-01','2026-09-30',NULL),(11,8,'registered',1,'2026-07-01','2026-09-30',NULL)");$db->query("INSERT INTO `{$p}fm2_order_installers` VALUES(10,42,'Native Installer','Монтажник')");$db->query("INSERT INTO `{$p}fm2_checklist_template_snapshots` VALUES(3,'$hash','$payload')");
 $db->query("INSERT INTO `{$p}fm2_checklist_operations`(installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json,template_snapshot_id,template_snapshot_version,template_content_sha256)VALUES(7,'11111111-1111-4111-8111-111111111111','33333333-3333-4333-8333-333333333333','item_completed',1,1,1,'2026-08-10T10:00:00+03:00','2026-08-10T10:01:00+03:00',0,1,'{}',3,'fixture','$hash'),(7,'22222222-2222-4222-8222-222222222222','33333333-3333-4333-8333-333333333333','item_completed',1,2,1,'2026-09-10T10:00:00+03:00','2026-09-10T10:01:00+03:00',1,2,'{}',3,'fixture','$hash')");$db->query("INSERT INTO `{$p}fm2_checklist_operation_installers` VALUES('11111111-1111-4111-8111-111111111111',42,'Native Installer','Монтажник','employed',NULL,'2026-08-01','completion'),('22222222-2222-4222-8222-222222222222',42,'Native Installer','Монтажник','employed',NULL,'2026-08-01','completion')");
 $card=['fields'=>['floors'=>['raw'=>'5'],'weight'=>['raw'=>'320'],'pitmaterial'=>['display'=>'металлокаркас+стекло']]];$cardJson=json_encode($card,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$cardSql=$db->real_escape_string($cardJson);$cardHash=hash('sha256',$cardJson);$db->query("INSERT INTO `{$p}fm2_pilot_object_details` VALUES(7007,'$cardHash','$cardSql','2026-08-01T10:00:00+03:00')");
 $reader=new NativeOperationalPremiumInputs($db,$p);$aug=$reader->forDate('2026-08-31');$sep=$reader->forDate('2026-09-30');
 if(count($aug)!==1||$aug[0]['id']!==7007||$aug[0]['progress']!==2500||$aug[0]['premium']!==52000000||$aug[0]['shaft']!==12500||$aug[0]['issues']!==[])throw new RuntimeException('native/card/as-of filter failed');
 if($sep[0]['progress']!==6000||($sep[0]['operands']['progressBp']['source']['locator']??'')!=='fm2_checklist_operations/case/7')throw new RuntimeException('cumulative provenance failed');
 $db->query("DELETE FROM `{$p}fm2_pilot_object_details`");$blocked=$reader->forDate('2026-09-30');if($blocked[0]['operands']!==null||!in_array('OBJECT_CARD_EVIDENCE_ABSENT',array_column($blocked[0]['issues'],'code'),true))throw new RuntimeException('missing card did not fail closed');
 echo "PASS native-only card-derived OTIZ inputs, as-of progress and blockers\n";
}finally{foreach($tables as$t)$db->query("DROP TABLE IF EXISTS `{$p}{$t}`");$db->close();}
