<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

// YII2-CONSTRUCTION-CONTROL-PREOPENING-001: real Yii HTTP is the public seam.
$f=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));
    foreach(['construction_control.read','checklist.read','installation.open']as$permission)$f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>2,'permission'=>$permission]);
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_user_roles WHERE user_id=95");
    $f->insert($f->p.'fm2_pilot_user_roles',['user_id'=>95,'role_id'=>2,'origin'=>'fixture','assigned_at'=>'2026-09-10T09:00:00+03:00']);
    $f->start();
    $fkr=[];assertSameValue(303,$f->login($fkr,18)['status'],'FKR login');
    assertSameValue(303,$f->selection($fkr)['status'],'selected assigned engineer');
    $assigned=[];assertSameValue(303,$f->login($assigned,73)['status'],'assigned engineer login');
    $substitute=[];assertSameValue(303,$f->login($substitute,95)['status'],'substitute engineer login');
    $selectedOnly=$f->facts();$notReady=$f->request('GET','/pilot/construction-control',[],$assigned);
    assertSameValue(200,$notReady['status'],'selected-only queue');
    assertSameValue(false,str_contains($notReady['body'],'data-object-id="4512"'),'selection without original absent');
    assertSameValue($selectedOnly,$f->facts(),'selected-only read-only');
    $receipt=$f->upload($fkr,$f->metadata($fkr));assertSameValue(201,$receipt['status'],'accepted original');
    $original=json_decode($receipt['body'],true,flags:JSON_THROW_ON_ERROR);
    $before=$f->facts();$queue=$f->request('GET','/pilot/construction-control',[],$assigned);
    assertSameValue(200,$queue['status'],'ready queue');
    assertSameValue(1,substr_count($queue['body'],'data-object-id="4512"'),'INTENDED_RED ready object appears once');
    assertSameValue(true,str_contains($queue['body'],'data-engineer-id="73"'),'assigned engineer drives Mine projection');
    assertSameValue(true,str_contains($queue['body'],'Готов к открытию'),'ready status');
    assertSameValue($before,$f->facts(),'queue read-only');
    $queueHead=$f->request('HEAD','/pilot/construction-control',[],$assigned);
    assertSameValue([200,''],[$queueHead['status'],$queueHead['body']],'ready queue HEAD');
    assertSameValue($before,$f->facts(),'ready queue HEAD read-only');
    $f->insert($f->p.'fm2_pilot_completion_facts',['installation_case_id'=>6101,'fact_type'=>'pto_act','fact_date'=>'2026-09-01','details'=>'','recorded_at'=>'2026-09-01T12:00:00+03:00','recorded_by_user_id'=>18]);
    $pto=$f->facts();$ptoQueue=$f->request('GET','/pilot/construction-control',[],$assigned);
    assertSameValue(false,str_contains($ptoQueue['body'],'data-object-id="4512"'),'ready case with PTO absent');assertSameValue($pto,$f->facts(),'PTO queue read-only');
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_completion_facts WHERE installation_case_id=6101 AND fact_type='pto_act'");
    $before=$f->facts();
    $checklist=$f->request('GET','/pilot/construction-control/objects/4512/checklist',[],$substitute);
    assertSameValue(200,$checklist['status'],'authorized substitute reads ready checklist');
    foreach(['Готов к открытию','name="action" value="open_confirmed"','name="actualStartDate"','>Открыть работы</button>','data-enabled="false"']as$marker)assertSameValue(true,str_contains($checklist['body'],$marker),'ready checklist '.$marker);
    $checkHead=$f->request('HEAD','/pilot/construction-control/objects/4512/checklist',[],$substitute);assertSameValue([200,''],[$checkHead['status'],$checkHead['body']],'ready checklist HEAD');
    $operation=json_encode(['clientOperationId'=>'aaaaaaaa-aaaa-4aaa-8aaa-000000000040','deviceInstallationId'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb','type'=>'item_completed','deviceTime'=>'2026-09-02T10:00:00+03:00','baseRevision'=>0,'sectionId'=>1,'itemId'=>28,'installerTabIds'=>[7001]],JSON_THROW_ON_ERROR);
    $locked=$f->request('POST','/pilot/construction-control/objects/4512/checklist/operations',[],$substitute,['Content-Type: application/json; charset=UTF-8','X-FM2-CSRF: '.$f->csrf($checklist['body']),'Content-Length: '.strlen($operation)],$operation);
    assertSameValue(403,$locked['status'],'preopening mutation locked');assertSameValue(403,$f->request('GET','/pilot/construction-control/objects/4512/sync-context',[],$substitute)['status'],'preopening sync locked');assertSameValue($before,$f->facts(),'ready checklist reads/denials no facts');
    preg_match_all('/<form\b[^>]*>.*?<\/form>/s',$checklist['body'],$forms);
    $opening=array_values(array_filter($forms[0],static fn(string$form):bool=>str_contains($form,'name="action" value="open_confirmed"')));
    assertSameValue(1,count($opening),'one opening form');
    assertSameValue(1,preg_match('/<form\b(?=[^>]*method="post")(?=[^>]*action="([^"]+)")[^>]*>/',$opening[0],$action),'opening action');
    $path=html_entity_decode($action[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
    assertSameValue('/pilot/objects/4512/execution?return=construction-control',$path,'exact construction-control return action');
    $intent=[];foreach(['requestId','orderId','revisionId','sequence']as$name){assertSameValue(1,preg_match('/name="'.$name.'" value="([^"]+)"/',$opening[0],$m),'opening '.$name);$intent[$name]=html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');}
    assertSameValue(['81',$original['currentRevisionId'],'0'],[$intent['orderId'],$intent['revisionId'],$intent['sequence']],'current opening intent');
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE role_id=2 AND permission='installation.open'");
    $deniedBefore=$f->facts();$denied=$f->form($path,['_csrf'=>$f->csrf($checklist['body']),'action'=>'open_confirmed','requestId'=>$intent['requestId'],'orderId'=>$intent['orderId'],'revisionId'=>$intent['revisionId'],'sequence'=>$intent['sequence'],'actualStartDate'=>'2026-09-02'],$substitute);
    assertSameValue(403,$denied['status'],'opening requires current exact permission');assertSameValue($deniedBefore,$f->facts(),'denied opening no facts');
    $f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>2,'permission'=>'installation.open']);
    $opened=$f->form($path,['_csrf'=>$f->csrf($checklist['body']),'action'=>'open_confirmed','requestId'=>$intent['requestId'],'orderId'=>$intent['orderId'],'revisionId'=>$intent['revisionId'],'sequence'=>$intent['sequence'],'actualStartDate'=>'2026-09-02'],$substitute);
    assertSameValue([303,'/pilot/construction-control/objects/4512/checklist'],[$opened['status'],$opened['headers']['location'][0]??null],'substitute opening returns to checklist');
    $case=$f->rows('fm2_installation_cases')[0];assertSameValue(['working','95'],[$case['process_state'],$case['opened_by_user_id']],'substitute owns opening fact');
    assertSameValue(1,count($f->rows('fm2_assignment_order_applications')),'one application');
    $after=$f->request('GET','/pilot/construction-control/objects/4512/checklist',[],$substitute);
    assertSameValue(true,str_contains($after['body'],'data-enabled="true"'),'checklist enabled');
    assertSameValue(false,str_contains($after['body'],'name="action" value="open_confirmed"'),'repeat opening absent');
    assertSameValue(1,substr_count($f->request('GET','/pilot/construction-control',[],$assigned)['body'],'data-object-id="4512"'),'opened object remains in queue');
    $f->noLegacy();
    echo "PASS: YII2-CONSTRUCTION-CONTROL-PREOPENING-001 Yii HTTP\n";
} finally {if($f instanceof PreopeningFixture)$f->close();}
