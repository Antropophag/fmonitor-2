<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001: real Yii HTTP + isolated DB.
$fixture=null;
try{
    $fixture=new InspectionFixture(dirname(__DIR__,2));$fixture->open();$fixture->queueFixtures();$http=$fixture->http;
    $object=$http->db->query("SELECT * FROM {$http->p}fm_maintable WHERE id=4513")->fetch_assoc();
    $case=$http->rows('fm2_installation_cases')[0];
    for($i=0;$i<51;$i++){
        $copy=$object;$copy['id']=5000+$i;$copy['ordadr_address']='Тестовый адрес '.$i;$copy['regnumber']=$i===50?'OWN-TAIL':'BULK-'.$i;
        $http->insert($http->p.'fm_maintable',$copy);
        $copyCase=$case;$copyCase['id']=7000+$i;$copyCase['legacy_installation_object_id']=5000+$i;
        $http->insert($http->p.'fm2_installation_cases',$copyCase);
    }
    $before=$http->facts();
    $search=$fixture->page('/pilot/construction-control?ownership=all&query=OWN-TAIL');
    assertSameValue(200,$search['status'],'search request accepted');
    assertSameValue(1,preg_match_all('/data-control-row\b/',$search['body']),'INTENDED_RED search filters before pagination');
    assertSameValue(true,str_contains($search['body'],'data-object-id="5050"'),'tail object found on result page 1');
    assertSameValue(true,str_contains($search['body'],PHP_EOL.'1 объект</span>'),'filtered total matches rows');
    assertSameValue(true,str_contains($search['body'],'name="query"')&&str_contains($search['body'],'value="OWN-TAIL"'),'URL query reflected in form');
    $empty=$fixture->page('/pilot/construction-control?ownership=all&query=NO-SUCH-OBJECT');
    assertSameValue([200,0],[ $empty['status'],preg_match_all('/data-control-row\b/',$empty['body'])],'empty filtered set');
    assertSameValue(true,str_contains($empty['body'],'0 объектов'),'empty total is truthful');
    $completed=$fixture->page('/pilot/construction-control?ownership=all&query=QUEUE-4514&completed=1');
    assertSameValue(true,str_contains($completed['body'],'data-object-id="4514"'),'canonical completed row included by server filter');
    $defaultCompleted=$fixture->page('/pilot/construction-control?ownership=all&query=QUEUE-4514');
    assertSameValue(false,str_contains($defaultCompleted['body'],'data-object-id="4514"'),'canonical completed row excluded by default');
    foreach(['/pilot/construction-control?ownership=bogus','/pilot/construction-control?completed=maybe','/pilot/construction-control?query%5B%5D=x']as$path)
        assertSameValue(404,$fixture->page($path)['status'],'invalid filter controlled '.$path);
    assertSameValue($before,$http->facts(),'all filtered reads preserve facts');
    $source=(string)file_get_contents(dirname(__DIR__,2).'/app/YiiRuntime/Assets/control-queue.js');
    assertSameValue(false,str_contains($source,'row.hidden='),'INTENDED_RED browser no longer filters current DOM page');
    assertSameValue(false,str_contains($source,'count.textContent='),'INTENDED_RED browser preserves server total');
    assertSameValue(true,str_contains($source,'readOperations')&&str_contains($source,'syncLocalChanges')&&str_contains($source,'prefetchChecklists'),'local sync and prefetch retained');
    echo "PASS: YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001\n";
}finally{if($fixture instanceof InspectionFixture)$fixture->close();}
