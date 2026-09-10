<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';require __DIR__.'/PreopeningConcurrentRequests.php';
// YII2-PREOPENING-JOURNEY-001: concurrent real Yii processes, distinct authenticated sessions.
$f=null;$first=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));$f->start();$first=$f->server;$a=[];$f->login($a);$csrfA=$f->token($a);
    $f->start();$second=$f->server;$b=[];$f->login($b);$csrfB=$f->token($b);
    assertSameValue(200,$f->request('GET','/pilot/objects/4512/assignment-order/selection',[],$b)['status'],'INTENDED_RED concurrent Yii selection');
    $servers=[$first,$second];$cookies=[$a,$b];$csrf=[$csrfA,$csrfB];
    $batch=static function(string$path,callable$body,callable$headers)use($servers,$cookies):array{
        $requests=[];foreach([0,1]as$i)$requests[]=['port'=>$servers[$i]['port'],'path'=>$path,'cookies'=>$cookies[$i],'body'=>$body($i),'headers'=>$headers($i)];return preopeningConcurrent($requests);
    };
    $selection=['requestId'=>'11111111-1111-4111-8111-000000000066','mode'=>'new_order','expectedSelectionRevision'=>'0','controlEngineerUserId'=>'73','controlEngineerConfirmed'=>'yes'];
    $results=$batch('/pilot/objects/4512/assignment-order/selection',static fn($i)=>http_build_query(['_csrf'=>$csrf[$i]]+$selection).'&installerTabIds%5B%5D=7001',static fn($i)=>['Content-Type: application/x-www-form-urlencoded']);
    foreach($results as$r)assertSameValue(303,$r['status'],'concurrent same selection succeeds/replays');assertSameValue(1,count($f->rows('fm2_assignment_order_selections')),'one immutable selection');
    $path='/pilot/objects/4512/assignment-orders/81/originals';$metadata=$f->metadata($b,'22222222-2222-4222-8222-000000000066');$pdf=FMonitor2\Tests\Support\SelectedOriginalFixture::pdf();
    foreach(['initial','correction']as$mode){
        if($mode==='correction'){$metadata=array_replace($metadata,['requestId'=>'22222222-2222-4222-8222-000000000067','mode'=>'correction','documentDate'=>'2026-09-02','rootOriginalId'=>$receipt['rootOriginalId'],'targetRevisionId'=>$receipt['currentRevisionId'],'expectedCurrentRevisionId'=>$receipt['currentRevisionId'],'correctionReason'=>'Concurrent correction']);}
        $results=$batch($path,static fn($i)=>$pdf,static function($i)use($metadata,$csrf){$m=array_replace($metadata,['csrfToken'=>$csrf[$i]]);return['Content-Type: application/pdf','X-CSRF-Token: '.$csrf[$i],'X-FMonitor-Original: '.base64_encode(json_encode($m,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))];});
        $statuses=array_column($results,'status');assertSameValue(true,in_array(201,$statuses,true),'one accepted original '.$mode);
        foreach($results as$r){assertSameValue(true,in_array($r['status'],[200,201,503],true),'bounded native concurrency result');if($r['status']===503)assertSameValue(true,str_contains(strtolower($r['headers']),'retry-after: 60'),'concurrent retry guidance');}
        $metadata['csrfToken']=$csrfB;$replayed=$f->upload($b,$metadata,$pdf);assertSameValue(200,$replayed['status'],'same upload resolves through native replay');$receipt=json_decode($replayed['body'],true,flags:JSON_THROW_ON_ERROR);
        assertSameValue($mode==='initial'?1:2,count($f->rows('fm2_assignment_order_original_revisions')),'no duplicate revision from concurrent transport');
    }
    $open=['action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000066','orderId'=>'81','revisionId'=>$receipt['currentRevisionId'],'sequence'=>'0','actualStartDate'=>'2026-09-02'];
    $results=$batch('/pilot/objects/4512/execution',static fn($i)=>http_build_query(['_csrf'=>$csrf[$i]]+$open),static fn($i)=>['Content-Type: application/x-www-form-urlencoded']);
    foreach($results as$r)assertSameValue(303,$r['status'],'concurrent exact opening replay');assertSameValue(1,count($f->rows('fm2_assignment_order_applications')),'one application');
    assertSameValue(1,count(array_filter($f->rows('fm2_process_events'),static fn($r)=>$r['event_type']==='installation_opened_from_original')),'one opening event');$f->noLegacy();
    echo "PASS: YII2-PREOPENING-JOURNEY-001 concurrent selection, original/correction and opening\n";
}finally{if($f instanceof PreopeningFixture)$f->close();if($first!==null){proc_terminate($first['process']);proc_close($first['process']);}}
