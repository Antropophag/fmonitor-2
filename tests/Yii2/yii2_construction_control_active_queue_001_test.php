<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001: real Yii HTTP is the public seam.
$fixture=null;
try {
    $fixture=new InspectionFixture(dirname(__DIR__,2));
    $fixture->open();
    $checklist=$fixture->page();
    InspectionFixture::result($fixture->send(InspectionFixture::operation(),InspectionFixture::csrf($checklist)),200,'accepted');
    $fixture->queueFixtures();
    $http=$fixture->http;

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
    $first=$fixture->page('/pilot/construction-control');
    $second=$fixture->page('/pilot/construction-control?page=2');
    assertSameValue([200,200],[$first['status'],$second['status']],'active queue page boundaries');
    preg_match_all('/data-object-id="(\d+)"/',$first['body'],$firstMatches);
    preg_match_all('/data-object-id="(\d+)"/',$second['body'],$secondMatches);
    $ids=array_map('intval',array_merge($firstMatches[1],$secondMatches[1]));sort($ids);
    assertSameValue(false,in_array(4516,$ids,true),'INTENDED_RED PTO-only documentary case is absent');
    assertSameValue(51,count($ids),'50 plus 1 eligible rows');
    assertSameValue(true,in_array(4512,$ids,true),'working case with activity remains');
    assertSameValue(true,in_array(4513,$ids,true),'working case without activity remains');
    assertSameValue(false,in_array(4514,$ids,true),'completed documentary case is absent');
    assertSameValue(false,in_array(4515,$ids,true),'non-working case is absent');
    assertSameValue([4512],array_map('intval',$secondMatches[1]),'activity ordering leaves exact tail row');
    foreach([$first,$second]as$page)assertSameValue(true,str_contains($page['body'],PHP_EOL.'51 объектов</span>'),'filtered total is rendered');
    $repeat=$fixture->page('/pilot/construction-control');
    preg_match_all('/data-object-id="(\d+)"/',$repeat['body'],$repeatMatches);
    assertSameValue($firstMatches[1],$repeatMatches[1],'identical GET repeats row composition');
    assertSameValue(true,str_contains($repeat['body'],PHP_EOL.'51 объектов</span>'),'identical GET repeats total');
    assertSameValue(true,str_contains($repeat['body'],'href="/pilot/construction-control?page=2"'),'identical GET repeats page boundary');
    assertSameValue($before,$http->facts(),'repeated queue GET preserves all facts');
    $head=$http->request('HEAD','/pilot/construction-control?page=2',[],$fixture->cookies);
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
    $afterTransition=$fixture->page('/pilot/construction-control');
    assertSameValue(200,$afterTransition['status'],'active queue after transition');
    assertSameValue(false,str_contains($afterTransition['body'],'data-object-id="4512"'),'transitioned case disappears after refresh');
    assertSameValue(50,preg_match_all('/data-control-row\b/',$afterTransition['body']),'filtered first page remains full');
    assertSameValue(true,str_contains($afterTransition['body'],PHP_EOL.'50 объектов</span>'),'transition updates filtered total');
    assertSameValue(503,$fixture->page('/pilot/construction-control?page=2')['status'],'no stale empty tail page after transition');
    assertSameValue($transitioned,$http->facts(),'refresh preserves cases and append-only history');

    $reader=[];
    assertSameValue(303,$http->login($reader,95)['status'],'reader login');
    $deniedBefore=$http->facts();
    assertSameValue(403,$http->request('GET','/pilot/construction-control',[],$reader)['status'],'exact queue permission remains required');
    assertSameValue($deniedBefore,$http->facts(),'denied read writes no facts');
    $fixture->http->noLegacy();
    echo "PASS: YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001 Yii HTTP\n";
} finally {
    if($fixture instanceof InspectionFixture)$fixture->close();
}
