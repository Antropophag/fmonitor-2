<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{OriginalHttpFixture as F,SelectionHttpAssertions as A};
use FMonitor2\InstallationProcess as I;
$oldNow=getenv('FMONITOR_NOW');putenv('FMONITOR_NOW=2026-09-07T12:00:00+03:00');$f=new F();
try {
    $native=$f->http->original->selection;$db=$native->db;
    I\AssignmentOrderApplicationSchemaMigration::apply($db);
    I\ChecklistTemplateSchemaMigration::apply($db);
    I\InspectionEvidenceSchemaMigration::apply($db);I\InstallationCompletionDetailsSchemaMigration::apply($db);I\ObjectDetailSnapshotSchemaMigration::apply($db);
    foreach(['assignment_order.composition.apply','installation.open','objects.read','checklist.read'] as $cap)$native->schema->insert('fm2_pilot_role_permissions',['role_id'=>1,'permission'=>$cap]);
    $payload='{"sections":[{"id":1,"name":"Работы","items":[{"id":28,"name":"Работа","weight":2}]}]}';
    $native->schema->insert('fm2_checklist_template_snapshots',['snapshot_version'=>'manual-http-smoke-v1','captured_at'=>'2026-09-01 00:00:00','valid_from'=>'2026-09-01 00:00:00','validity_scope'=>'active_baseline_and_future_native_only','source_label'=>'synthetic HTTP smoke','content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'created_at'=>'2026-09-01 00:00:00']);
    $db->query('UPDATE fm_maintable SET workdatestart=NULL,plan_finish_date=NULL');
    $template=$f->http->request('POST','/pilot/objects/4512/assignment-orders/81/template',http_build_query(['csrfToken'=>$f->http->csrf]));assertSameValue(200,$template['status'],'optional PDF with unknown planning dates');
    $accepted=F::json($f->post($f->fields()),201);
    $history=$f->http->request('GET','/pilot/objects/4512/assignment-orders/81/originals/history');assertSameValue(200,$history['status'],'original history HTTP');
    $download=$f->http->request('GET','/pilot/objects/4512/assignment-orders/81/originals/'.$accepted['currentRevisionId'].'/download');assertSameValue(200,$download['status'],'original download HTTP');assertSameValue($accepted['sha256'],hash('sha256',$download['body']),'download exact accepted PDF');
    $path='/pilot/objects/4512/execution';$page=$f->http->request('GET',$path);
    assertSameValue(200,$page['status'],'execution page after accepted original');
    $fields=['csrfToken'=>$f->http->csrf,'action'=>'apply','requestId'=>A::field($page['body'],'requestId'),'orderId'=>'81','revisionId'=>A::field($page['body'],'revisionId'),'sequence'=>'0'];
    assertSameValue(303,$f->http->request('POST',$path,http_build_query($fields))['status'],'apply through HTTP');
    $page=$f->http->request('GET',$path);assertSameValue(200,$page['status'],'applied page');
    assertSameValue(true,str_contains($page['body'],'Состав применён'),'applied state visible');
    $fields=['csrfToken'=>$f->http->csrf,'action'=>'open','applicationId'=>A::field($page['body'],'applicationId'),'actualStartDate'=>'2026-09-04'];
    assertSameValue(303,$f->http->request('POST',$path,http_build_query($fields))['status'],'opening through HTTP');
    $page=$f->http->request('GET',$path);assertSameValue(true,str_contains($page['body'],'Перейти к чек-листу'),'checklist link after opening');
    $card=$f->http->request('GET','/pilot/objects/4512');if($card['status']!==200)copy($f->http->original->control.'/http.log','/tmp/fm2-manual-card-error.log');assertSameValue(200,$card['status'],'native applied object card');
    assertSameValue(false,str_contains($card['body'],'Зарегистрировано в 1С ДО'),'no fabricated registration');assertSameValue(true,str_contains($card['body'],'Ход работ'),'completion panel visible on actual card');
    $denied=$f->http->request('POST',$path,http_build_query($fields),99);assertSameValue(403,$denied['status'],'admin does not inherit FKR action');
    echo "PASS manual HTTP original -> application -> opening -> current object card\n";
}finally{$f->close();putenv($oldNow===false?'FMONITOR_NOW':'FMONITOR_NOW='.$oldNow);}
