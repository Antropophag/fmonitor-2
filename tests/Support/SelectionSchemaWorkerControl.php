<?php
declare(strict_types=1);
use FMonitor2\Tests\Support\IdentityRegistryTestDatabase;
// Bounded process mechanics copied from independently approved registry concurrency fixture.
function aossStartWorker(IdentityRegistryTestDatabase $f,string $prefix,string $phase):array
{
    $process=proc_open([PHP_BINARY,__DIR__.'/assignment_order_selection_schema_worker.php',$f->name,$prefix,$phase],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if(!is_resource($process)) { throw new TestFailure('SETUP_FAILURE: selection worker start'); }
    stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
    return ['process'=>$process,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];
}
function aossDrainWorker(array &$w,int $waitMicros=100000):void
{
    $read=[$w['pipes'][1],$w['pipes'][2]];$write=null;$except=null;
    if(stream_select($read,$write,$except,0,$waitMicros)===false) { throw new TestFailure('worker stream select failure'); }
    foreach($read as $stream) {
        $bytes=stream_get_contents($stream);
        if($bytes===false) { throw new TestFailure('worker pipe read failure'); }
        $w[$stream===$w['pipes'][1]?'stdout':'stderr'].=$bytes;
    }
    if($w['status']===null || $w['status']['running']) { $w['status']=proc_get_status($w['process']); }
}
function aossWaitPhase(array &$w,string $phase):void
{
    $deadline=hrtime(true)+5_000_000_000;
    do {
        aossDrainWorker($w);
        if(in_array('PHASE '.$phase,explode("\n",$w['stdout']),true)) { return; }
        if(!$w['status']['running']) { throw new TestFailure('worker exited before phase: '.$w['stdout'].' '.$w['stderr']); }
    } while(hrtime(true)<$deadline);
    throw new TestFailure('SETUP_FAILURE: selection phase deadline');
}
function aossReapWorker(array &$w,float $seconds=30):array
{
    $deadline=hrtime(true)+(int)($seconds*1e9);
    do { aossDrainWorker($w); if(!$w['status']['running']) { break; } } while(hrtime(true)<$deadline);
    if($w['status']['running']) { throw new TestFailure('SETUP_FAILURE: selection completion deadline'); }
    $w['stdout'].=stream_get_contents($w['pipes'][1]);$w['stderr'].=stream_get_contents($w['pipes'][2]);
    foreach($w['pipes'] as $pipe) { fclose($pipe); }
    $close=proc_close($w['process']);$exit=$w['status']['exitcode']>=0?$w['status']['exitcode']:$close;
    $w['reaped']=true;
    return ['exit'=>$exit,'stdout'=>$w['stdout'],'stderr'=>$w['stderr'],'signaled'=>$w['status']['signaled'],'termsig'=>$w['status']['termsig']];
}
function aossCleanupWorker(array &$w):void
{
    if(($w['reaped']??false)===true) { return; }
    $state=proc_get_status($w['process']);
    if($state['running']) {
        proc_terminate($w['process'],15);
        $deadline=hrtime(true)+1_000_000_000;
        do { aossDrainWorker($w,20000); if(!$w['status']['running']) { break; } } while(hrtime(true)<$deadline);
        if($w['status']['running']) { proc_terminate($w['process'],9); }
    }
    aossReapWorker($w,1);
}
