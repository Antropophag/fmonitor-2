<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/ObjectQueueFixture.php';
// YII2-OBJECT-QUEUE-001: simultaneous public owner calls from independent DB connections.
function queueRace(UserAccessFixture $f,array $operations):array
{
 $dir=$f->artifacts.'/race-'.bin2hex(random_bytes(4));mkdir($dir,0700);$e=$f->environment();$config=['connection'=>['dsn'=>'mysql:host='.$e['FMONITOR_DB_HOST'].';port='.$e['FMONITOR_DB_PORT'].';dbname='.$e['FMONITOR_DB_NAME'],'username'=>$f->dmlUser,'password'=>$f->dmlPassword,'charset'=>'utf8mb4'],'prefix'=>$f->p,'directory'=>$dir,'operations'=>$operations];$path=$dir.'/config.json';file_put_contents($path,json_encode($config,JSON_THROW_ON_ERROR));chmod($path,0600);$processes=[];
 try{
 foreach($operations as$i=>$op){$process=proc_open([PHP_BINARY,__DIR__.'/inspection_planning_worker.php',$path,(string)$i],[0=>['file','/dev/null','r'],1=>['file',$dir.'/worker-'.$i.'.log','a'],2=>['file',$dir.'/worker-'.$i.'.log','a']],$pipes,$f->root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: worker');$processes[$i]=$process;}
 $deadline=microtime(true)+15;while(count(glob($dir.'/ready-*'))!==count($operations)){if(microtime(true)>$deadline)throw new TestFailure('SETUP_FAILURE: ready barrier '.$dir);usleep(10000);}file_put_contents($dir.'/go','go');
 $results=[];foreach($processes as$i=>$process){$deadline=microtime(true)+20;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(10000);}while(microtime(true)<$deadline);if($state['running'])throw new TestFailure('worker timeout '.$dir);assertSameValue(0,$state['exitcode'],'worker result '.file_get_contents($dir.'/worker-'.$i.'.log'));$results[]=json_decode(file_get_contents($dir.'/result-'.$i.'.json'),true,flags:JSON_THROW_ON_ERROR);proc_close($process);unset($processes[$i]);}return$results;
 }finally{foreach($processes as$process){proc_terminate($process,9);proc_close($process);}unlink($path);}
}

$f=null;
try {
    $f=new ObjectQueueFixture(dirname(__DIR__,2));$f->planning();
    $operations=array_fill(0,4,[9101,451201,'2026-09-12']);
    $results=queueRace($f->http,$operations);
    assertSameValue(['scheduled','scheduled','scheduled','scheduled'],array_column($results,'status'),'all exact duplicates succeed');
    $ids=array_unique(array_column($results,'scheduleId'));assertSameValue(1,count($ids),'all observe same identity');
    $rows=$f->rows('fm2_pilot_inspection_schedules');$events=$f->rows('fm2_pilot_inspection_schedule_events');
    assertSameValue([1,1],[count($rows),count($events)],'one atomic schedule/event despite concurrency');
    assertSameValue((int)$rows[0]['id'],(int)$ids[0],'result identity persisted');
    assertSameValue(['6101','7301','9101','2026-09-12','2026-09-10T09:30:00+03:00'],[$rows[0]['installation_case_id'],$rows[0]['control_engineer_user_id'],$rows[0]['scheduled_by_user_id'],$rows[0]['inspection_date'],$rows[0]['scheduled_at']],'concurrent literal facts');
    assertSameValue($rows[0]['id'],$events[0]['schedule_id'],'event references winner');
    $before=$f->facts();$results=queueRace($f->http,$operations);assertSameValue($before,$f->facts(),'concurrent existing replay preserves every fact');
    $results=queueRace($f->http,[[9101,451201,'2026-09-13'],[9101,451201,'2026-09-14']]);
    assertSameValue(['scheduled','scheduled'],array_column($results,'status'),'distinct dates both succeed');
    assertSameValue([3,3],[count($f->rows('fm2_pilot_inspection_schedules')),count($f->rows('fm2_pilot_inspection_schedule_events'))],'each unique intention audited once');
    echo "PASS: YII2-OBJECT-QUEUE-001 concurrent schedule and replay\n";
}finally{if($f instanceof ObjectQueueFixture)$f->close();}
