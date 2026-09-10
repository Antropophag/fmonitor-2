<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';
// YII2-PREOPENING-JOURNEY-001: real MariaDB commits; network loses only its acknowledgement.
$f=null;$proxy=null;$pipes=[];
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));
    assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'native selection');
    $original=$f->nativeOriginal();assertSameValue('accepted',$original->status()->value,'native original');
    $transcript=$f->artifacts.'/commit-transcript';
    $env=array_replace(getenv(),['FMONITOR_TEST_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1','FMONITOR_TEST_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','PREOPENING_COMMIT_TRANSCRIPT'=>$transcript]);
    $proxy=proc_open([PHP_BINARY,__DIR__.'/preopening_commit_proxy.php'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['file',$f->artifacts.'/proxy.log','a']],$pipes,$f->root,$env);
    if(!is_resource($proxy))throw new TestFailure('SETUP_FAILURE proxy');stream_set_timeout($pipes[1],5);$port=trim((string)fgets($pipes[1]));assertSameValue(true,ctype_digit($port),'proxy listener');
    $f->start(['FMONITOR_DB_HOST'=>'127.0.0.1','FMONITOR_DB_PORT'=>$port]);$cookies=[];$f->login($cookies);
    $card=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue(200,$card['status'],'INTENDED_RED Yii opening through real wire proxy');
    assertSameValue(false,file_exists($transcript),'read/login transactions never consume opening fault');
    $open=['_csrf'=>$f->csrf($card['body']),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000077','orderId'=>'81','revisionId'=>$original->currentRevisionId(),'sequence'=>'0','actualStartDate'=>'2026-09-02'];
    $result=$f->form('/pilot/objects/4512/execution',$open,$cookies);
    assertSameValue("OPENING_COMMIT_ACKNOWLEDGED_RESPONSE_DROPPED\n",file_get_contents($transcript),'database acknowledgement truly lost');
    assertSameValue(503,$result['status'],'uncertain command outcome is unavailable');assertSameValue('60',$result['headers']['retry-after'][0]??null,'safe bounded retry guidance');
    foreach([$f->dmlPassword,'SQLSTATE','Stack trace']as$secret)assertSameValue(false,str_contains($result['body'],$secret),'safe uncertain response');
    assertSameValue(1,count($f->rows('fm2_assignment_order_applications')),'one committed application despite lost acknowledgement');
    $events=array_filter($f->rows('fm2_process_events'),static fn($row)=>$row['event_type']==='installation_opened_from_original');assertSameValue(1,count($events),'one committed opening');
    $facts=$f->facts();$files=$f->base->privateFiles();
    assertSameValue(303,$f->form('/pilot/objects/4512/execution',$open,$cookies)['status'],'same intent resolves by native replay');
    assertSameValue($facts,$f->facts(),'replay after actual commit loss no new facts');assertSameValue($files,$f->base->privateFiles(),'evidence unchanged');$f->noLegacy();
    echo "PASS: YII2-PREOPENING-JOURNEY-001 acknowledged commit loss and exact replay\n";
} finally {
    if($f instanceof PreopeningFixture)$f->close();
    foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);
    if(is_resource($proxy)){proc_terminate($proxy);proc_close($proxy);}
}
