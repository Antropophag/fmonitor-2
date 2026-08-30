<?php
declare(strict_types=1);
require_once __DIR__.'/CompletionFlow.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('FMONITOR_DB_HOST')?:getenv('FMONITOR_DEMO_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:getenv('FMONITOR_DEMO_DB_USER')?:'',getenv('FMONITOR_DB_PASSWORD')?:getenv('FMONITOR_DEMO_DB_PASSWORD')?:'',getenv('FMONITOR_DB_NAME')?:getenv('FMONITOR_DEMO_DB_NAME')?:'',(int)(getenv('FMONITOR_DB_PORT')?:getenv('FMONITOR_DEMO_DB_PORT')?:3306));$db->set_charset('utf8mb4');
$prefix='completion_verify_'.bin2hex(random_bytes(5)).'_';putenv('FMONITOR_PROCESS_TABLE_PREFIX='.$prefix);
$tables=['fm2_pilot_completion_facts','fm2_checklist_operations','fm2_installation_cases'];
$ok=static function(bool$value,string$message):void{if(!$value)throw new RuntimeException($message);};
try{
 $db->query("CREATE TABLE `{$prefix}fm2_installation_cases`(id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT UNIQUE,process_state VARCHAR(40))");
 $db->query("CREATE TABLE `{$prefix}fm2_checklist_operations`(id BIGINT AUTO_INCREMENT PRIMARY KEY,installation_case_id BIGINT,operation_type VARCHAR(40),item_id INT)");
 $db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES(7,4512,'working')");
 $shell='<div class="fm2-object-workspace"></div>';
 $below=RapidPilotCompletionFlow::enhanceCard($shell,4512);$ok(str_contains($below,'Монтажные работы · 0%')&&str_contains($below,'aria-valuenow="0"')&&str_contains($below,'style="width:0%"'),'empty checklist starts montage state with an empty bar');
 $weights=[28,29,30,31,32,33,34,35,36,37,38,39,40,41,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27];
 $insert=$db->prepare("INSERT INTO `{$prefix}fm2_checklist_operations`(installation_case_id,operation_type,item_id)VALUES(7,'item_completed',?)");foreach($weights as$item){$insert->bind_param('i',$item);$insert->execute();}
 $at85=RapidPilotCompletionFlow::enhanceCard($shell,4512);$ok(str_contains($at85,'Документарное закрытие · 85%')&&str_contains($at85,'aria-valuenow="85"')&&str_contains($at85,'style="width:100%"')&&str_contains($at85,'Зафиксировать акт ПТО')&&!str_contains($at85,'value="record_declaration"'),'85% requires PTO first: '.$at85);
 $db->query("INSERT INTO `{$prefix}fm2_pilot_completion_facts`(installation_case_id,fact_type,fact_date,details,recorded_at,recorded_by_user_id)VALUES(7,'pto_act','2026-08-29','','2026-08-30T10:00:00+03:00',18)");
 $afterPto=RapidPilotCompletionFlow::enhanceCard($shell,4512);$ok(str_contains($afterPto,'Акт ПТО от 29.08.2026')&&str_contains($afterPto,'Завершить работы')&&!str_contains($afterPto,'Работы по объекту завершены'),'PTO unlocks declaration but not completion');
 $db->query("INSERT INTO `{$prefix}fm2_pilot_completion_facts`(installation_case_id,fact_type,fact_date,details,recorded_at,recorded_by_user_id)VALUES(7,'declaration','2026-08-30','ЕАЭС N RU Д-RU.РА01.А.12345/26','2026-08-30T12:00:00+03:00',18)");
 $complete=RapidPilotCompletionFlow::enhanceCard($shell,4512);$ok(str_contains($complete,'Работы по объекту завершены')&&str_contains($complete,'Работы завершены')&&str_contains($complete,'100%'),'PTO plus declaration completes object');
 $queue=RapidPilotCompletionFlow::decorateQueue([['id'=>4512,'status'=>'В работе','nextStep'=>'Инспекция'],['id'=>4512,'status'=>'Требуется распоряжение','nextStep'=>'Загрузить распоряжение']],$db,$prefix);$ok($queue[0]['status']==='Работы завершены'&&str_contains($queue[0]['nextStep'],'декларацией'),'completed state reaches opened object in queue');$ok($queue[1]['status']==='Требуется распоряжение'&&$queue[1]['nextStep']==='Загрузить распоряжение','completion projection must not overwrite unopened object status');
 $check='<span data-total-progress>0</span>%<span data-total-items>0</span> из 42 работ<section class="fm2-check-section" data-check-section="8"><div>old item</div></section></body>';
 $enhanced=RapidPilotCompletionFlow::enhanceChecklist($check,4512);$ok(!str_contains($enhanced,'old item')&&!str_contains($enhanced,'Перейти к закрытию')&&str_contains($enhanced,'fm2-check-closeout')&&str_contains($enhanced,'Посмотреть документы')&&str_contains($enhanced,'data-progress-cap="100"')&&str_contains($enhanced,'из 41 монтажной работы'),'checklist exposes a complete closeout block instead of legacy item 42');
 $statuses='<span class="shlz-status shlz-status--orange">Требуется распоряжение</span><span class="shlz-status shlz-status--orange">Готов к открытию</span><span class="shlz-status shlz-status--bright-green">Монтажные работы</span><span class="shlz-status shlz-status--orange">Документарное закрытие</span><span class="shlz-status shlz-status--orange">Работы завершены</span>';$painted=RapidPilotCompletionFlow::paintStatuses($statuses);foreach(['shlz-status--orange">Требуется распоряжение','shlz-status--source-blue">Готов к открытию','shlz-status--cyan">Монтажные работы','shlz-status--purple">Документарное закрытие','shlz-status--bright-green">Работы завершены']as$mapping)$ok(str_contains($painted,$mapping),'status paint mapping missing: '.$mapping);
 echo "PASS rapid completion flow 85% -> PTO -> declaration -> 100%\n";
}finally{foreach($tables as$table)try{$db->query("DROP TABLE IF EXISTS `{$prefix}{$table}`");}catch(Throwable){}$db->close();}
