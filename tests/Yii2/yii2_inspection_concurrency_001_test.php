<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/InspectionFixture.php';require __DIR__.'/PreopeningConcurrentRequests.php';
// YII2-INSPECTION-JOURNEY-001 A4: real requests, independent servers/cookies/connections.
$f=null;$second=null;
try {
 $f=new InspectionFixture(dirname(__DIR__,2));$f->open();$h=$f->http;$first=$h->server;
 $page=$f->page();InspectionFixture::projection($page);$csrf=InspectionFixture::csrf($page);$cookies=$f->cookies;
 $h->server=null;$h->start();$second=$h->server;$other=[];$h->login($other,73);$otherPage=$h->request('GET','/pilot/objects/4512/checklist',[],$other);$otherCsrf=InspectionFixture::csrf($otherPage);
 $requests=[];foreach([[$first,$cookies,$csrf],[$second,$other,$otherCsrf]]as$i=>[$server,$jar,$token])$requests[]=['port'=>$server['port'],'path'=>'/pilot/objects/4512/checklist/operations','body'=>json_encode(InspectionFixture::operation($i+1,0,28+$i),JSON_THROW_ON_ERROR),'cookies'=>$jar,'headers'=>['Content-Type: application/json','X-FM2-CSRF: '.$token,'Origin: http://127.0.0.1:'.$server['port'],'Sec-Fetch-Site: same-origin']];
 $results=preopeningConcurrent($requests);$statuses=array_column($results,'status');sort($statuses);assertSameValue([200,409],$statuses,'one accepted one stale');$bodies=array_map(static fn($r)=>json_decode($r['body'],true,flags:JSON_THROW_ON_ERROR),$results);$states=array_column($bodies,'status');sort($states);assertSameValue(['accepted','conflict'],$states,'serialized typed results');
 $rows=$h->rows('fm2_checklist_operations');assertSameValue(1,count($rows),'single winner operation');assertSameValue('1',$rows[0]['accepted_revision'],'winner revision');assertSameValue(2,count($h->rows('fm2_checklist_operation_installers')),'only winner snapshots');
 $before=$h->facts();$winner=$results[0]['status']===200?0:1;$replays=preopeningConcurrent([$requests[$winner],$requests[$winner]]);foreach($replays as$r)InspectionFixture::result($r,200,'duplicate');assertSameValue($before,$h->facts(),'concurrent exact replay no facts');
 $h->noLegacy();echo "PASS: YII2-INSPECTION-JOURNEY-001 concurrent HTTP and replay\n";
}finally{if($second!==null){proc_terminate($second['process']);proc_close($second['process']);if($f!==null)$f->http->server=$first;}if($f instanceof InspectionFixture)$f->close();}
