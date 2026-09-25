<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

$fixture=null;$process=null;
try{
    $fixture=new InspectionFixture(dirname(__DIR__,2));$fixture->open();$fixture->queueFixtures();
    $http=$fixture->http;$p=$http->p;
    $http->db->query("INSERT IGNORE INTO {$p}fm2_pilot_role_permissions(role_id,permission) VALUES(7,'inspection.schedule')");
    $long='г. Москва, Бескудниковский бульвар, дом 32, корпус 5, строение 2';
    $statement=$http->db->prepare("UPDATE {$p}fm_maintable SET ordadr_address=? WHERE id=4512");$statement->bind_param('s',$long);$statement->execute();
    $http->db->query("UPDATE {$p}fm_maintable SET zavnumber='NO-DOC-4513' WHERE id=4513");
    $http->db->query("UPDATE {$p}fm_maintable SET zavnumber='NO-DOC-4514' WHERE id=4514");
    $http->db->query("INSERT INTO {$p}fm2_equipment_fact_current(object_id,readiness_date,first_shipment_date,full_shipment_date,source,source_order_hmac,last_successful_run_id,last_successful_observed_at) VALUES(4512,NULL,'2026-09-24','2026-09-25','1c_erp',REPEAT('a',64),'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','2026-09-25 09:00:00')");
    $http->db->query("INSERT INTO {$p}fm2_equipment_fact_current(object_id,readiness_date,first_shipment_date,full_shipment_date,source,source_order_hmac,last_successful_run_id,last_successful_observed_at) VALUES(4513,NULL,'2026-09-24',NULL,'1c_erp',REPEAT('b',64),'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb','2026-09-25 09:00:00')");
    $today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');$tomorrow=(new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
    foreach([[4512,9961,$today],[4513,9962,$tomorrow]]as[$objectId,$scheduleId,$date]){$caseId=(int)$http->db->query("SELECT id FROM {$p}fm2_installation_cases WHERE legacy_installation_object_id={$objectId}")->fetch_column();$http->insert($p.'fm2_pilot_inspection_schedules',['id'=>$scheduleId,'installation_case_id'=>$caseId,'legacy_object_id'=>$objectId,'control_engineer_user_id'=>73,'inspection_date'=>$date,'scheduled_by_user_id'=>73,'scheduled_at'=>$today.'T09:00:00+03:00']);$http->insert($p.'fm2_pilot_inspection_schedule_events',['schedule_id'=>$scheduleId,'installation_case_id'=>$caseId,'event_type'=>'inspection_scheduled','event_version'=>1,'request_identity'=>sprintf('99610000-0000-4000-8000-%012d',$scheduleId),'request_fingerprint'=>hash('sha256','mobile-list-'.$scheduleId),'payload_json'=>json_encode(['scheduleId'=>$scheduleId,'inspectionDate'=>$date],JSON_THROW_ON_ERROR),'actor_user_id'=>73,'occurred_at'=>$today.'T09:00:00+03:00']);}
    $config=$http->artifacts.'/control-mobile-list.json';$result=$http->artifacts.'/control-mobile-list-result.json';$log=$http->artifacts.'/control-mobile-list-browser.log';
    file_put_contents($config,json_encode(['origin'=>'http://127.0.0.1:'.$http->server['port'],'email'=>$http->emails[73],'password'=>$http->password,'result'=>$result,'todayLabel'=>(new DateTimeImmutable($today))->format('d.m.Y'),'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($http->root).'/shlz-ui/node_modules/playwright'],JSON_THROW_ON_ERROR));chmod($config,0600);
    $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/construction_control_mobile_list_browser.mjs',$config],[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$http->root);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');
    $deadline=microtime(true)+70;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
    if($state['running']){proc_terminate($process,9);throw new TestFailure('browser timeout '.$http->artifacts);}
    $exit=$state['exitcode'];proc_close($process);$process=null;
    assertSameValue(0,$exit,'INTENDED_RED mobile list browser '.file_get_contents($log));
    assertSameValue(['passed'=>true],json_decode((string)file_get_contents($result),true,flags:JSON_THROW_ON_ERROR),'device matrix');
    echo "PASS: YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001 browser\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($fixture instanceof InspectionFixture)$fixture->close();}
