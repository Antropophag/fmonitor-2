<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__).'/app/PilotHttp/ChecklistSync.php';
use FMonitor2\PilotHttp\ChecklistSync;
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'fmonitor2_demo',getenv('FMONITOR_DB_PASSWORD')?:'fmonitor2_demo_local',getenv('FMONITOR_DB_NAME')?:'fmonitor2_demo',(int)(getenv('FMONITOR_DB_PORT')?:23306));$db->set_charset('utf8mb4');$p='crew_'.bin2hex(random_bytes(5)).'_';
try{
 $db->query("CREATE TABLE `{$p}fm2_installation_cases`(id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT,process_state VARCHAR(80))");
 $db->query("CREATE TABLE `{$p}fm2_assignment_orders`(id BIGINT PRIMARY KEY,installation_case_id BIGINT,version_no INT,status VARCHAR(40))");
 $db->query("CREATE TABLE `{$p}fm2_order_installers`(assignment_order_id BIGINT,installer_tab_id BIGINT,fio_snapshot VARCHAR(300),position_snapshot VARCHAR(300),employment_status_snapshot VARCHAR(40),workforce_source_updated_at_snapshot VARCHAR(40))");
 $db->query("CREATE TABLE `{$p}fm2_workforce_catalog`(installer_tab_id BIGINT PRIMARY KEY,fio VARCHAR(300),position VARCHAR(300),employment_status VARCHAR(40),dismissal_effective_at VARCHAR(40),workforce_source_updated_at VARCHAR(40))");
 $db->query("INSERT INTO `{$p}fm2_installation_cases` VALUES(1,1103,'working')");
 $db->query("INSERT INTO `{$p}fm2_assignment_orders` VALUES(11,1,1,'registered'),(12,1,2,'registered')");
 $db->query("INSERT INTO `{$p}fm2_order_installers` VALUES(11,101,'Первый','Монтажник','employed','2026-08-01T00:00:00+03:00'),(12,202,'Последний','Монтажник','employed','2026-08-02T00:00:00+03:00')");
 $sync=new ChecklistSync($db,$p,sys_get_temp_dir(),'2026-08-30T20:00:00+03:00');$sync->ensureSchema();
 $db->query("INSERT INTO `{$p}fm2_checklist_operations`(installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json) VALUES(1,'11111111-1111-4111-8111-111111111111','22222222-2222-4222-8222-222222222222','item_completed',1,28,9,'2026-08-01T10:00:00+03:00','2026-08-01T10:00:01+03:00',0,1,'{\"installerTabIds\":[\"101\"]}')");
 $db->query("INSERT INTO `{$p}fm2_checklist_operation_installers` VALUES('11111111-1111-4111-8111-111111111111',101,'Первый','Монтажник','employed',NULL,'2026-08-01T00:00:00+03:00','completion')");
 $projection=$sync->projection(1103);$ids=array_column($projection['crew'],'tabId');if($ids!==['202'])throw new RuntimeException('Expected only latest registered order crew, got '.json_encode($ids));$historical=array_column($projection['items']['28']['installers']??[],'tabId');if($historical!==['101'])throw new RuntimeException('Expected historical item installer snapshot 101, got '.json_encode($historical));echo "Checklist current crew contract OK.\n";
}finally{foreach(['fm2_checklist_operation_installers','fm2_checklist_photos','fm2_checklist_operations','fm2_checklist_revisions','fm2_workforce_catalog','fm2_order_installers','fm2_assignment_orders','fm2_installation_cases']as$t)$db->query("DROP TABLE IF EXISTS `{$p}{$t}`");$db->close();}
