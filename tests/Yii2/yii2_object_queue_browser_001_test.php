<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/ObjectQueueFixture.php';
// YII2-OBJECT-QUEUE-001: real login/filter/dialog submission; independent DB audit.
$f=null;$process=null;$config=null;
try {
 $f=new ObjectQueueFixture(dirname(__DIR__,2));for($i=1;$i<=50;$i++)$f->object(451201+$i,6101+$i);$h=$f->http;$h->start();$config=$h->artifacts.'/browser.json';
 file_put_contents($config,json_encode(['origin'=>'http://127.0.0.1:'.$h->server['port'],'email'=>$h->auth->email,'password'=>$h->auth->password,'artifacts'=>$h->artifacts,'result'=>$h->artifacts.'/result.json','playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'],JSON_THROW_ON_ERROR));chmod($config,0600);
 $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/object_queue_browser.mjs',$config],[0=>['file','/dev/null','r'],1=>['file',$h->artifacts.'/browser.log','a'],2=>['file',$h->artifacts.'/browser.log','a']],$pipes,$f->root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');
 $deadline=microtime(true)+75;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);if($state['running'])throw new TestFailure('browser timeout '.$h->artifacts);
 $exit=$state['exitcode'];proc_close($process);$process=null;assertSameValue(0,$exit,'browser flow '.file_get_contents($h->artifacts.'/browser.log'));
 $result=json_decode(file_get_contents($h->artifacts.'/result.json'),true,flags:JSON_THROW_ON_ERROR);assertSameValue([true,true,true,[]],[$result['scheduled'],$result['desktop'],$result['mobile'],$result['assetFailures']],'complete UI path');
 $rows=$f->rows('fm2_pilot_inspection_schedules');$events=$f->rows('fm2_pilot_inspection_schedule_events');assertSameValue([1,1],[count($rows),count($events)],'browser created one schedule and event');
 assertSameValue(['6101','451201','7301','2099-09-12','9101'],[$rows[0]['installation_case_id'],$rows[0]['legacy_object_id'],$rows[0]['control_engineer_user_id'],$rows[0]['inspection_date'],$rows[0]['scheduled_by_user_id']],'browser persisted exact intended facts');assertSameValue([$rows[0]['id'],$rows[0]['scheduled_at'],'9101'],[$events[0]['schedule_id'],$events[0]['occurred_at'],$events[0]['actor_user_id']],'browser audit identity time actor');$f->noLegacy();
 echo 'PASS: YII2-OBJECT-QUEUE-001 browser; artifacts '.$h->artifacts."\n";
} finally {if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($config!==null&&is_file($config))unlink($config);if($f instanceof ObjectQueueFixture)$f->close();}
