<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigrationVerification as Verification;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\Tests\Support\IdentityRegistryTestDatabase;

// ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001; real FMONITOR_TEST_DB connections/processes.
function aoirStartWorker(IdentityRegistryTestDatabase $f,string $prefix,string $phase):array
{
    $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/assignment_order_identity_registry_worker.php',$f->name,$prefix,$phase],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if(!is_resource($process)) { throw new TestFailure('SETUP_FAILURE: registry worker start'); }
    stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
    return ['process'=>$process,'pipes'=>$pipes,'stdout'=>'','stderr'=>'','started'=>hrtime(true),'status'=>null];
}
function aoirDrainWorker(array &$w,int $waitMicros=100000):void
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
function aoirWaitPhase(array &$w,string $phase):void
{
    $deadline=hrtime(true)+5_000_000_000;
    do {
        aoirDrainWorker($w);
        if(in_array('PHASE '.$phase,explode("\n",$w['stdout']),true)) { return; }
        if(!$w['status']['running']) { throw new TestFailure('worker exited before phase: '.$w['stdout'].' '.$w['stderr']); }
    } while(hrtime(true)<$deadline);
    throw new TestFailure('SETUP_FAILURE: registry phase deadline');
}
function aoirReapWorker(array &$w,float $seconds=30):array
{
    $deadline=hrtime(true)+(int)($seconds*1e9);
    do { aoirDrainWorker($w); if(!$w['status']['running']) { break; } } while(hrtime(true)<$deadline);
    if($w['status']['running']) { throw new TestFailure('SETUP_FAILURE: registry completion deadline'); }
    $w['stdout'].=stream_get_contents($w['pipes'][1]);$w['stderr'].=stream_get_contents($w['pipes'][2]);
    foreach($w['pipes'] as $pipe) { fclose($pipe); }
    $close=proc_close($w['process']);$exit=$w['status']['exitcode']>=0?$w['status']['exitcode']:$close;
    $w['reaped']=true;
    return ['exit'=>$exit,'stdout'=>$w['stdout'],'stderr'=>$w['stderr'],'signaled'=>$w['status']['signaled'],'termsig'=>$w['status']['termsig']];
}
function aoirCleanupWorker(array &$w):void
{
    if(($w['reaped']??false)===true) { return; }
    $state=proc_get_status($w['process']);
    if($state['running']) {
        proc_terminate($w['process'],15);
        $deadline=hrtime(true)+1_000_000_000;
        do { aoirDrainWorker($w,20000); if(!$w['status']['running']) { break; } } while(hrtime(true)<$deadline);
        if($w['status']['running']) { proc_terminate($w['process'],9); }
    }
    aoirReapWorker($w,1);
}
function aoirConcurrentFixture(callable $body):void
{
    $f=new IdentityRegistryTestDatabase();$workers=[];$errors=[];
    try { $body($f,$workers); } catch(Throwable $e) { $errors[]=$e->getMessage(); }
    foreach($workers as &$worker) { try { aoirCleanupWorker($worker); } catch(Throwable $e) { $errors[]=$e->getMessage(); } }
    unset($worker);
    try { $f->close(); } catch(Throwable $e) { $errors[]=$e->getMessage(); }
    if($errors!==[]) { throw new TestFailure(implode(' | ',$errors)); }
}
try {
    aoirConcurrentFixture(static function($f,array &$workers):void {
        assertSameValue(true,class_exists(Migration::class),'RED_ASSERTION: registry concurrency engine is missing');
        $source=$f->allState();
        $workers[]=aoirStartWorker($f,'','lock_acquired');aoirWaitPhase($workers[0],'lock_acquired');
        $workers[]=aoirStartWorker($f,'','');
        ProductionProcessSchemaMigration::apply($f->connection,'other_');
        $workers[]=aoirStartWorker($f,'other_','');
        $other=aoirReapWorker($workers[2],5);
        assertSameValue([0,''],[$other['exit'],$other['stderr']],'independent prefix completes while first holds its lock');
        assertSameValue(true,str_contains($other['stdout'],'RESULT {"applied":true}'),'independent prefix migration applied');
        $otherSnapshot=serialize(Verification::snapshot($f->connection,'other_'));
        $second=aoirReapWorker($workers[1],8);
        $elapsed=(hrtime(true)-$workers[1]['started'])/1e9;
        assertSameValue(true,$elapsed>=3 && $elapsed<=7,'same-prefix lock timeout is5s±2s');
        assertSameValue([2,''],[$second['exit'],$second['stderr']],'same-prefix timeout stays a negative outcome');
        assertSameValue(true,str_contains($second['stdout'],'Assignment order identity registry unavailable.'),'timeout is fixed redacted error');
        assertSameValue(false,in_array('fm2_assignment_order_identities',$f->tables(),true),'timed-out worker did no DDL');
        foreach($source as $table=>$state) { assertSameValue($state,$f->allState()[$table],'same-prefix source/decoy unchanged'); }
        assertSameValue(strlen("continue\n"),fwrite($workers[0]['pipes'][0],"continue\n"),'release first phase barrier');fflush($workers[0]['pipes'][0]);
        $first=aoirReapWorker($workers[0]);
        assertSameValue([0,''],[$first['exit'],$first['stderr']],'first creator completes');
        assertSameValue(true,Migration::isBackfillComplete($f->connection),'same-prefix backfill complete after owner release');
        assertSameValue(['applied'=>false],Migration::apply($f->connection),'later creator repeats exact state');
        assertSameValue($otherSnapshot,serialize(Verification::snapshot($f->connection,'other_')),'independent prefix metadata unchanged by first');
    });
    aoirConcurrentFixture(static function($f,array &$workers):void {
        $f->seedOrder();$source=$f->allState();
        $workers[]=aoirStartWorker($f,'','before_backfill_commit');aoirWaitPhase($workers[0],'before_backfill_commit');
        $external=$f->connect($f->name);
        try {
            assertSameValue('0',(string)$external->query('SELECT COUNT(*) n FROM fm2_assignment_order_identities')->fetch_assoc()['n'],'fresh observer sees no uncommitted identities');
            assertSameValue('0',(string)$external->query('SELECT COUNT(*) n FROM fm2_assignment_order_id_receipts')->fetch_assoc()['n'],'fresh observer sees no uncommitted receipt');
        } finally { $external->close(); }
        assertSameValue(true,proc_get_status($workers[0]['process'])['running'],'worker is live at deterministic transaction barrier');
        assertSameValue(true,proc_terminate($workers[0]['process'],15),'terminate exact owned worker before commit');
        $terminated=aoirReapWorker($workers[0],1);
        assertSameValue(true,$terminated['signaled'],'actual process termination observed');
        assertSameValue(15,$terminated['termsig'],'expected owned TERM signal');
        assertSameValue(false,Migration::isBackfillComplete($f->connection),'terminated transaction did not commit receipt');
        assertSameValue(['applied'=>true],Migration::apply($f->connection),'new process can recover after real interrupted connection');
        assertSameValue(1,count(Verification::snapshot($f->connection,'')->identities),'recovery backfills once');
        foreach($source as $table=>$state) { assertSameValue($state,$f->allState()[$table],'hard termination preserves source/decoy'); }
    });
    echo "ASSIGNMENT_ORDER_IDENTITY_REGISTRY_CONCURRENCY_001_OK\n";
} catch(TestFailure $e) { fwrite(STDERR,$e->getMessage()."\n");exit(1); }
