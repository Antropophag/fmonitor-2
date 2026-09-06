<?php

declare(strict_types=1);
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

// DATA-INTEGRITY001v0.6§10: the writer repository never masquerades as a fresh reader.
function integrityRecoveryFixture(array $options=[]):S\OriginalIntegrityFixture
{return new S\OriginalIntegrityFixture($options+['acceptedOutcome'=>O\AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN,'durableAccepted'=>true]);}
function integrityRecoveryOwnership(S\OriginalIntegrityFixture $f,bool $opened=true):void
{
    assertSameValue(1,count($f->repository->acceptedCalls),'exactly one accepted commit invocation');
    assertSameValue(1,$f->repository->terminalCalls,'ordinary terminal lookup is never reused for recovery');
    assertSameValue(1,$f->fresh->opens,'one fresh factory open');assertSameValue($opened?1:0,$f->fresh->reader->reads,'one owned fresh read');
    assertSameValue($opened?1:0,$f->fresh->reader->closes,'fresh reader closes exactly once');
    assertSameValue(1,$f->clock->calls,'no clock retry');assertSameValue(1,$f->ids->rootCalls,'one initial root allocation');assertSameValue(1,$f->ids->revisionCalls,'one revision allocation');
    assertSameValue(2,$f->stream->readCalls,'no stream retry');assertSameValue(1,$f->stream->closeCalls,'one stream close');
    assertSameValue(1,$f->storage->stage->closeCalls,'one stage close');assertSameValue(1,$f->storage->stage->lease->releaseCalls,'one lease release');
    assertSameValue([], $f->repository->attemptCalls,'fresh stored outcome never re-commits an attempt');
    $calls=$f->trace->calls;assertSameValue(true,array_search('accepted',$calls,true)<array_search('fresh.open',$calls,true),'open only after ambiguous commit');
    if($opened)assertSameValue(true,array_search('fresh.close',$calls,true)<array_search('lease.release',$calls,true),'retain content lease through fresh read/copy/close');
    else assertSameValue(true,array_search('fresh.open',$calls,true)<array_search('lease.release',$calls,true),'release after failed open');
}
function integrityRecoveryFailure(S\OriginalIntegrityFixture $f,O\AssignmentOrderOriginalResult $r,string $reason):void
{assertSameValue(['failed',$reason,true,$f->command->requestId,null,null,null,null,null,null,null],integrityTuple($r),'exact recovery failure without evidence');assertSameValue(0,$f->observers->deliveryCalls,'unconfirmed outcome has no delivery');}
integrityCase('fresh-public-API',function(){
    foreach([O\AssignmentOrderOriginalFreshTerminalReaderFactory::class,O\AssignmentOrderOriginalFreshTerminalReader::class] as $name)assertSameValue(true,interface_exists($name),'INTENDED_RED: required fresh reader interface '.$name);
    foreach([O\AssignmentOrderOriginalFreshTerminalReaderOpenResult::class,O\AssignmentOrderOriginalFreshReaderConfig::class,O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory::class] as $name)assertSameValue(true,class_exists($name),'INTENDED_RED: required fresh reader class '.$name);
    assertSameValue(['opened','unavailable'],array_map(fn($s)=>$s->value,O\AssignmentOrderOriginalFreshReaderOpenStatus::cases()),'closed open states');
    assertSameValue(['closed','failed'],array_map(fn($s)=>$s->value,O\AssignmentOrderOriginalFreshReaderCloseStatus::cases()),'closed close states');
    $parameters=(new ReflectionClass(O\AssignmentOrderOriginalDependencies::class))->getConstructor()->getParameters();$last=end($parameters);
    assertSameValue('freshTerminalReaders',$last->getName(),'exact trailing dependency name');assertSameValue(true,$last->isDefaultValueAvailable(),'source-compatible optional factory');assertSameValue(null,$last->getDefaultValue(),'missing explicit factory is degraded');
});
foreach([O\AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN,new RuntimeException('unconfirmed native commit')] as $i=>$commit)integrityCase('fresh-found-after-ambiguous-'.$i,function()use($commit){$f=integrityRecoveryFixture(['acceptedOutcome'=>$commit]);$r=$f->run();assertSameValue(['accepted',null,false,'00000000-0000-4000-8000-000000000001','original-0001','revision-0001',1,'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z'],integrityTuple($r),'confirmed fresh stored accepted result');integrityRecoveryOwnership($f);assertSameValue(1,$f->observers->deliveryCalls,'accepted recovery delivery once');});
foreach(['unavailable','throw'] as $kind)integrityCase('fresh-open-'.$kind,function()use($kind){$f=integrityRecoveryFixture(['freshOpen'=>$kind]);integrityRecoveryFailure($f,$f->run(),'persistence_outcome_unknown');integrityRecoveryOwnership($f,false);});
integrityCase('fresh-absent-provider-degraded',function(){$f=integrityRecoveryFixture(['withoutFresh'=>true]);integrityRecoveryFailure($f,$f->run(),'persistence_outcome_unknown');assertSameValue(1,$f->repository->terminalCalls,'missing provider never reuses writer');assertSameValue(0,$f->fresh->opens,'omitted fixture factory unused');assertSameValue(1,$f->storage->stage->lease->releaseCalls,'degraded recovery releases once');});
foreach(O\AssignmentOrderOriginalLookupStatus::cases() as $status)foreach([false,true] as $payload)integrityCase('fresh-lookup-'.$status->value.'-'.(int)$payload,function()use($status,$payload){
    $lookup=new S\OriginalIntegrityLookup($status,$payload?new S\OriginalIntegrityResult():null);
    $f=integrityRecoveryFixture(['freshLookup'=>$lookup,'durableAccepted'=>$status!==O\AssignmentOrderOriginalLookupStatus::NOT_FOUND]);$r=$f->run();
    if($status===O\AssignmentOrderOriginalLookupStatus::FOUND&&$payload)assertSameValue(O\AssignmentOrderOriginalStatus::ACCEPTED,$r->status(),'valid fresh FOUND');
    else integrityRecoveryFailure($f,$r,$status===O\AssignmentOrderOriginalLookupStatus::NOT_FOUND&&!$payload?'persistence_failure':'persistence_outcome_unknown');
    integrityRecoveryOwnership($f);assertSameValue(1,$lookup->statusCalls,'fresh status once');assertSameValue(1,$lookup->resultCalls,'fresh payload once for closed negative states too');
});
foreach(['status','result','read'] as $kind)integrityCase('fresh-read-throw-'.$kind,function()use($kind){$lookup=$kind==='read'?new RuntimeException('fresh query failed'):new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,new S\OriginalIntegrityResult(),$kind);$f=integrityRecoveryFixture(['freshLookup'=>$lookup]);integrityRecoveryFailure($f,$f->run(),'persistence_outcome_unknown');integrityRecoveryOwnership($f);});
foreach(['status','reason','retryable','request','root','revision','number','date','sha','size','at'] as $getter)integrityCase('fresh-result-getter-throw-'.$getter,function()use($getter){$v=new S\OriginalIntegrityResult([],$getter);$f=integrityRecoveryFixture(['freshLookup'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);integrityRecoveryFailure($f,$f->run(),'persistence_outcome_unknown');integrityRecoveryOwnership($f);});
foreach(['foreign-UUID'=>['request'=>'00000000-0000-4000-8000-000000000999'],'bad-date'=>['date'=>'2026-02-31'],'missing-sha'=>['sha'=>null],'stored-replayed'=>['status'=>O\AssignmentOrderOriginalStatus::REPLAYED],'retryable'=>['retryable'=>true]] as $name=>$values)integrityCase('fresh-malformed-result-'.$name,function()use($values){$f=integrityRecoveryFixture(['freshLookup'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,new S\OriginalIntegrityResult($values))]);integrityRecoveryFailure($f,$f->run(),'persistence_outcome_unknown');integrityRecoveryOwnership($f);});
foreach([O\AssignmentOrderOriginalStatus::REJECTED,O\AssignmentOrderOriginalStatus::CONFLICT] as $status)integrityCase('fresh-stored-terminal-no-recommit-'.$status->value,function()use($status){$v=S\OriginalIntegrityResult::rejected($status===O\AssignmentOrderOriginalStatus::REJECTED?O\AssignmentOrderOriginalReason::INVALID_PDF:O\AssignmentOrderOriginalReason::STALE_REVISION);$v->values['status']=$status;$f=integrityRecoveryFixture(['durableAccepted'=>false,'freshLookup'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);$r=$f->run();assertSameValue([$status->value,$v->values['reason']->value,false,$f->command->requestId,null,null,null,null,null,null,null],integrityTuple($r),'stored competing terminal is resolved without mutation');integrityRecoveryOwnership($f);});
foreach(['failed','throw'] as $close)foreach(['found','miss','unavailable'] as $read)foreach([false,true] as $logThrows)integrityCase('fresh-close-'.$close.'-'.$read.'-log'.(int)$logThrows,function()use($close,$read,$logThrows){
    $lookup=match($read){'found'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,new S\OriginalIntegrityResult()),'miss'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::NOT_FOUND),default=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE)};
    $f=integrityRecoveryFixture(['freshLookup'=>$lookup,'freshClose'=>$close,'logThrows'=>$logThrows,'durableAccepted'=>$read!=='miss']);$r=$f->run();
    if($read==='found')assertSameValue(O\AssignmentOrderOriginalStatus::ACCEPTED,$r->status(),'reader close failure cannot undo validated accepted read');else integrityRecoveryFailure($f,$r,$read==='miss'?'persistence_failure':'persistence_outcome_unknown');
    integrityRecoveryOwnership($f);assertSameValue([['ASSIGNMENT_ORDER_ORIGINAL_FRESH_READER_CLOSE_FAILED',['phase'=>'accepted_commit_recovery']]],$f->observers->logs,'one exact safe observer event with existing envelope-owned correlation');
});
integrityCase('fresh-snapshot-before-close',function(){$v=new S\OriginalIntegrityResult();$f=integrityRecoveryFixture(['freshLookup'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v),'mutateOnClose'=>$v]);$r=$f->run();assertSameValue('2026-09-02T09:15:30Z',$r->uploadedAt(),'reader-backed source cannot change selected immutable result after close');assertSameValue('mutated-after-reader-close',$v->values['at'],'close mutation actually occurred');integrityRecoveryOwnership($f);foreach($v->gets as $count)assertSameValue(1,$count,'no late/repeated getter');});
integrityCase('fresh-accepted-response-loss',function(){$f=integrityRecoveryFixture(['deliveryThrows'=>true]);$caught=null;try{$f->run();}catch(Throwable $e){$caught=$e;}assertSameValue(O\AssignmentOrderOriginalResponseDeliveryLost::class,$caught===null?null:get_class($caught),'confirmed fresh acceptance retains response-loss exception');assertSameValue(['Assignment order original response delivery lost.',0,null],[$caught->getMessage(),$caught->getCode(),$caught->getPrevious()],'fixed nonconfidential response-loss shape');integrityRecoveryOwnership($f);assertSameValue(1,count($f->repository->accepted),'accepted fact remains');});
foreach([O\AssignmentOrderOriginalCommitStatus::COMMITTED,O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK,O\AssignmentOrderOriginalCommitStatus::CONFLICT] as $status)integrityCase('non-unknown-never-opens-fresh-'.$status->value,function()use($status){$f=new S\OriginalIntegrityFixture(['acceptedOutcome'=>$status]);$f->run();assertSameValue(0,$f->fresh->opens,'ordinary typed outcome does not open recovery reader');assertSameValue(0,$f->fresh->reader->reads+$f->fresh->reader->closes,'no invented reader ownership');});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_DATA_RECOVERY_OK');
