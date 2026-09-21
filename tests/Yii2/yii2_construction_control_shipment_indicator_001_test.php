<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001: real Yii HTTP queue is the public read seam.
$fixture=null;
$htmlFile=null;
$failures=[];
$check=static function(mixed$expected,mixed$actual,string$label)use(&$failures):void{if($expected!==$actual)$failures[]=$label.' expected '.json_encode($expected,JSON_UNESCAPED_UNICODE).' actual '.json_encode($actual,JSON_UNESCAPED_UNICODE);};
try {
    $fixture=new InspectionFixture(dirname(__DIR__,2));
    $fixture->open();
    $fixture->queueFixtures();
    $http=$fixture->http;
    $p=$http->p;
    $rows=[
        [4512,'2026-09-09','2026-09-10',null],
        [4513,'2026-09-09','2026-09-10','2026-09-12'],
        [4514,null,null,'2026-09-13'],
    ];
    foreach($rows as[$id,$ready,$first,$full]){
        $statement=$http->db->prepare("INSERT INTO {$p}fm2_equipment_fact_current(object_id,readiness_date,first_shipment_date,full_shipment_date,source,source_order_hmac,last_successful_run_id,last_successful_observed_at) VALUES(?,?,?,?,'1c_erp',REPEAT('a',64),'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','2026-09-15 09:00:00')");
        $statement->bind_param('isss',$id,$ready,$first,$full);
        $statement->execute();
    }
    $before=$http->facts();
    $page=$fixture->page('/pilot/construction-control');
    $check(200,$page['status'],'queue available');
    $row=static function(string$html,int$id):string{
        $start=strpos($html,'data-object-id="'.$id.'"');
        if($start===false)return'';
        $end=strpos($html,'</tr>',$start);
        return$end===false?'':substr($html,$start,$end-$start);
    };
    $first=$row($page['body'],4512);
    foreach(['data-shipment-state="first"','Первое грузоместо отгружено','10.09.2026','delivery-box.svg','<details']as$needle)$check(true,str_contains($first,$needle),'first shipment '.$needle);
    $check(false,str_contains($first,'Заказ отгружен полностью'),'first does not claim full');
    $full=$row($page['body'],4513);
    foreach(['data-shipment-state="full"','Заказ отгружен полностью','12.09.2026','delivery-4.svg','<details']as$needle)$check(true,str_contains($full,$needle),'full shipment '.$needle);
    $check(false,str_contains($full,'10.09.2026'),'full date has priority over first');
    $fullWithoutFirst=$row($page['body'],4514);
    foreach(['data-shipment-state="full"','Заказ отгружен полностью','13.09.2026']as$needle)$check(true,str_contains($fullWithoutFirst,$needle),'full without first '.$needle);
    foreach([
        'delivery-box.svg'=>'b4517454d78cb79f5063022a65c7d685de5035b1baa204bc442fc181eb5afa04',
        'delivery-4.svg'=>'e79f74ceb6b1b7c596a22c1be3d9072005203f3681181fa193d2f56a8ab81583',
    ]as$asset=>$sha){$path=dirname(__DIR__,2).'/app/YiiRuntime/Assets/shlz-icons/'.$asset;$check($sha,is_file($path)?hash_file('sha256',$path):null,'exact public shlz-ui asset '.$asset);}
    $htmlFile=sys_get_temp_dir().'/fm2-shipment-indicator-'.bin2hex(random_bytes(6)).'.html';
    file_put_contents($htmlFile,$page['body'],LOCK_EX);
    $pipes=[];$process=proc_open(['node',dirname(__DIR__).'/Support/construction_control_shipment_indicator_browser.cjs',$htmlFile,dirname(__DIR__,2).'/app/YiiRuntime/Assets/pilot.css',dirname(__DIR__,2).'/app/YiiRuntime/Assets/control-queue.js'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if(!is_resource($process))throw new RuntimeException('browser start');
    $browserOut=stream_get_contents($pipes[1]);$browserErr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    $browserExit=proc_close($process);$check([0,''],[$browserExit,$browserErr],'shipment indicator browser acceptance');
    $browser=$browserExit===0?json_decode($browserOut,true,16,JSON_THROW_ON_ERROR):[];
    $check(['first'=>'Первое грузоместо отгружено','full'=>'Заказ отгружен полностью'],$browser['accessibleNames']??null,'distinct accessible names');
    $check([true,true,true,true],[$browser['keyboardOpened']??null,$browser['pointerOpened']??null,$browser['desktopCompact']??null,$browser['mobileContained']??null],'keyboard pointer desktop compactness and opened mobile containment');
    $check(true,isset($browser['firstExpandedText'])&&str_contains($browser['firstExpandedText'],'Первое грузоместо отгружено')&&str_contains($browser['firstExpandedText'],'10.09.2026'),'expanded first disclosure binds title and date');
    $check(true,isset($browser['fullExpandedText'])&&str_contains($browser['fullExpandedText'],'Заказ отгружен полностью')&&str_contains($browser['fullExpandedText'],'12.09.2026'),'expanded full disclosure binds title and date');
    $check([0,0],[$browser['navigationAfterKeyboard']??null,$browser['navigationAfterPointer']??null],'disclosure does not trigger row navigation');
    $check(1,$browser['navigationAfterRow']??null,'explicit row link remains navigable');

    $http->db->query("UPDATE {$p}fm2_equipment_fact_current SET first_shipment_date=NULL,full_shipment_date=NULL WHERE object_id=4512");
    $readinessOnly=$row($fixture->page('/pilot/construction-control')['body'],4512);
    $check(false,str_contains($readinessOnly,'data-shipment-state='),'readiness alone has no positive shipment indicator');
    $check(false,str_contains($readinessOnly,'не отгружено'),'unknown is not a negative fact');
    $http->db->query("UPDATE {$p}fm2_equipment_fact_current SET readiness_date=NULL WHERE object_id=4512");
    $allNull=$row($fixture->page('/pilot/construction-control')['body'],4512);
    $check(false,str_contains($allNull,'data-shipment-state='),'three NULL facts have no positive indicator');
    $http->db->query("DELETE FROM {$p}fm2_equipment_fact_current WHERE object_id=4512");
    $neverSynced=$row($fixture->page('/pilot/construction-control')['body'],4512);
    $check(false,str_contains($neverSynced,'data-shipment-state=')||str_contains($neverSynced,'не отгружено'),'never-synced object has no invented shipment fact');
    $http->db->query("INSERT INTO {$p}fm2_equipment_fact_runs(run_id,command_hash,kind,status,reason,observed_at,receipt_json,created_at) VALUES('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',REPEAT('b',64),'failed','failed','SOURCE_UNAVAILABLE','2026-09-15 10:00:00','{\"status\":\"failed\"}','2026-09-15 10:00:00')");
    $failedBeforeSuccess=$row($fixture->page('/pilot/construction-control')['body'],4512);
    $check(false,str_contains($failedBeforeSuccess,'data-shipment-state=')||str_contains($failedBeforeSuccess,'не отгружено'),'failed-before-success has no invented shipment fact');
    $factsAfterReadiness=$http->facts();
    $repeat=$fixture->page('/pilot/construction-control');
    $check(200,$repeat['status'],'repeat GET');
    $check($factsAfterReadiness,$http->facts(),'queue GET is read only');
    $head=$http->request('HEAD','/pilot/construction-control',[],$fixture->cookies);
    $check([200,''],[$head['status'],$head['body']],'HEAD has no body');
    $check($factsAfterReadiness,$http->facts(),'HEAD is read only');
    $guest=[];
    $check(303,$http->request('GET','/pilot/construction-control',[],$guest)['status'],'guest denied');
    $check($factsAfterReadiness,$http->facts(),'denied read is read only');
    $check(false,$before===$factsAfterReadiness,'test fixture correction is observable before read-only checks');
    $http->db->query("DROP TABLE {$p}fm2_equipment_fact_current");
    $unavailable=$fixture->page('/pilot/construction-control');
    $check(503,$unavailable['status'],'unavailable shipment projection is an explicit runtime failure');
    $check(false,str_contains($unavailable['body'],'отгружен')||str_contains($unavailable['body'],'не отгружено'),'unavailable projection has no shipment claim');
    if($failures!==[]){foreach($failures as$failure)fwrite(STDERR,"INTENDED_RED: {$failure}\n");throw new TestFailure('aggregate shipment indicator acceptance failed: '.count($failures).' findings');}
    echo "PASS: YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001 Yii HTTP\n";
} finally {
    if(is_string($htmlFile)&&is_file($htmlFile))unlink($htmlFile);
    if($fixture instanceof InspectionFixture)$fixture->close();
}
