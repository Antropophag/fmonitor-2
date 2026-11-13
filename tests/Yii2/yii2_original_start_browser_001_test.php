<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
// ACTUAL-START-FROM-ORDER-001 A7/A9: native HTTP upload, lost response, real controls, independent DB assertions.
$f=null;$process=null;
try{
    $f=new PreopeningFixture(dirname(__DIR__,2));$f->start();$cookies=[];
    assertSameValue(303,$f->login($cookies)['status'],'real login prerequisite');
    assertSameValue(303,$f->selection($cookies)['status'],'real composition prerequisite');
    $config=$f->artifacts.'/start-browser.json';
    file_put_contents($config,json_encode(['origin'=>'http://127.0.0.1:'.$f->server['port'],'email'=>$f->emails[18],'engineerEmail'=>$f->emails[73],'password'=>$f->password,'pdf'=>base64_encode(FMonitor2\Tests\Support\SelectedOriginalFixture::pdf()),'artifacts'=>$f->artifacts,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'],JSON_THROW_ON_ERROR));chmod($config,0600);
    $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/original_start_browser.mjs',$config],[0=>['file','/dev/null','r'],1=>['file',$f->artifacts.'/start-browser.log','a'],2=>['file',$f->artifacts.'/start-browser.log','a']],$pipes,$f->root);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');$deadline=microtime(true)+120;
    do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
    if($state['running'])throw new TestFailure('browser timeout '.$f->artifacts);$exit=$state['exitcode'];proc_close($process);$process=null;
    assertSameValue(0,$exit,'actual-start browser: '.file_get_contents($f->artifacts.'/start-browser.log'));
    $case=$f->rows('fm2_installation_cases')[0];assertSameValue(['working','2030-10-09','18'],[$case['process_state'],$case['actual_start_date'],$case['opened_by_user_id']],'future appendix date persisted and opened immediately');
    assertSameValue(1,count($f->rows('fm2_assignment_order_original_revisions')),'lost response retry creates one original');
    assertSameValue(1,count($f->rows('fm2_assignment_order_applications')),'lost response retry creates one application');
    $events=array_filter($f->rows('fm2_process_events'),static fn($r)=>$r['event_type']==='installation_opened_from_original');assertSameValue(1,count($events),'lost response retry creates one opening');
    // Legacy POST cannot inject another start date after the UI disappeared.
    $r=$f->form('/pilot/objects/4512/execution',['_csrf'=>$f->token($cookies),'action'=>'open_confirmed','requestId'=>'15151515-1515-4515-8515-000000000001','orderId'=>'81','revisionId'=>'invalid-revision','sequence'=>'1','actualStartDate'=>'2026-09-03'],$cookies);
    assertSameValue(true,$r['status']>=400&&$r['status']<500,'manual HTTP opening path closed');
    assertSameValue('2030-10-09',$f->rows('fm2_installation_cases')[0]['actual_start_date'],'manual HTTP input cannot alter date');
    $f->noLegacy();echo 'PASS ACTUAL-START-FROM-ORDER-001 browser; artifacts '.$f->artifacts."\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($f instanceof PreopeningFixture)$f->close();}
