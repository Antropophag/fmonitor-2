<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$temporary=realpath(sys_get_temp_dir());if(!is_string($temporary))throw new TestFailure('SETUP_FAILURE: canonical temp root');
$root=$temporary.'/fmonitor-session-contention-'.bin2hex(random_bytes(6));$instance='runtime';$id=str_repeat('R',32);$directory=$root.'/sessions/'.$instance;$entered=$root.'/child-entered';
mkdir($directory,0700,true);chmod($root,0700);chmod($root.'/sessions',0700);chmod($directory,0700);
$payload='authenticated-runtime-session';$committed=$directory.'/s-'.$id.'.session';file_put_contents($committed,$payload,LOCK_EX);chmod($committed,0600);
$lockPath=$directory.'/l-'.hash('sha256',$id).'.lock';$lock=fopen($lockPath,'c+b');if(!is_resource($lock))throw new TestFailure('SETUP_FAILURE: native lock open');chmod($lockPath,0600);assertSameValue(true,flock($lock,LOCK_EX),'fixture owns real session lock');
$pipes=[];$worker=proc_open([PHP_BINARY,__DIR__.'/session_contention_worker.php',$root,$instance,$id,$entered],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));if(!is_resource($worker))throw new TestFailure('SETUP_FAILURE: child start');
$remove=function(string$path)use(&$remove):void{if(is_file($path)||is_link($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path)?:[]as$entry)if($entry!=='.'&&$entry!=='..')$remove($path.'/'.$entry);rmdir($path);};
try{
    $deadline=microtime(true)+3;while(!is_file($entered)&&microtime(true)<$deadline)usleep(10000);assertSameValue(true,is_file($entered),'child reached public start boundary');
    usleep(250000);assertSameValue(true,proc_get_status($worker)['running'],'child waits while native session lock is held');
    $freshPayload='authenticated-runtime-session-after-owner-update';file_put_contents($committed,$freshPayload,LOCK_EX);chmod($committed,0600);
    flock($lock,LOCK_UN);
    $completionDeadline=microtime(true)+3;do{$workerStatus=proc_get_status($worker);if(!$workerStatus['running'])break;usleep(10000);}while(microtime(true)<$completionDeadline);
    assertSameValue(false,$workerStatus['running'],'child completes within three seconds after native lock release');
    $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($worker);$worker=null;
    assertSameValue([0,''],[$exit,$stderr],'contended public start child completes cleanly');
    $observed=json_decode(trim($stdout),true,8,JSON_THROW_ON_ERROR);
    assertSameValue(['status'=>'OK','category'=>null,'payload'=>$freshPayload],$observed,'transient native contention retries and reads the fresh payload after lock acquisition');
    assertSameValue($freshPayload,file_get_contents($committed),'contended read preserves the owner-updated committed session bytes');
    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 transient native session contention\n";
}finally{
    @flock($lock,LOCK_UN);fclose($lock);
    if(is_resource($worker)){proc_terminate($worker,9);foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);proc_close($worker);}
    $remove($root);
}
