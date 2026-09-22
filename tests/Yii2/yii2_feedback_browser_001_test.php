<?php
declare(strict_types=1);
// FEEDBACK-001 A6/A8/A9: two bounded browser journeys on the feedback HTTP seam.
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/FeedbackFixture.php';
$f=null;$process=null;
try {
 $f=new UserAccessFixture(dirname(__DIR__,2));$build='dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';$buildFile=feedbackBuildFile($f,$build,'browser');$f->start(extraEnvironment:['FMONITOR_RUNTIME_BUILD_ID_FILE'=>$buildFile]);$ordinary=[];$f->login($ordinary,'ordinary.person@shlz.ru');
 $form=$f->request('GET','/pilot/feedback',[],$ordinary);assertSameValue(200,$form['status'],'INTENDED_RED FEEDBACK-001 browser route absent');
 $admin=[];$f->login($admin);
 $input=['url'=>'http://127.0.0.1:'.$f->server['port'],'cookies'=>$ordinary,'adminCookies'=>$admin,'artifacts'=>$f->artifacts,'build'=>$build,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'];
 $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/feedback_browser.mjs'],[0=>['pipe','r'],1=>['file',$f->artifacts.'/feedback-browser.log','a'],2=>['file',$f->artifacts.'/feedback-browser.log','a']],$pipes,$f->root);
 if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser process');fwrite($pipes[0],json_encode($input));fclose($pipes[0]);
 $deadline=microtime(true)+90;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
 if($state['running'])throw new TestFailure('browser deadline');$exit=$state['exitcode'];proc_close($process);$process=null;assertSameValue(0,$exit,'browser: '.file_get_contents($f->artifacts.'/feedback-browser.log'));
 $rows=$f->rows('fm2_feedback');$results=$f->rows('fm2_feedback_results');assertSameValue(2,count($rows),'two real browser submissions');assertSameValue(2,count($results),'two real browser review notes');
 assertSameValue(['9401','9401'],array_column($rows,'actor_user_id'),'browser submit actor');assertSameValue(['9101','9101'],array_column($results,'actor_user_id'),'browser reviewer actor');
 echo 'PASS FEEDBACK-001 browser artifacts '.$f->artifacts."\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($f instanceof UserAccessFixture)$f->close();}
