<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

// YII2-CONSTRUCTION-CONTROL-PREOPENING-001: real Yii HTTP is the public seam.
$f=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));
    foreach(['construction_control.read','checklist.read','installation.open']as$permission)$f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>2,'permission'=>$permission]);
    $f->insert($f->p.'fm2_process_user_capabilities',['user_id'=>73,'capability'=>'installation.open','position_snapshot'=>null]);
    $f->insert($f->p.'fm2_process_user_capabilities',['user_id'=>95,'capability'=>'installation.open','position_snapshot'=>null]);
    $f->start();
    $fkr=[];assertSameValue(303,$f->login($fkr,18)['status'],'FKR login');
    assertSameValue(303,$f->selection($fkr)['status'],'selected assigned engineer');
    $engineer=[];assertSameValue(303,$f->login($engineer,73)['status'],'assigned engineer login');
    $selectedOnly=$f->facts();
    $notReady=$f->request('GET','/pilot/construction-control',[],$engineer);
    assertSameValue(200,$notReady['status'],'selected-only queue response');
    assertSameValue(false,str_contains($notReady['body'],'data-object-id="4512"'),'selection without original is absent');
    assertSameValue($selectedOnly,$f->facts(),'selected-only queue read-only');
    $receipt=$f->upload($fkr,$f->metadata($fkr));
    assertSameValue(201,$receipt['status'],'accepted current original');
    $original=json_decode($receipt['body'],true,flags:JSON_THROW_ON_ERROR);

    $before=$f->facts();
    $queue=$f->request('GET','/pilot/construction-control',[],$engineer);
    assertSameValue(200,$queue['status'],'ready queue response');
    assertSameValue(1,substr_count($queue['body'],'data-object-id="4512"'),'INTENDED_RED ready object appears exactly once');
    assertSameValue(true,str_contains($queue['body'],'Готов к открытию'),'ready status visible');
    assertSameValue($before,$f->facts(),'ready queue GET is read-only');
    $head=$f->request('HEAD','/pilot/construction-control',[],$engineer);
    assertSameValue([200,''],[$head['status'],$head['body']],'queue HEAD admission with empty body');
    assertSameValue($before,$f->facts(),'queue HEAD is read-only');

    $f->insert($f->p.'fm2_pilot_completion_facts',['installation_case_id'=>6101,'fact_type'=>'pto_act','fact_date'=>'2026-09-01','details'=>'','recorded_at'=>'2026-09-01T12:00:00+03:00','recorded_by_user_id'=>18]);
    $withPto=$f->facts();$ptoQueue=$f->request('GET','/pilot/construction-control',[],$engineer);
    assertSameValue(false,str_contains($ptoQueue['body'],'data-object-id="4512"'),'ready case with PTO absent');
    assertSameValue($withPto,$f->facts(),'PTO queue read-only');
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_completion_facts WHERE installation_case_id=6101 AND fact_type='pto_act'");

    $checklist=$f->request('GET','/pilot/construction-control/objects/4512/checklist',[],$engineer);
    assertSameValue(200,$checklist['status'],'assigned ready checklist');
    foreach(['Готов к открытию','method="post" action="/pilot/objects/4512/execution?return=construction-control"','name="action" value="open_confirmed"','name="actualStartDate"','>Открыть работы</button>','data-enabled="false"']as$marker)
        assertSameValue(true,str_contains($checklist['body'],$marker),'preopening checklist marker '.$marker);
    assertSameValue($before,$f->facts(),'ready checklist GET is read-only');
    $readyHead=$f->request('HEAD','/pilot/construction-control/objects/4512/checklist',[],$engineer);
    assertSameValue([200,''],[$readyHead['status'],$readyHead['body']],'ready checklist HEAD');
    assertSameValue($before,$f->facts(),'ready checklist HEAD is read-only');

    preg_match_all('/<form\b[^>]*>.*?<\/form>/s',$checklist['body'],$formMatches);
    $openingForms=array_values(array_filter($formMatches[0],static fn(string $form):bool=>str_contains($form,'name="action" value="open_confirmed"')));
    assertSameValue(1,count($openingForms),'one rendered opening form');
    assertSameValue(1,preg_match('/<form\b(?=[^>]*method="post")(?=[^>]*action="([^"]+)")[^>]*>/',$openingForms[0],$actionMatch),'rendered opening form action');
    $renderedAction=html_entity_decode($actionMatch[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
    assertSameValue('/pilot/objects/4512/execution?return=construction-control',$renderedAction,'exact parsed opening action');
    $rendered=[];foreach(['requestId','orderId','revisionId','sequence']as$name){
        assertSameValue(1,preg_match('/name="'.preg_quote($name,'/').'" value="([^"]+)"/',$checklist['body'],$m),'rendered '.$name);
        $rendered[$name]=html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
    }
    assertSameValue(true,preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$rendered['requestId'])===1,'fresh rendered UUIDv4');
    assertSameValue(['81',$original['currentRevisionId'],'0'],[$rendered['orderId'],$rendered['revisionId'],$rendered['sequence']],'rendered current owner intent');
    $operation=json_encode(['clientOperationId'=>'aaaaaaaa-aaaa-4aaa-8aaa-000000000040','deviceInstallationId'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb','type'=>'item_completed','deviceTime'=>'2026-09-02T10:00:00+03:00','baseRevision'=>0,'sectionId'=>1,'itemId'=>28,'installerTabIds'=>[7001]],JSON_THROW_ON_ERROR);
    $locked=$f->request('POST','/pilot/construction-control/objects/4512/checklist/operations',[],$engineer,['Content-Type: application/json; charset=UTF-8','X-FM2-CSRF: '.$f->csrf($checklist['body']),'Content-Length: '.strlen($operation)],$operation);
    assertSameValue(403,$locked['status'],'preopening checklist mutation locked');
    assertSameValue(403,$f->request('GET','/pilot/construction-control/objects/4512/sync-context',[],$engineer)['status'],'preopening sync context locked');
    assertSameValue($before,$f->facts(),'locked checklist attempts write no facts');

    $f->db->query("DELETE FROM {$f->p}fm2_pilot_user_roles WHERE user_id=95");
    $f->insert($f->p.'fm2_pilot_user_roles',['user_id'=>95,'role_id'=>2,'origin'=>'fixture','assigned_at'=>'2026-09-10T09:00:00+03:00']);
    $foreign=[];assertSameValue(303,$f->login($foreign,95)['status'],'foreign actor login');
    $foreignBefore=$f->facts();
    $foreignQueue=$f->request('GET','/pilot/construction-control',[],$foreign);
    assertSameValue(200,$foreignQueue['status'],'foreign actor admitted to queue seam');
    assertSameValue(false,str_contains($foreignQueue['body'],'data-object-id="4512"'),'foreign ready object absent server-side');
    $foreignChecklist=$f->request('GET','/pilot/construction-control/objects/4512/checklist',[],$foreign);
    assertSameValue(200,$foreignChecklist['status'],'foreign actor admitted to checklist read seam');
    assertSameValue(false,str_contains($foreignChecklist['body'],'name="action" value="open_confirmed"'),'foreign opening form absent');
    assertSameValue($foreignBefore,$f->facts(),'foreign reads write no facts');
    $foreignIntent=$rendered;$foreignIntent['requestId']='40404040-4040-4040-8040-404040404095';
    $foreignOpen=$f->form($renderedAction,['_csrf'=>$f->csrf($foreignChecklist['body']),'action'=>'open_confirmed','requestId'=>$foreignIntent['requestId'],'orderId'=>$foreignIntent['orderId'],'revisionId'=>$foreignIntent['revisionId'],'sequence'=>$foreignIntent['sequence'],'actualStartDate'=>'2026-09-02'],$foreign);
    assertSameValue(422,$foreignOpen['status'],'otherwise authorized foreign engineer rejected by owner');
    assertSameValue($foreignBefore,$f->facts(),'foreign opening writes no facts');

    $f->db->query("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE permission='installation.open'");
    $noGrant=$f->request('GET','/pilot/construction-control/objects/4512/checklist',[],$engineer);
    assertSameValue(false,str_contains($noGrant['body'],'name="action" value="open_confirmed"'),'opening form absent without exact grant');
    $revokedBefore=$f->facts();
    $revoked=$f->form($renderedAction,[
        '_csrf'=>$f->csrf($checklist['body']),'action'=>'open_confirmed','requestId'=>$rendered['requestId'],
        'orderId'=>$rendered['orderId'],'revisionId'=>$rendered['revisionId'],'sequence'=>$rendered['sequence'],'actualStartDate'=>'2026-09-02',
    ],$engineer);
    assertSameValue(403,$revoked['status'],'revoked between GET and POST');
    assertSameValue($revokedBefore,$f->facts(),'revoked opening writes no facts');
    foreach([1,2]as$role)$f->db->query("INSERT IGNORE INTO {$f->p}fm2_pilot_role_permissions(role_id,permission) VALUES($role,'installation.open')");

    $open=$f->form($renderedAction,[
        '_csrf'=>$f->csrf($checklist['body']),'action'=>'open_confirmed',
        'requestId'=>$rendered['requestId'],'orderId'=>$rendered['orderId'],
        'revisionId'=>$rendered['revisionId'],'sequence'=>$rendered['sequence'],'actualStartDate'=>'2026-09-02',
    ],$engineer);
    assertSameValue([303,'/pilot/construction-control/objects/4512/checklist'],[$open['status'],$open['headers']['location'][0]??null],'opening returns to control checklist');
    $cases=$f->rows('fm2_installation_cases');
    assertSameValue(['working','73'],[$cases[0]['process_state'],$cases[0]['opened_by_user_id']],'engineer owns durable opening');
    assertSameValue(1,count($f->rows('fm2_assignment_order_applications')),'one application');
    assertSameValue(1,count(array_filter($f->rows('fm2_process_events'),static fn(array $r):bool=>$r['event_type']==='installation_opened_from_original')),'one opening event');

    $opened=$f->request('GET','/pilot/construction-control/objects/4512/checklist',[],$engineer);
    assertSameValue(200,$opened['status'],'opened checklist');
    assertSameValue(true,str_contains($opened['body'],'data-enabled="true"'),'checklist enabled after opening');
    assertSameValue(false,str_contains($opened['body'],'name="action" value="open_confirmed"'),'repeat opening absent');
    $openedQueue=$f->request('GET','/pilot/construction-control',[],$engineer);
    assertSameValue(1,substr_count($openedQueue['body'],'data-object-id="4512"'),'opened object remains exactly once');
    $f->noLegacy();
    echo "PASS: YII2-CONSTRUCTION-CONTROL-PREOPENING-001 Yii HTTP\n";
} finally {
    if($f instanceof PreopeningFixture)$f->close();
}
