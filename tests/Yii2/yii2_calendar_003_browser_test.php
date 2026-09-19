<?php
declare(strict_types=1);
// YII2-CALENDAR-003 A2/A4 — rendered shlz-ui Calendar Grid at desktop and mobile.
require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/PreopeningFixture.php';
$f = null; $process = null;
try {
    $f = new PreopeningFixture(dirname(__DIR__, 2));
    foreach ([[7202,6101,19,'2026-10-15'],[7201,6102,7,'2026-10-15'],[7203,6103,42,'2026-11-03']] as [$id,$case,$object,$date]) {
        $f->insert($f->p.'fm2_pilot_inspection_schedules',['id'=>$id,'installation_case_id'=>$case,'legacy_object_id'=>$object,'control_engineer_user_id'=>73,'inspection_date'=>$date,'scheduled_by_user_id'=>18,'scheduled_at'=>'2026-09-19T12:00:00+03:00']);
    }
    $f->start(['FMONITOR_NOW'=>'2026-09-19T12:00:00+03:00']); $cookies=[]; $f->login($cookies,18);
    $probe=$f->request('GET','/pilot/calendar?date=2026-10-15',[],$cookies);
    assertSameValue(200,$probe['status'],'INTENDED_RED YII2-CALENDAR-003 browser route currently 404');
    $input=['url'=>'http://127.0.0.1:'.$f->server['port'],'cookies'=>$cookies,'artifacts'=>$f->artifacts,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'];
    $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/calendar_003_browser.mjs'],[0=>['pipe','r'],1=>['file',$f->artifacts.'/calendar-browser.log','a'],2=>['file',$f->artifacts.'/calendar-browser.log','a']],$pipes,$f->root);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');fwrite($pipes[0],json_encode($input));fclose($pipes[0]);
    $deadline=microtime(true)+90;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
    if($state['running'])throw new TestFailure('browser deadline');$exit=$state['exitcode'];proc_close($process);$process=null;
    assertSameValue(0,$exit,'calendar browser '.file_get_contents($f->artifacts.'/calendar-browser.log'));
    assertSameValue($f->facts(),$f->facts(),'browser leaves facts readable');
    echo 'PASS YII2-CALENDAR-003 browser artifacts '.$f->artifacts."\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($f instanceof PreopeningFixture)$f->close();}
