<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001: real Yii HTTP is the public seam.
$fixture=null;
$failures=[];
$check=static function(mixed$expected,mixed$actual,string$label)use(&$failures):void{
    if($expected!==$actual)$failures[]=$label.' expected '.json_encode($expected,JSON_UNESCAPED_UNICODE).' actual '.json_encode($actual,JSON_UNESCAPED_UNICODE);
};
$row=static function(string$html,int$id):string{
    $start=strpos($html,'data-object-id="'.$id.'"');
    if($start===false)return'';
    $end=strpos($html,'</tr>',$start);
    return$end===false?'':substr($html,$start,$end-$start+5);
};
try{
    $fixture=new InspectionFixture(dirname(__DIR__,2));
    $fixture->open();
    $fixture->queueFixtures();
    $http=$fixture->http;$p=$http->p;
    $http->db->query("INSERT IGNORE INTO {$p}fm2_pilot_role_permissions(role_id,permission) VALUES(7,'inspection.schedule')");
    $long='г. Москва, Бескудниковский бульвар, дом 32, корпус 5, строение 2';
    $statement=$http->db->prepare("UPDATE {$p}fm_maintable SET ordadr_address=?,entrance='1',regnumber='REG-4512' WHERE id=4512");
    $statement->bind_param('s',$long);$statement->execute();
    $http->db->query("UPDATE {$p}fm_maintable SET zavnumber='NO-DOC-4513' WHERE id=4513");
    $today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');
    $caseId=(int)$http->db->query("SELECT id FROM {$p}fm2_installation_cases WHERE legacy_installation_object_id=4512")->fetch_column();
    $http->insert($p.'fm2_pilot_inspection_schedules',['id'=>9951,'installation_case_id'=>$caseId,'legacy_object_id'=>4512,'control_engineer_user_id'=>73,'inspection_date'=>$today,'scheduled_by_user_id'=>73,'scheduled_at'=>$today.'T09:00:00+03:00']);
    $http->insert($p.'fm2_pilot_inspection_schedule_events',['schedule_id'=>9951,'installation_case_id'=>$caseId,'event_type'=>'inspection_scheduled','event_version'=>1,'request_identity'=>'99510000-0000-4000-8000-000000000001','request_fingerprint'=>hash('sha256','mobile-list-today'),'payload_json'=>json_encode(['scheduleId'=>9951,'inspectionDate'=>$today],JSON_THROW_ON_ERROR),'actor_user_id'=>73,'occurred_at'=>$today.'T09:00:00+03:00']);
    $before=$http->facts();
    $page=$fixture->page('/pilot/construction-control?ownership=all&completed=1');
    $check(200,$page['status'],'queue response');
    $target=$row($page['body'],4512);
    foreach([
        $long,
        'Подъезд 1',
        'Рег. № REG-4512',
        'Зав. № CONTROL-4512',
        'class="fm2-control-document-action',
        'href="https://bitrix24public.com/control-4512"',
        'target="_blank"',
        'rel="noopener noreferrer"',
        'class="fm2-control-inspection-action',
        'class="fm2-control-checklist-rail',
        'href="/pilot/construction-control/objects/4512/checklist"',
        'data-shlz-icon="chevron-right-duo"',
        'data-local-sync',
        '>Инспекция сегодня<',
    ]as$needle)$check(true,str_contains($target,$needle),'target row '.$needle);
    $check(false,str_contains($target,(new DateTimeImmutable($today))->format('d.m.Y')),'today label does not repeat date');
    $check(1,preg_match_all('#href="/pilot/construction-control/objects/4512/checklist"#',$target),'only checklist rail links checklist');
    $check(false,str_contains($target,'<a class="fm2-control-link"'),'identity is not a nested checklist link');
    $check(false,preg_match('#fm2-control-(?:document|inspection)-action[^>]*>\s*<img#',$target)===1,'action icons are inline currentColor SVG');
    $withoutDocument=$row($page['body'],4513);
    $check(true,str_contains($withoutDocument,'fm2-control-document-action')&&str_contains($withoutDocument,'disabled'),'missing document has disabled action');
    $check(false,str_contains($withoutDocument,'bitrix24public.com'),'missing document publishes no URL');
    foreach(['fm2-control-toolbar','fm2-control-filters','data-control-search','data-show-completed']as$needle)$check(true,str_contains($page['body'],$needle),'toolbar '.$needle);
    $queueJs=(string)file_get_contents(dirname(__DIR__,2).'/app/YiiRuntime/Assets/control-queue.js');$css=(string)file_get_contents(dirname(__DIR__,2).'/app/YiiRuntime/Assets/pilot.css');
    foreach(['Синхронизировано','Ожидает отправки','Отправляется','Ошибка связи — можно повторить','Конфликт или операция отклонена']as$label)$check(true,str_contains($queueJs,$label),'sync label '.$label);
    foreach(['.fm2-local-sync[data-state="queued"]','.fm2-local-sync[data-state="sending"]','.fm2-local-sync[data-state="retryable"]','.fm2-local-sync[data-state="blocked"]']as$selector)$check(true,str_contains($css,$selector),'sync visual state '.$selector);
    foreach([null,'','0']as$missing){$statement=$http->db->prepare("UPDATE {$p}fm_maintable SET zavnumber=? WHERE id=4513");$statement->bind_param('s',$missing);$statement->execute();$missingRow=$row($fixture->page('/pilot/construction-control?ownership=all&completed=1')['body'],4513);$check(true,str_contains($missingRow,'Заводской номер не указан'),'factory fallback '.var_export($missing,true));$check(false,str_contains($missingRow,'Зав. № QUEUE-4513'),'factory fallback never substitutes registration');}
    $http->db->query("UPDATE {$p}fm_maintable SET zavnumber='CONTROL-4512' WHERE id=4512");
    $http->db->query("UPDATE {$p}fm_maintable SET zavnumber='NO-DOC-4513' WHERE id=4513");
    $factorySearch=$fixture->page('/pilot/construction-control?ownership=all&query=control-4512');
    $check([200,1,true],[$factorySearch['status'],preg_match_all('/data-control-row\b/',$factorySearch['body']),str_contains($factorySearch['body'],'data-object-id="4512"')],'factory number server search');
    $http->db->query("DELETE FROM {$p}fm2_pilot_role_permissions WHERE role_id=7 AND permission='inspection.schedule'");$unauthorized=$row($fixture->page('/pilot/construction-control?ownership=all&completed=1')['body'],4512);$check(false,str_contains($unauthorized,'fm2-control-inspection-action'),'reader without inspection.schedule sees no denied action');$http->db->query("INSERT INTO {$p}fm2_pilot_role_permissions(role_id,permission) VALUES(7,'inspection.schedule')");
    $check($before,$http->facts(),'queue reads preserve facts');
    if($failures!==[]){foreach($failures as$failure)fwrite(STDERR,"INTENDED_RED: {$failure}\n");throw new TestFailure('mobile list acceptance failed: '.count($failures).' findings');}
    echo "PASS: YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001 Yii HTTP\n";
}finally{if($fixture instanceof InspectionFixture)$fixture->close();}
