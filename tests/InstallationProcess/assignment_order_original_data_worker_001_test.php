<?php

declare(strict_types=1);
// FMONITOR_TEST_DB: real public worker composition and truthful race/lease boundaries.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityWorker.php';
use FMonitor2\Tests\Support as S;

function integrityWorkerResult(array $out):array
{assertSameValue(0,$out['exit'],'worker exits successfully');assertSameValue('',$out['stdout'],'worker stdout empty');assertSameValue('',$out['stderr'],'worker stderr empty');return json_decode($out['result'],true,32,JSON_THROW_ON_ERROR);}
function integrityWorkerLogs(S\OriginalIntegrityDatabase $f):array
{$items=[];foreach(file($f->safeLog,FILE_IGNORE_NEW_LINES)?:[] as $line)if($line!=='')$items[]=json_decode($line,true,32,JSON_THROW_ON_ERROR);return $items;}
foreach(['commit_unknown_found','commit_unknown_not_found','commit_unknown_unavailable'] as $fault)foreach([false,true] as $releaseFailure)integrityCase('real-worker-'.$fault.'-release'.(int)$releaseFailure,function()use($fault,$releaseFailure){
    $f=new S\OriginalIntegrityDatabase();$worker=null;
    try{
        $worker=new S\OriginalIntegrityWorker($f,S\OriginalIntegrityWorker::command(),['faultPoint'=>$fault.($releaseFailure?'_release_failure':'')]);$r=integrityWorkerResult($worker->finish());$worker=null;
        $found=$fault==='commit_unknown_found';$unavailable=$fault==='commit_unknown_unavailable';
        assertSameValue($found?['accepted',null,false]:['failed',$unavailable?'persistence_outcome_unknown':'persistence_failure',true],[$r['status'],$r['reasonCode'],$r['retryable']],'closed worker unknown outcome');
        assertSameValue($fault==='commit_unknown_not_found'?0:1,count($f->rows('fm2_assignment_order_original_requests')),'unknown script actual durable state');
        $logs=array_values(array_filter(integrityWorkerLogs($f),fn($v)=>$v['event']==='ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED'));
        $phase=match($fault){'commit_unknown_found'=>'unknown_found','commit_unknown_not_found'=>'unknown_not_found',default=>'unknown_unavailable'};
        assertSameValue($releaseFailure?1:0,count($logs),'exact once release diagnostic');
        if($releaseFailure)assertSameValue(['correlationId'=>'11e594f48195','event'=>'ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED','safeFields'=>['phase'=>$phase],'sequence'=>$logs[0]['sequence']],$logs[0],'release phase proves unknown recovery, not disguised rollback');
        if($found||$unavailable){$before=$f->facts();$retry=S\OriginalIntegrityWorker::command(['documentDate'=>'2099-01-01'], 'unread retry bytes');$worker=new S\OriginalIntegrityWorker($f,$retry);$again=integrityWorkerResult($worker->finish());$worker=null;assertSameValue('replayed',$again['status'],'next ordinary same request replays durable fact');assertSameValue('revision-0001',$again['currentRevisionId'],'exact existing revision');assertSameValue($before,$f->facts(),'worker retry no new fact');}
    }finally{if($worker!==null)$worker->stop();$f->close();}
});
foreach([false,true] as $missingPassword)integrityCase('worker-safe-log-first-missing-password-'.(int)$missingPassword,function()use($missingPassword){$f=new S\OriginalIntegrityDatabase();$worker=null;try{$changes=['safeLogFile'=>$f->control.'/missing-safe-log'];if($missingPassword)$changes['databasePasswordFile']=$f->control.'/missing-password';$before=$f->facts();$worker=new S\OriginalIntegrityWorker($f,S\OriginalIntegrityWorker::command(),$changes);$out=$worker->finish();$worker=null;assertSameValue([70,'',"ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n",'',''],[$out['exit'],$out['stdout'],$out['stderr'],$out['ready'],$out['result']],'bad safe log fails through exact redacted transport before any secret-content read warning');assertSameValue($before,$f->facts(),'invalid configuration creates no fact');assertSameValue(false,file_exists($f->control.'/missing-safe-log'),'worker never creates log');assertSameValue(false,file_exists($f->control.'/missing-password'),'worker never creates password');}finally{if($worker!==null)$worker->stop();$f->close();}});
foreach([false,true] as $postFinalize)integrityCase('real-worker-different-correction-race-'.($postFinalize?'post-finalize':'same-pdf-precheck'),function()use($postFinalize){
    $f=new S\OriginalIntegrityDatabase();$baseWorker=$a=$b=null;
    try{
        $baseWorker=new S\OriginalIntegrityWorker($f,S\OriginalIntegrityWorker::command());$initial=integrityWorkerResult($baseWorker->finish());$baseWorker=null;assertSameValue('accepted',$initial['status'],'real baseline owns original blob and fact');
        $before=$f->facts();$baseBlob=json_decode(file_get_contents($f->privateRoot.'/.aoou-state.json'),true,32,JSON_THROW_ON_ERROR);
        $common=['mode'=>'correction','rootOriginalId'=>'original-0001','targetRevisionId'=>'revision-0001','expectedCurrentRevisionId'=>'revision-0001','correctionReason'=>'Concurrent correction'];
        $ca=S\OriginalIntegrityWorker::command(['requestId'=>'00000000-0000-4000-8000-000000000701','documentDate'=>'2026-09-02']+$common);
        $differentBytes=S\OriginalIntegrityFixture::pdf().' ';$cb=S\OriginalIntegrityWorker::command(['requestId'=>'00000000-0000-4000-8000-000000000702','documentDate'=>'2026-08-29']+$common,$postFinalize?$differentBytes:null);
        $barrier=$postFinalize?'after_private_finalize_before_commit':'after_fingerprint_miss_before_cas';
        $a=new S\OriginalIntegrityWorker($f,$ca,['revisionIdSequenceCsv'=>'revision-0701','barrierEvent'=>$barrier,'clockUtc'=>'2026-09-02T09:16:00Z']);assertSameValue("READY 00000000-0000-4000-8000-000000000701\n",$a->awaitReady(),'A reached exact requested application barrier');
        $b=new S\OriginalIntegrityWorker($f,$cb,['revisionIdSequenceCsv'=>'revision-0702','barrierEvent'=>$barrier,'clockUtc'=>'2026-09-02T09:16:00Z','faultPoint'=>'content_lease_release']);assertSameValue("READY 00000000-0000-4000-8000-000000000702\n",$b->awaitReady(),'B reached exact requested application barrier');
        assertSameValue($before,$f->facts(),'both barriers precede any new domain fact');$atBarrier=json_decode(file_get_contents($f->privateRoot.'/.aoou-state.json'),true,32,JSON_THROW_ON_ERROR);
        assertSameValue($postFinalize?0:2,count($atBarrier['stages']),'barrier reflects actual stage/finalize ownership');assertSameValue($postFinalize?2:1,count($atBarrier['finalized']),'distinct digest supports two held post-finalize leases');
        $a->release();$ra=integrityWorkerResult($a->finish(false));$a=null;assertSameValue('accepted',$ra['status'],'A durably wins before B release');$b->release();$rb=integrityWorkerResult($b->finish(false));$b=null;
        assertSameValue(['conflict','stale_revision',false],[$rb['status'],$rb['reasonCode'],$rb['retryable']],'B exact stale outcome');assertSameValue(2,count($f->rows('fm2_assignment_order_original_revisions')),'only A appended revision');assertSameValue(2,count($f->rows('fm2_assignment_order_original_events')),'only A emitted event');
        $requests=$f->rows('fm2_assignment_order_original_requests');$losers=array_values(array_filter($requests,fn($v)=>$v['request_id']===$cb['requestId']));assertSameValue([['conflict','stale_revision',null]],array_map(fn($v)=>[$v['status'],$v['reason_code'],$v['current_revision_id']],$losers),'B owns only terminal stale result');
        $logs=array_values(array_filter(integrityWorkerLogs($f),fn($v)=>$v['correlationId']===substr(hash('sha256',$cb['requestId']),0,12)&&$v['event']==='ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED'));
        assertSameValue($postFinalize?1:0,count($logs),'pre-finalize stale owns no lease; post-CAS loser owns one failed release');if($postFinalize)assertSameValue(['phase'=>'commit_conflict'],$logs[0]['safeFields'],'post-CAS release diagnostic phase');
        $after=json_decode(file_get_contents($f->privateRoot.'/.aoou-state.json'),true,32,JSON_THROW_ON_ERROR);assertSameValue([],$after['stages'],'both stages gone');
        if(!$postFinalize)assertSameValue($baseBlob['finalized'],$after['finalized'],'same-PDF race creates no extra blob/orphan');
        else{$loserIdentity='content-sha256-'.hash('sha256',$differentBytes);$matching=array_values(array_filter($after['finalized'],fn($v)=>$v['opaqueIdentity']===$loserIdentity));assertSameValue(1,count($matching),'loser distinct digest remains private finalized orphan');assertSameValue(328,$matching[0]['byteSize'],'exact alternate valid PDF size');$references=array_filter($f->rows('fm2_assignment_order_original_revisions'),fn($v)=>$v['private_content_identity']===$loserIdentity);assertSameValue([] ,array_values($references),'orphan never becomes a domain reference');}
    }finally{foreach([$baseWorker,$a,$b] as $worker)if($worker!==null)$worker->stop();$f->close();}
});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_DATA_WORKER_OK');
