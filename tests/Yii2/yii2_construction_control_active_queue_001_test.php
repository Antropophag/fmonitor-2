<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001: real Yii HTTP is the public seam.
$css=(string)file_get_contents(dirname(__DIR__,2).'/app/YiiRuntime/Assets/pilot.css');
assertSameValue(true,str_contains($css,'.fm2-check-order-link { color: var(--fm2-primary); text-decoration: none; }'),'INTENDED_RED linked factory number has no default underline');
assertSameValue(true,str_contains($css,'.fm2-check-order-link:hover { color: var(--fm2-primary); text-decoration: underline; }'),'INTENDED_RED linked factory number underlines on hover');
$fixture=null;
$htmlFile=null;
try {
    $fixture=new InspectionFixture(dirname(__DIR__,2));
    $fixture->open();
    $checklist=$fixture->page();
    foreach(['fm2-check-order-link','>CONTROL-4512</a>','href="https://bitrix24public.com/control-4512"','data-total-progress']as$text)assertSameValue(true,str_contains($checklist['body'],$text),'INTENDED_RED linked factory number '.$text);
    foreach(['fm2-control-document-button','Техническая документация</a>','fm2-technical-document-surface','folder-file-open.svg','data-document-count']as$text)assertSameValue(false,str_contains($checklist['body'],$text),'separate checklist document action removed '.$text);
    foreach(['Соседний заказ','control-neighbor']as$text)assertSameValue(false,str_contains($checklist['body'],$text),'byte-exact order excludes '.$text);
    InspectionFixture::result($fixture->send(InspectionFixture::operation(),InspectionFixture::csrf($checklist)),200,'accepted');
    $fixture->queueFixtures();
    $http=$fixture->http;$http->db->query("INSERT IGNORE INTO {$http->p}fm2_pilot_role_permissions(role_id,permission) VALUES(1,'construction_control.read'),(1,'checklist.read')");$global=[];assertSameValue(303,$http->login($global,18)['status'],'FKR global queue fixture login');$fixture->cookies=$global;

    $object=$http->db->query("SELECT * FROM {$http->p}fm_maintable WHERE id=4513")->fetch_assoc();
    $case=$http->rows('fm2_installation_cases')[0];
    $excludedObject=$object;$excludedObject['id']=4516;$excludedObject['regnumber']='PTO-ONLY';
    $http->insert($http->p.'fm_maintable',$excludedObject);
    $excludedCase=$case;$excludedCase['id']=6105;$excludedCase['legacy_installation_object_id']=4516;
    $http->insert($http->p.'fm2_installation_cases',$excludedCase);
    for($i=0;$i<49;$i++){
        $copy=$object;$copy['id']=5000+$i;$copy['regnumber']='ACTIVE-'.$i;
        $http->insert($http->p.'fm_maintable',$copy);
        $copyCase=$case;$copyCase['id']=7000+$i;$copyCase['legacy_installation_object_id']=5000+$i;
        $http->insert($http->p.'fm2_installation_cases',$copyCase);
    }
    // Independent examples: 4512 has activity, 4513 has none, 4516 starts
    // documentary closeout, 4514 has both facts, and 4515 is not working.
    $http->insert($http->p.'fm2_pilot_completion_facts',[
        'installation_case_id'=>6105,
        'fact_type'=>'pto_act',
        'fact_date'=>'2026-09-10',
        'details'=>'',
        'recorded_at'=>'2026-09-10T09:00:00+03:00',
        'recorded_by_user_id'=>18,
    ]);
    $before=$http->facts();
    $first=$fixture->page('/pilot/construction-control?ownership=all&completed=1');
    $second=$fixture->page('/pilot/construction-control?ownership=all&completed=1&page=2');
    assertSameValue([200,200],[$first['status'],$second['status']],'active queue page boundaries');
    preg_match_all('/data-object-id="(\d+)"/',$first['body'],$firstMatches);
    preg_match_all('/data-object-id="(\d+)"/',$second['body'],$secondMatches);
    $ids=array_map('intval',array_merge($firstMatches[1],$secondMatches[1]));sort($ids);
    assertSameValue(false,in_array(4516,$ids,true),'INTENDED_RED PTO-only documentary case is absent');
    assertSameValue(52,count($ids),'50 plus 2 eligible rows including completed case');
    assertSameValue(true,in_array(4512,$ids,true),'working case with activity remains');
    assertSameValue(true,str_contains($first['body'].$second['body'],'data-has-document="true"')&&str_contains($first['body'].$second['body'],'folder-file-open.svg'),'INTENDED_RED queue exposes document indicator');
    assertSameValue(false,str_contains($first['body'].$second['body'],'https://bitrix24public.com/control-4512'),'queue indicator is noninteractive and exposes no URL');
    foreach(['data-document-count','Документация · 1']as$text)assertSameValue(false,str_contains($first['body'].$second['body'],$text),'queue count removed '.$text);
    $http->db->query("INSERT INTO {$http->p}fm2_bitrix_order_document_links(source_folder_id,source_folder_name,order_number,url) VALUES(94514,'Другой комплект','CONTROL-4512','https://bitrix24public.com/control-ambiguous')");
    try{$ambiguousQueue=$fixture->page('/pilot/construction-control?ownership=all&completed=1&page=2');$ambiguousChecklist=$fixture->page();assertSameValue(true,str_contains($ambiguousQueue['body'],'data-object-id="4512"'),'ambiguity queue contains target object');foreach([$ambiguousQueue['body'],$ambiguousChecklist['body']]as$body){assertSameValue(false,str_contains($body,'control-4512')||str_contains($body,'control-ambiguous'),'ambiguous order publishes no arbitrary action');}}
    finally{$http->db->query("DELETE FROM {$http->p}fm2_bitrix_order_document_links WHERE source_folder_id=94514");}
    assertSameValue(true,in_array(4513,$ids,true),'working case without activity remains');
    assertSameValue(true,in_array(4514,$ids,true),'completed documentary case remains available to client filter');
    assertSameValue(false,in_array(4515,$ids,true),'non-working case is absent');
    assertSameValue([4512,4514],array_map('intval',$secondMatches[1]),'activity ordering leaves exact tail rows including completed case');
    assertSameValue(true,str_contains($second['body'],'data-object-id="4514" data-engineer-id="73" data-completed="true"'),'completed row carries native filter marker');
    foreach([$first,$second]as$page)assertSameValue(true,str_contains($page['body'],PHP_EOL.'52 объектов</span>'),'queue total includes completed rows');
    assertSameValue(false,str_contains($second['body'],'data-object-id="4514" data-engineer-id="73" data-completed="true" hidden'),'server-included completed row is rendered, not hidden by the current page');
    assertSameValue(true,str_contains($second['body'],'name="completed" value="1" data-show-completed checked'),'server completed filter is reflected in checked control');
    assertSameValue(true,str_contains($first['body'],'class="shlz-pagination"')&&str_contains($first['body'],'aria-label="Страницы стройконтроля"')&&str_contains($first['body'],'<ul class="shlz-pagination__list">'),'INTENDED_RED shared construction-control pagination');
    $http->db->query("RENAME TABLE {$http->p}fm2_bitrix_order_document_links TO {$http->p}fm2_bitrix_order_document_links_unavailable");
    try{$unavailableQueue=$fixture->page('/pilot/construction-control?ownership=all&completed=1');$unavailableChecklist=$fixture->page();assertSameValue([200,200,false,false],[$unavailableQueue['status'],$unavailableChecklist['status'],str_contains($unavailableQueue['body'],'folder-file-open.svg'),str_contains($unavailableChecklist['body'],'fm2-control-document-button')],'document outage removes compact action and is fail-soft');}
    finally{$http->db->query("RENAME TABLE {$http->p}fm2_bitrix_order_document_links_unavailable TO {$http->p}fm2_bitrix_order_document_links");}
    $repeat=$fixture->page('/pilot/construction-control?ownership=all&completed=1');
    preg_match_all('/data-object-id="(\d+)"/',$repeat['body'],$repeatMatches);
    assertSameValue($firstMatches[1],$repeatMatches[1],'identical GET repeats row composition');
    assertSameValue(true,str_contains($repeat['body'],PHP_EOL.'52 объектов</span>'),'identical GET repeats total');
    assertSameValue(true,str_contains($repeat['body'],'ownership=all')&&str_contains($repeat['body'],'completed=1')&&str_contains($repeat['body'],'page=2'),'identical GET repeats filtered page boundary');
    assertSameValue($before,$http->facts(),'repeated queue GET preserves all facts');
    $head=$http->request('HEAD','/pilot/construction-control?ownership=all&completed=1&page=2',[],$fixture->cookies);
    assertSameValue([200,''],[$head['status'],$head['body']],'HEAD preserves valid page without body');
    $guest=[];$guestResult=$http->request('GET','/pilot/construction-control',[],$guest);
    assertSameValue([303,'/pilot/login'],[$guestResult['status'],$guestResult['headers']['location'][0]??null],'guest redirect remains');
    assertSameValue($before,$http->facts(),'HEAD and guest read preserve all facts');

    $http->insert($http->p.'fm2_pilot_completion_facts',[
        'installation_case_id'=>6101,
        'fact_type'=>'pto_act',
        'fact_date'=>'2026-09-11',
        'details'=>'',
        'recorded_at'=>'2026-09-11T08:00:00+03:00',
        'recorded_by_user_id'=>18,
    ]);
    $transitioned=$http->facts();
    $afterTransition=$fixture->page('/pilot/construction-control?ownership=all&completed=1');
    assertSameValue(200,$afterTransition['status'],'active queue after transition');
    assertSameValue(false,str_contains($afterTransition['body'],'data-object-id="4512"'),'transitioned case disappears after refresh');
    assertSameValue(50,preg_match_all('/data-control-row\b/',$afterTransition['body']),'filtered first page remains full');
    assertSameValue(true,str_contains($afterTransition['body'],PHP_EOL.'51 объектов</span>'),'PTO-only transition updates completed-inclusive total');
    assertSameValue(200,$fixture->page('/pilot/construction-control?ownership=all&completed=1&page=2')['status'],'completed-inclusive tail page remains');
    assertSameValue($transitioned,$http->facts(),'refresh preserves cases and append-only history');

    $reader=[];
    assertSameValue(303,$http->login($reader,95)['status'],'reader login');
    $http->db->query("DELETE FROM {$http->p}fm2_pilot_role_permissions WHERE role_id=5 AND permission='checklist.read'");
    $deniedBefore=$http->facts();
    assertSameValue(403,$http->request('GET','/pilot/construction-control',[],$reader)['status'],'exact queue permission remains required');
    $deniedChecklist=$http->request('GET','/pilot/construction-control/objects/4512/checklist',[],$reader);assertSameValue(403,$deniedChecklist['status'],'checklist permission remains required');assertSameValue(false,str_contains($deniedChecklist['body'],'control-4512'),'denied checklist discloses no link');
    assertSameValue($deniedBefore,$http->facts(),'denied read writes no facts');
    $fixture->http->noLegacy();
    echo "PASS: YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001 Yii HTTP\n";
} finally {
    if(is_string($htmlFile)&&is_file($htmlFile))unlink($htmlFile);
    if($fixture instanceof InspectionFixture)$fixture->close();
}
