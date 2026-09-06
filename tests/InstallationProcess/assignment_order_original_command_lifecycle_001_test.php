<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalLifecycleFixture.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;
// COMMAND-LIFECYCLE-001 v0.1. Literal Example A, no reference production trace.
$pdf=base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);
assertSameValue(327,strlen($pdf),'fixed byte size');assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',hash('sha256',$pdf),'fixed digest');
const LIFECYCLE_FP='fingerprint:dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d';
function lifeTuple(O\AssignmentOrderOriginalResult $r):array{return [$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->requestId(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize(),$r->uploadedAt()];}
function lifeExpected(string $status='accepted',?string $reason=null,bool $retry=false,string $root='original-0001',string $revision='revision-0001'):array{return [$status,$reason,$retry,'00000000-0000-4000-8000-000000000001',...(in_array($status,['accepted','replayed'],true)?[$root,$revision,1,'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z']:array_fill(0,7,null))];}
$pre=['authorize','request','composition','clock','lifecycle:after_request_miss_before_stream'];
$begin=['storage:stage_begin:null','stage.begin'];
$read=[...$begin,'stream.read:65536','stage.write:327','storage:stage_write:null','stream.read:65536','storage:stage_done:null','stage.completed','inspect'];
$identity=[LIFECYCLE_FP,'lifecycle:after_fingerprint_miss_before_cas','lineage','id.root','id.revision'];
$finalize=['storage:finalize_begin:null','stage.finalize','storage:finalize_done:private-content-0001'];
$close=['storage:stage_close:null','stage.close','stream.close'];
$candidate=[...$pre,...$read,...$identity,...$finalize,...$close,'lifecycle:after_private_finalize_before_commit'];
$normal=[...$candidate,'commit','lease.release','lifecycle:after_commit_before_return','delivery'];
$abort=['storage:abort_begin:null','stage.abort','storage:abort_done:null'];
$cleanup=[...$abort,...$close];
function lifeNoFacts(S\OriginalLifecycleFixture $f,string $before):void{assertSameValue($before,$f->repository->evidenceCanonicalJson(4512,81),'no facts or prior-evidence mutation');assertSameValue(0,$f->observers->deliveryCalls,'no delivery');}
function lifeCounts(S\OriginalLifecycleFixture $f,int $abort,int $close,int $finalize,int $release):void
{
    assertSameValue([$abort,$close,$finalize],[$f->storage->stage?->abortCalls??0,$f->storage->stage?->closeCalls??0,$f->storage->stage?->finalizeCalls??0],'exact stage primitive counts');
    assertSameValue(1,$f->stream->closeCalls,'stream close exactly once');assertSameValue($release,$f->storage->stage?->outcome?->value->releaseCalls??0,'returned lease release exactly once');
}
$failures=[];$passed=0;
function lifeCase(string $name,callable $case):void{global $failures,$passed;try{$case();++$passed;echo "PASS $name\n";}catch(Throwable $e){$failures[]=$name;echo "FAIL $name: ".$e->getMessage()."\n";}}
foreach([[],['content_second_call_trap'],['outcome_already']] as $index=>$faults){lifeCase('accepted-capture-once-'.$index,function()use($pdf,$faults,$normal){
    $f=new S\OriginalLifecycleFixture($pdf,$faults);$r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected(),lifeTuple($r),'full accepted result');assertSameValue($normal,$f->trace->calls,'literal accepted full ordering');lifeCounts($f,0,1,1,1);assertSameValue([1,0,1],[count($f->repository->accepted),count($f->repository->attempts),$f->observers->deliveryCalls],'exact accepted/audit/delivery');
    $out=$f->storage->stage->outcome;assertSameValue([1,1,1,1],[$out->statusCalls,$out->leaseCalls,$out->value->statusCalls,$out->value->contentCalls],'outcome/lease/content each captured once');$gets=array_count_values($out->value->value->gets);ksort($gets);assertSameValue(['id'=>1,'sha'=>1,'size'=>1],$gets,'validate content fields once and reuse captured values');
});}
lifeCase('pdf-failed-factory',function(){assertSameValue(true,method_exists(O\AssignmentOrderOriginalPdfInspection::class,'failed'),'approved failed factory exists; missing API cannot masquerade as inspector failure');assertSameValue(O\AssignmentOrderOriginalPdfStatus::INSPECTOR_FAILED,O\AssignmentOrderOriginalPdfInspection::failed()->status,'typed factory outcome');});
lifeCase('pdf-private-constructor',function(){assertSameValue(true,(new ReflectionClass(O\AssignmentOrderOriginalPdfInspection::class))->getConstructor()->isPrivate(),'approved inspection constructor remains closed');});
$failMatrix=[
 'begin_throw'=>['storage_failure',[...$pre,...$begin,'stream.close'],0,0,0],
 'read_throw'=>['stream_failure',[...$pre,...$begin,'stream.read:65536',...$cleanup],1,1,0],
 'read_empty'=>['stream_failure',[...$pre,...$begin,'stream.read:65536',...$cleanup],1,1,0],
 'read_eof_payload'=>['stream_failure',[...$pre,...$begin,'stream.read:65536',...$cleanup],1,1,0],
 'read_failed_payload'=>['stream_failure',[...$pre,...$begin,'stream.read:65536',...$cleanup],1,1,0],
 'read_oversized'=>['stream_failure',[...$pre,...$begin,'stream.read:65536',...$cleanup],1,1,0],
 'write_throw'=>['storage_failure',[...$pre,...$begin,'stream.read:65536','stage.write:327',...$cleanup],1,1,0],
 'write_failed'=>['storage_failure',[...$pre,...$begin,'stream.read:65536','stage.write:327',...$cleanup],1,1,0],
 'completed_throw'=>['storage_failure',[...$pre,...array_slice($read,0,-1),...$cleanup],1,1,0],
 'inspector_throw'=>['storage_failure',[...$pre,...$read,...$cleanup],1,1,0],
 'inspector_failed'=>['storage_failure',[...$pre,...$read,...$cleanup],1,1,0],
 'finalize_throw'=>['storage_failure',[...$pre,...$read,...$identity,'storage:finalize_begin:null','stage.finalize',...$cleanup],1,1,1],
 'observer:stage_begin'=>['storage_failure',[...$pre,'storage:stage_begin:null','stream.close'],0,0,0],
 'observer:stage_write'=>['storage_failure',[...$pre,...$begin,'stream.read:65536','stage.write:327','storage:stage_write:null',...$cleanup],1,1,0],
 'observer:stage_done'=>['storage_failure',[...$pre,...array_slice($read,0,-2),...$cleanup],1,1,0],
 'observer:finalize_begin'=>['storage_failure',[...$pre,...$read,...$identity,'storage:finalize_begin:null',...$cleanup],1,1,0],
 'observer:after_request_miss_before_stream'=>['persistence_failure',[...$pre,'stream.close'],0,0,0],
 'observer:after_fingerprint_miss_before_cas'=>['persistence_failure',[...$pre,...$read,LIFECYCLE_FP,'lifecycle:after_fingerprint_miss_before_cas',...$cleanup],1,1,0],
];
foreach($failMatrix as $fault=>[$reason,$trace,$aborts,$closes,$finalizes])lifeCase($fault,function()use($pdf,$fault,$reason,$trace,$aborts,$closes,$finalizes){$f=new S\OriginalLifecycleFixture($pdf,[$fault]);$before=$f->repository->evidenceCanonicalJson(4512,81);$r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected('failed',$reason,true),lifeTuple($r),'primitive-specific full failure');assertSameValue([...$trace,'log:'.(in_array($reason,['stream_failure','storage_failure'],true)?'file_failure':'submission')],$f->trace->calls,'literal acquisition trace followed by required diagnostic');lifeCounts($f,$aborts,$closes,$finalizes,0);lifeNoFacts($f,$before);});
foreach(['outcome_failed_with_lease','outcome_null_lease','lease_bad_status','lease_status_throw','lease_null_content','lease_content_throw','content_bad_id','content_bad_sha','content_bad_size','content_id_throw','content_sha_throw','content_size_throw'] as $fault){
 lifeCase($fault,function()use($pdf,$fault,$pre,$read,$identity,$cleanup){$f=new S\OriginalLifecycleFixture($pdf,[$fault]);$before=$f->repository->evidenceCanonicalJson(4512,81);$r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected('failed','storage_failure',true),lifeTuple($r),'invalid finalize/lease/content maps storage failure');$release=$fault==='outcome_null_lease'?0:1;assertSameValue([...$pre,...$read,...$identity,'storage:finalize_begin:null','stage.finalize',...$cleanup,...($release?['lease.release']:[]),'log:file_failure'],$f->trace->calls,'invalid finalize cleanup/release without DONE or commit');lifeCounts($f,1,1,1,$release);assertSameValue(1,$f->storage->stage->outcome->leaseCalls,'inspect returned lease even for non-success outcome');lifeNoFacts($f,$before);});
}
foreach(['outcome_status_throw'=>[1,0,0],'outcome_lease_throw'=>[1,1,0],'outcome_locked'=>[1,1,1],'outcome_locked_null'=>[1,1,0]] as $fault=>[$statusGets,$leaseGets,$release]){
 lifeCase($fault,function()use($pdf,$fault,$statusGets,$leaseGets,$release,$pre,$read,$identity,$cleanup){$f=new S\OriginalLifecycleFixture($pdf,[$fault]);$before=$f->repository->evidenceCanonicalJson(4512,81);$r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected('failed','storage_failure',true),lifeTuple($r),'outcome getter/LOCKED failure is storage failure');assertSameValue([...$pre,...$read,...$identity,'storage:finalize_begin:null','stage.finalize',...$cleanup,...($release?['lease.release']:[]),'log:file_failure'],$f->trace->calls,'no finalize DONE/commit/audit/delivery and release only returned lease');lifeCounts($f,1,1,1,$release);$out=$f->storage->stage->outcome;assertSameValue([$statusGets,$leaseGets],[$out->statusCalls,$out->leaseCalls],'status then lease getter each attempted once until throw');if(!$release)assertSameValue([0,0],[$out->value->statusCalls,$out->value->contentCalls],'inaccessible/unreturned lease not inspected or adopted');lifeNoFacts($f,$before);});
}
$late=[
 'observer:finalize_done'=>['storage_failure',[...$pre,...$read,...$identity,...$finalize,...$cleanup,'lease.release'],1],
 'stage_close_throw'=>['storage_failure',[...$pre,...$read,...$identity,...$finalize,'storage:stage_close:null','stage.close','log:stage_close',...$abort,'stream.close','lease.release'],1],
 'observer:stage_close'=>['storage_failure',[...$pre,...$read,...$identity,...$finalize,'storage:stage_close:null','stage.close',...$abort,'stream.close','lease.release'],1],
 'stream_close_throw'=>['stream_failure',[...$pre,...$read,...$identity,...$finalize,...$close,'log:stream_close',...$abort,'lease.release'],1],
 'observer:after_private_finalize_before_commit'=>['persistence_failure',[...$candidate,'lease.release'],0],
];
foreach($late as $fault=>[$reason,$trace,$aborts])lifeCase($fault,function()use($pdf,$fault,$reason,$trace,$aborts){$f=new S\OriginalLifecycleFixture($pdf,[$fault]);$before=$f->repository->evidenceCanonicalJson(4512,81);$r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected('failed',$reason,true),lifeTuple($r),'late failure result');assertSameValue([...$trace,'log:'.(in_array($reason,['stream_failure','storage_failure'],true)?'file_failure':'submission')],$f->trace->calls,'no repeated close/abort; default unavailable attempt writer is diagnosed');lifeCounts($f,$aborts,1,1,1);lifeNoFacts($f,$before);});
foreach(['rejected','fingerprint_replay'] as $mode)foreach([[],['abort_failed'],['abort_throw'],['stage_close_throw'],['stream_close_throw'],['observer:abort_begin','abort_throw','stage_close_throw','stream_close_throw','log_throw'],['observer:abort_done'],['observer:stage_close']] as $index=>$faults){
 lifeCase($mode.'-cleanup-'.$index,function()use($pdf,$mode,$faults,$index,$pre,$read){$f=new S\OriginalLifecycleFixture($pdf,[...$faults,...($mode==='rejected'?['invalid_pdf']:[])],$mode==='rejected'?'normal':$mode);$before=$f->repository->evidenceCanonicalJson(4512,81);$r=$f->application->submitAssignmentOrderOriginal($f->command());$expected=$mode==='rejected'?lifeExpected('rejected','invalid_pdf'):lifeExpected('replayed');assertSameValue($expected,lifeTuple($r),'selected rejected/replay preserved through all cleanup failures');
    $trace=[...$pre,...$read,...($mode==='fingerprint_replay'?[LIFECYCLE_FP]:[]),'storage:abort_begin:null','stage.abort'];$logs=[];
    if(in_array('abort_failed',$faults,true)||in_array('abort_throw',$faults,true)){$trace[]='log:stage_abort';$logs[]=['ASSIGNMENT_ORDER_ORIGINAL_STAGE_ABORT_FAILED',['phase'=>'stage_abort']];}else $trace[]='storage:abort_done:null';
    array_push($trace,'storage:stage_close:null','stage.close');if(in_array('stage_close_throw',$faults,true)){$trace[]='log:stage_close';$logs[]=['ASSIGNMENT_ORDER_ORIGINAL_STAGE_CLOSE_FAILED',['phase'=>'stage_close']];}
    $trace[]='stream.close';if(in_array('stream_close_throw',$faults,true)){$trace[]='log:stream_close';$logs[]=['ASSIGNMENT_ORDER_ORIGINAL_STREAM_CLOSE_FAILED',['phase'=>'stream_close']];}if($mode==='rejected')$trace[]='audit';
    assertSameValue($trace,$f->trace->calls,'attempt-always cleanup and exact observer/log order');assertSameValue($logs,$f->observers->logs,'exact event names and sole fields; observer-only errors do not invent primitive diagnostics');lifeCounts($f,1,1,0,0);assertSameValue(0,count($f->repository->accepted),'no acceptance');assertSameValue($mode==='rejected'?1:0,count($f->repository->attempts),'only required rejected audit');assertSameValue(0,$f->observers->deliveryCalls,'no delivery');if($mode==='fingerprint_replay')assertSameValue($before,$f->repository->evidenceCanonicalJson(4512,81),'winner evidence unchanged');
 });
}
foreach([[],['stream_close_throw','log_throw']] as $index=>$faults)lifeCase('terminal-replay-'.$index,function()use($pdf,$faults,$index){$f=new S\OriginalLifecycleFixture($pdf,$faults,'terminal_replay');$before=$f->repository->evidenceCanonicalJson(4512,81);$r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected('replayed'),lifeTuple($r),'terminal replay result preserved');assertSameValue(['authorize','request','stream.close',...($index?['log:stream_close']:[])],$f->trace->calls,'exact replay closure-only transcript');assertSameValue([0,1,0],[$f->stream->readCalls,$f->stream->closeCalls,$f->storage->beginCalls],'unread stream close without stage');lifeNoFacts($f,$before);});
$commits=[
 'normal'=>['accepted',null,false,'committed',[],1],
 'rolled_back'=>['failed','persistence_failure',true,'rolled_back',[],0],
 'unknown_found'=>['accepted',null,false,'unknown_found',['fresh.open','fresh.read','fresh.close'],1],
 'unknown_not_found'=>['failed','persistence_failure',true,'unknown_not_found',['fresh.open','fresh.read','fresh.close'],0],
 'unknown_unavailable'=>['failed','persistence_outcome_unknown',true,'unknown_unavailable',['fresh.open','fresh.read','fresh.close'],1],
 'unknown_recovery_throw'=>['failed','persistence_outcome_unknown',true,'unknown_unavailable',['fresh.open','fresh.read','fresh.close'],1],
 'unknown_result_getter_throw'=>['failed','persistence_outcome_unknown',true,'unknown_unavailable',['fresh.open','fresh.read','fresh.close'],1],
 'commit_throw_found'=>['accepted',null,false,'unknown_found',['fresh.open','fresh.read','fresh.close'],1],
 'commit_throw_not_found'=>['failed','persistence_failure',true,'unknown_not_found',['fresh.open','fresh.read','fresh.close'],0],
 'commit_throw_unavailable'=>['failed','persistence_outcome_unknown',true,'unknown_unavailable',['fresh.open','fresh.read','fresh.close'],1],
 'conflict_replay'=>['replayed',null,false,'commit_conflict',[LIFECYCLE_FP],0],
 'conflict_none'=>['conflict','initial_already_exists',false,'commit_conflict',[LIFECYCLE_FP,'lineage'],0],
 'conflict_fingerprint_throw'=>['failed','persistence_failure',true,'commit_conflict',[LIFECYCLE_FP],0],
 'conflict_lineage_throw'=>['failed','persistence_failure',true,'commit_conflict',[LIFECYCLE_FP,'lineage'],0],
 'conflict_result_getter_throw'=>['failed','persistence_failure',true,'commit_conflict',[LIFECYCLE_FP],0],
];
foreach($commits as $mode=>[$status,$reason,$retry,$phase,$recovery,$facts])foreach(['release_failed','release_throw'] as $releaseFault)lifeCase('commit-'.$mode.'-'.$releaseFault,function()use($pdf,$mode,$status,$reason,$retry,$phase,$recovery,$facts,$candidate,$releaseFault){$f=new S\OriginalLifecycleFixture($pdf,[$releaseFault,'log_throw'],$mode);$r=$f->application->submitAssignmentOrderOriginal($f->command());$winner=$mode==='conflict_replay';assertSameValue(lifeExpected($status,$reason,$retry,$winner?'original-0099':'original-0001',$winner?'revision-0099':'revision-0001'),lifeTuple($r),'selected commit/recovery result preserved');assertSameValue([...$candidate,'commit',...$recovery,'lease.release','log:'.$phase,...($status==='accepted'?['lifecycle:after_commit_before_return','delivery']:[]),...($status==='conflict'?['audit']:[]),...($status==='failed'?['log:submission']:[])],$f->trace->calls,'exact reread/release/log/lifecycle/delivery/audit order');lifeCounts($f,0,1,1,1);assertSameValue([['ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED',['phase'=>$phase]],...($status==='failed'?[[$reason==='persistence_outcome_unknown'?'ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_UNKNOWN':'ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_FAILED',['phase'=>'submission']]]:[])],$f->observers->logs,'one release diagnostic plus required submission diagnostic despite logger failure');assertSameValue($facts,count($f->repository->accepted),'fixture-confirmed committed facts');assertSameValue(1,$f->repository->commitCalls,'no blind commit retry');assertSameValue($status==='conflict'?1:0,count($f->repository->attempts),'only required conflict audit');});
lifeCase('accepted-close-first-failure-preserved',function()use($pdf,$pre,$read,$identity,$finalize){$f=new S\OriginalLifecycleFixture($pdf,['stage_close_throw','abort_failed','stream_close_throw','release_failed','log_throw']);$before=$f->repository->evidenceCanonicalJson(4512,81);$r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected('failed','storage_failure',true),lifeTuple($r),'first candidate failure survives later cleanup/release/log faults');assertSameValue([...$pre,...$read,...$identity,...$finalize,'storage:stage_close:null','stage.close','log:stage_close','storage:abort_begin:null','stage.abort','log:stage_abort','stream.close','log:stream_close','lease.release','log:rolled_back','log:file_failure'],$f->trace->calls,'actual exceptional attempt order and audit diagnostic without repeated closes');lifeCounts($f,1,1,1,1);lifeNoFacts($f,$before);});
foreach(['observer:after_commit_before_return','delivery_throw'] as $fault)lifeCase('response-loss-'.$fault,function()use($pdf,$fault,$candidate){
    $f=new S\OriginalLifecycleFixture($pdf,[$fault]);$command=$f->command();$stream=$f->stream;$returned=null;$caught=null;try{$returned=$f->application->submitAssignmentOrderOriginal($command);}catch(Throwable $e){$caught=$e;}
    assertSameValue(null,$returned,'response-loss invocation returns no Result');assertSameValue(true,$caught!==null,'fixed response-loss exception is observable');assertSameValue('FMonitor2\\AssignmentOrderOriginal\\AssignmentOrderOriginalResponseDeliveryLost',get_class($caught),'fixed exception class');assertSameValue(['Assignment order original response delivery lost.',0,null],[$caught->getMessage(),$caught->getCode(),$caught->getPrevious()],'fixed redacted exception values');
    assertSameValue([...$candidate,'commit','lease.release','lifecycle:after_commit_before_return',...($fault==='delivery_throw'?['delivery']:[])],$f->trace->calls,'postcommit failure does not reenter acquisition cleanup');lifeCounts($f,0,1,1,1);assertSameValue(1,count($f->repository->accepted),'durable acceptance remains');$before=$f->repository->evidenceCanonicalJson(4512,81);$f->trace->calls=[];
    $r=$f->application->submitAssignmentOrderOriginal($f->command());assertSameValue(lifeExpected('replayed'),lifeTuple($r),'same-request retry replays committed evidence');assertSameValue(['authorize','request','stream.close'],$f->trace->calls,'retry does no postcommit callbacks or new acquisition');assertSameValue([0,1,1],[$f->stream->readCalls,$f->stream->closeCalls,$stream->closeCalls],'fresh unread stream closed; old stream not closed again');assertSameValue($before,$f->repository->evidenceCanonicalJson(4512,81),'retry no duplicate persistence');
});
if($failures!==[])throw new TestFailure('Lifecycle failed '.count($failures).' cases: '.implode(',',$failures));
echo "PASS COMMAND-LIFECYCLE-001 ($passed cases)\n";
