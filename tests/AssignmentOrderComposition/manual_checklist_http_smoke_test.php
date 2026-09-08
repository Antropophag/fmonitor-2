<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require dirname(__DIR__,2).'/app/PilotHttp/ConstructionControlView.php';
use FMonitor\IdentityAccess as S;
use FMonitor2\Tests\Support\{OriginalHttpFixture as F,SelectionHttpAssertions as A};
use FMonitor2\InstallationProcess as I;

$oldNow=getenv('FMONITOR_NOW');putenv('FMONITOR_NOW=2026-09-07T12:00:00+03:00');$f=new F();
try{
    $native=$f->http->original->selection;$db=$native->db;
    I\AssignmentOrderApplicationSchemaMigration::apply($db);I\ChecklistTemplateSchemaMigration::apply($db);
    I\InspectionEvidenceSchemaMigration::apply($db);I\InstallationCompletionDetailsSchemaMigration::apply($db);I\ObjectDetailSnapshotSchemaMigration::apply($db);
    foreach(['assignment_order.composition.apply','installation.open','objects.read','checklist.read']as$cap)$native->schema->insert('fm2_pilot_role_permissions',['role_id'=>1,'permission'=>$cap]);
    foreach(['objects.read','construction_control.read','checklist.read','checklist.edit','inspection.item.complete','inspection.photo.revoke']as$cap)$native->schema->insert('fm2_pilot_role_permissions',['role_id'=>2,'permission'=>$cap]);
    $db->query("UPDATE fm2_pilot_users SET email='test73@shlz.ru' WHERE user_id=73");
    $native->schema->insert('fm2_pilot_auth_credentials',['user_id'=>73,'email_normalized'=>'test73@shlz.ru','password_hash'=>'fixture-not-used-for-login','updated_at'=>'2026-09-01T06:00:00Z']);
    $payload='{"snapshotVersion":"manual-checklist-v1","capturedAt":"2026-09-01 00:00:00","validFrom":"2026-09-01 00:00:00","validity":"synthetic","source":"synthetic","parts":[{"id":777,"name":"Монтаж ДШ","rang":40},{"id":500,"name":"Первый","rang":10},{"id":900,"name":"Третий","rang":30},{"id":700,"name":"Второй","rang":20}],"definitions":[{"id":10,"part_id":777,"name":"Монтаж постов","share":1,"rang":40,"needphoto":1},{"id":7,"part_id":777,"name":"Сборка порталов","share":2,"rang":10,"needphoto":1},{"id":9,"part_id":777,"name":"Установка фартуков","share":1,"rang":30,"needphoto":1},{"id":8,"part_id":777,"name":"Монтаж ДШ","share":5,"rang":20,"needphoto":1}]}';
    $native->schema->insert('fm2_checklist_template_snapshots',['snapshot_version'=>'manual-checklist-v1','captured_at'=>'2026-09-01 00:00:00','valid_from'=>'2026-09-01 00:00:00','validity_scope'=>'active_baseline_and_future_native_only','source_label'=>'synthetic checklist smoke','content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'created_at'=>'2026-09-01 00:00:00']);
    F::json($f->post($f->fields()),201);
    $execution='/pilot/objects/4512/execution';$page=$f->http->request('GET',$execution);
    $apply=['csrfToken'=>$f->http->csrf,'action'=>'apply','requestId'=>A::field($page['body'],'requestId'),'orderId'=>'81','revisionId'=>A::field($page['body'],'revisionId'),'sequence'=>'0'];
    assertSameValue(303,$f->http->request('POST',$execution,http_build_query($apply))['status'],'apply selected original');
    $page=$f->http->request('GET',$execution);$open=['csrfToken'=>$f->http->csrf,'action'=>'open','applicationId'=>A::field($page['body'],'applicationId'),'actualStartDate'=>'2026-09-04'];
    assertSameValue(303,$f->http->request('POST',$execution,http_build_query($open))['status'],'open applied installation');

    $session=(new S\PilotSessionStorageFactory())->create(new S\PilotSessionStorageConfig($f->http->stateRoot,'pilot'),new S\NativePilotSessionFilesystem(),new S\SystemPilotSessionClock(),new S\CsprngPilotSessionEntropy(),new S\NoOpPilotSessionLifecycleObserver());
    $started=$session->start(null);$sid=$started->currentSessionId();$session->writeCommit($sid,serialize(['auth_user_id'=>73,'auth_email'=>'test73@shlz.ru','auth_csrf'=>$f->http->csrf]));$session->close();
    $property=new ReflectionProperty($f->http,'cookies');$cookies=$property->getValue($f->http);$cookies[73]='fm2auth_'.$f->http->port.'='.$sid;$property->setValue($f->http,$cookies);
    $queue=(new FMonitor2\PilotHttp\MariaDbConstructionControlQueue($db,'',''))->read(1);$queueHtml=(new FMonitor2\PilotHttp\ProductionConstructionControlRenderer())->render(new FMonitor2\PilotHttp\HttpUser(73,'Инженер теста','test73@shlz.ru'),$queue);assertSameValue(true,str_contains($queueHtml,'Инженер теста')&&str_contains($queueHtml,'/pilot/construction-control/objects/4512/checklist'),'queue uses applied engineer and checklist link');
    $checklist='/pilot/objects/4512/checklist';$page=$f->http->request('GET',$checklist,'',73);
    assertSameValue(200,$page['status'],'applied engineer opens checklist');preg_match('/data-csrf="([0-9a-f]+)"/',$page['body'],$match);$csrf=$match[1]??'';assertSameValue(32,strlen($csrf),'checklist CSRF');$pilotCookie=explode(';',$page['headers']['set-cookie']??'',2)[0];$requestHeaders=['Cookie'=>$cookies[73].'; '.$pilotCookie,'Origin'=>'https://127.0.0.1:'.$f->http->port,'Sec-Fetch-Site'=>'same-origin'];
    $device='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';$revision=0;$counter=1;
    $post=function(array$operation)use($f,$checklist,$csrf,$requestHeaders):array{$body=json_encode($operation,JSON_THROW_ON_ERROR);$r=$f->http->request('POST',$checklist.'/operations',$body,73,$requestHeaders+['Content-Type'=>'application/json; charset=UTF-8','X-FM2-CSRF'=>$csrf]);assertSameValue(200,$r['status'],'checklist operation accepted: '.$r['body']);return json_decode($r['body'],true,512,JSON_THROW_ON_ERROR);};
    $operation=function(string$type,array$fields=[])use(&$counter,$device,&$revision):array{return['clientOperationId'=>sprintf('bbbbbbbb-bbbb-4bbb-8bbb-%012d',$counter++),'deviceInstallationId'=>$device,'type'=>$type,'deviceTime'=>'2026-09-07T08:00:00+03:00','baseRevision'=>$revision]+$fields;};
    foreach([7,8,9,10]as$item){$command=$operation('item_completed',['sectionId'=>4,'itemId'=>$item,'installerTabIds'=>[7001]]);$result=$post($command);$revision=$result['revision'];}
    $pngA=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',true);
    $photo=function(string$bytes,string$name)use($f,$checklist,$csrf,$device,&$revision,&$counter,$requestHeaders):array{$meta=['clientOperationId'=>sprintf('bbbbbbbb-bbbb-4bbb-8bbb-%012d',$counter++),'deviceInstallationId'=>$device,'type'=>'photo_uploaded','deviceTime'=>'2026-09-07T08:01:00+03:00','baseRevision'=>$revision,'sectionId'=>4,'sha256'=>hash('sha256',$bytes),'mime'=>'image/png','size'=>strlen($bytes),'originalName'=>$name];$r=$f->http->request('POST',$checklist.'/photos',$bytes,73,$requestHeaders+['Content-Type'=>'image/png','X-FM2-CSRF'=>$csrf,'X-FM2-Operation'=>base64_encode(json_encode($meta,JSON_THROW_ON_ERROR))]);assertSameValue(200,$r['status'],'photo accepted: '.$r['body']);return json_decode($r['body'],true,512,JSON_THROW_ON_ERROR);};
    $result=$photo($pngA,'section-4-a.png');$revision=$result['revision'];$photoId=$result['projection']['photos'][0]['id'];
    $complete=$operation('section_completed',['sectionId'=>4]);$result=$post($complete);$revision=$result['revision'];
    $repeat=$post($complete);assertSameValue(['duplicate',$revision],[$repeat['status'],$repeat['revision']],'same payload replay is stable');
    $item=$result['projection']['items']['7'];$retract=$operation('completion_retracted',['sectionId'=>4,'itemId'=>7,'originalClientOperationId'=>$item['clientOperationId'],'reason'=>'Исправлена ошибочная отметка']);$result=$post($retract);$revision=$result['revision'];assertSameValue(false,isset($result['projection']['completedSections']['4']),'retraction reopens section');
    $result=$post($operation('item_completed',['sectionId'=>4,'itemId'=>7,'installerTabIds'=>[7001]]));$revision=$result['revision'];$result=$post($operation('section_completed',['sectionId'=>4]));$revision=$result['revision'];
    $blocked=$operation('photo_revoked',['sectionId'=>4,'photoId'=>$photoId,'reason'=>'Нужна более чёткая фотография']);$body=json_encode($blocked,JSON_THROW_ON_ERROR);$r=$f->http->request('POST',$checklist.'/operations',$body,73,$requestHeaders+['Content-Type'=>'application/json; charset=UTF-8','X-FM2-CSRF'=>$csrf]);assertSameValue(422,$r['status'],'last completed-section photo cannot be revoked');
    $pngB=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nS8AAAAASUVORK5CYII=',true);$result=$photo($pngB,'section-4-b.png');$revision=$result['revision'];$blocked['baseRevision']=$revision;$result=$post($blocked);$revision=$result['revision'];
    $caseId=(int)$db->query('SELECT id FROM fm2_installation_cases WHERE legacy_installation_object_id=4512')->fetch_assoc()['id'];
    $facts=$db->query("SELECT operation_type,device_installation_id,device_time,payload_json FROM fm2_checklist_operations WHERE installation_case_id=$caseId ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    assertSameValue(['completion_retracted','photo_revoked'],[$facts[6]['operation_type'],$facts[array_key_last($facts)]['operation_type']],'append-only correction and revoke facts');
    assertSameValue([$device,'2026-09-07T08:00:00+03:00'],[$facts[0]['device_installation_id'],$facts[0]['device_time']],'offline device metadata reaches server unchanged');
    assertSameValue(9,(new FMonitor2\InspectionEvidence\MariaDbChecklistProgress($db,''))->forCase($caseId),'working section contributes only its item weights');
    assertSameValue(false,(bool)$db->query("SELECT COUNT(*) n FROM fm2_checklist_operations WHERE installation_case_id=$caseId AND item_id=42")->fetch_assoc()['n'],'documentary item 42 is not manufactured');
    echo "PASS manual HTTP applied composition -> checklist -> photo -> correction -> completion\n";
}finally{$f->close();putenv($oldNow===false?'FMONITOR_NOW':'FMONITOR_NOW='.$oldNow);}
