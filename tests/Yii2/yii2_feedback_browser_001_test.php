<?php
declare(strict_types=1);
// FEEDBACK-001 A6: all named incumbent shells, then two bounded browser journeys.
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/InspectionFixture.php';
$inspection=null;$process=null;
try {
 $inspection=new InspectionFixture(dirname(__DIR__,2));$f=$inspection->http;$f->start();$ordinary=[];$f->login($ordinary,97);
 $form=$f->request('GET','/pilot/feedback',[],$ordinary);assertSameValue(200,$form['status'],'INTENDED_RED FEEDBACK-001 browser route absent');$f->stop();
 $f->insert($f->p.'fm2_pilot_role_permissions',['role_id'=>7,'permission'=>'installers.read']);
 $inspection->open();$ordinary=[];$admin=[];$f->login($ordinary,97);$f->login($admin,94);
 foreach(['/pilot/objects','/pilot/objects/4512','/pilot/objects/4512/assignment-order/selection','/pilot/objects/4512/execution','/pilot/objects/4512/assignment-orders/81/originals/submit','/pilot/objects/4512/assignment-orders/81/originals/history','/pilot/construction-control','/pilot/objects/4512/checklist','/pilot/installers']as$path){
  $r=$f->request('GET',$path,[],$ordinary);assertSameValue(200,$r['status'],'named shell '.$path);assertSameValue(true,str_contains($r['body'],'/pilot/feedback'),'feedback link '.$path);
 }
 foreach(['/pilot/admin/users','/pilot/admin/roles']as$path){$r=$f->request('GET',$path,[],$admin);assertSameValue(200,$r['status'],'admin shell');assertSameValue(true,str_contains($r['body'],'/pilot/feedback'),'admin feedback link');}
 $input=['url'=>'http://127.0.0.1:'.$f->server['port'],'cookies'=>$ordinary,'adminCookies'=>$admin,'artifacts'=>$f->artifacts,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'];
 $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/feedback_browser.mjs'],[0=>['pipe','r'],1=>['file',$f->artifacts.'/feedback-browser.log','a'],2=>['file',$f->artifacts.'/feedback-browser.log','a']],$pipes,$f->root);
 if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser process');fwrite($pipes[0],json_encode($input));fclose($pipes[0]);
 $deadline=microtime(true)+90;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
 if($state['running'])throw new TestFailure('browser deadline');$exit=$state['exitcode'];proc_close($process);$process=null;assertSameValue(0,$exit,'browser: '.file_get_contents($f->artifacts.'/feedback-browser.log'));
 $rows=$f->rows('fm2_feedback');$results=$f->rows('fm2_feedback_results');assertSameValue(2,count($rows),'two real browser submissions');assertSameValue(2,count($results),'two real browser review notes');
 assertSameValue(['97','97'],array_column($rows,'actor_user_id'),'browser submit actor');assertSameValue(['94','94'],array_column($results,'actor_user_id'),'browser reviewer actor');
 echo 'PASS FEEDBACK-001 browser artifacts '.$f->artifacts."\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($inspection instanceof InspectionFixture)$inspection->close();}
