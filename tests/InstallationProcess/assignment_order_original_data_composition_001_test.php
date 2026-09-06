<?php

declare(strict_types=1);
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

function integrityComposition(array $changes=[],bool $rehash=false):O\AssignmentOrderCompositionSnapshot
{
    $v=$changes+['status'=>O\AssignmentOrderCompositionLookupStatus::FOUND,'case'=>4512,'order'=>81,'identity'=>'composition-81-v1',
        'hash'=>'388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5','ids'=>[7001,7002],'engineer'=>31];
    if($rehash)$v['hash']=hash('sha256',json_encode(['caseId'=>$v['case'],'compositionIdentity'=>$v['identity'],'engineerUserId'=>$v['engineer'],'installers'=>$v['ids'],'orderId'=>$v['order']],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
    return new O\AssignmentOrderCompositionSnapshot($v['status'],$v['case'],$v['order'],$v['identity'],$v['hash'],$v['ids'],$v['engineer']);
}
function integrityCompositionRejected(S\OriginalIntegrityFixture $f,O\AssignmentOrderOriginalResult $r,O\AssignmentOrderOriginalReason $reason):void
{
    assertSameValue(['rejected',$reason->value,false,$f->command->requestId,null,null,null,null,null,null,null],integrityTuple($r),'exact authorized composition rejection');
    assertSameValue(0,$f->stream->readCalls,'prestream rejection');assertSameValue(1,$f->stream->closeCalls,'cleanup once');
    assertSameValue(1,$f->clock->calls,'one lazy invocation instant');assertSameValue(1,count($f->repository->attemptCalls),'one terminal/audit call');
    assertSameValue(1,count($f->repository->attempts),'one confirmed terminal/audit fact');assertSameValue([], $f->repository->acceptedCalls,'no accepted write');
    assertSameValue(0,$f->storage->beginCalls,'no stage');assertSameValue(0,$f->ids->rootCalls+$f->ids->revisionCalls,'no IDs');
    $a=$f->repository->attempts[0];assertSameValue([$f->command->requestId,18,$f->command->mode,4512,81,O\AssignmentOrderOriginalStatus::REJECTED,$reason,false,$f->options['clock']??'2026-09-02T09:15:30Z'],
        [$a->requestId,$a->actorUserId,$a->mode,$a->installationCaseId,$a->assignmentOrderId,$a->status,$a->reason,$a->retryable,$a->attemptedAt],'exact immutable AttemptCommit');
    $calls=$f->trace->calls;assertSameValue(true,array_search('stream.close',$calls,true)<array_search('clock',$calls,true),'cleanup before lazy clock');
    assertSameValue(true,array_search('clock',$calls,true)<array_search('attempt',$calls,true),'clock before attempt');
}
foreach([false,true] as $correction){
    $prefix=$correction?'correction':'initial';
    integrityCase($prefix.'-composition-valid-control',function()use($correction){$f=new S\OriginalIntegrityFixture(['correction'=>$correction]);$r=$f->run();assertSameValue(O\AssignmentOrderOriginalStatus::ACCEPTED,$r->status(),'canonical composition accepted');assertSameValue($correction?2:1,$r->revisionNumber(),'revision control');assertSameValue(1,$f->clock->calls,'one application clock');assertSameValue(1,count($f->repository->accepted),'one accepted fact');assertSameValue(0,$f->fresh->opens,'normal path no recovery');});
    foreach(['case-other'=>['case'=>9999],'case-zero'=>['case'=>0],'order-other'=>['order'=>82],'order-zero'=>['order'=>0]] as $name=>$changes)integrityCase($prefix.'-composition-protocol-'.$name,function()use($correction,$changes){$f=new S\OriginalIntegrityFixture(['correction'=>$correction,'composition'=>integrityComposition($changes,true)]);integrityFailure($f,$f->run());assertSameValue(0,$f->clock->calls,'bad echoed ownership no clock');});
    foreach([O\AssignmentOrderCompositionLookupStatus::NOT_FOUND,O\AssignmentOrderCompositionLookupStatus::UNAVAILABLE] as $status){
        foreach(['identity','hash','ids','engineer'] as $field)integrityCase($prefix.'-'.$status->value.'-contradictory-'.$field,function()use($correction,$status,$field){$empty=['status'=>$status,'identity'=>null,'hash'=>null,'ids'=>[],'engineer'=>null];$payload=['identity'=>'composition-81-v1','hash'=>str_repeat('a',64),'ids'=>[7001],'engineer'=>31];$empty[$field]=$payload[$field];$f=new S\OriginalIntegrityFixture(['correction'=>$correction,'composition'=>integrityComposition($empty)]);integrityFailure($f,$f->run());assertSameValue(0,$f->clock->calls,'contradictory snapshot no clock');});
        integrityCase($prefix.'-'.$status->value.'-empty-control',function()use($correction,$status){$f=new S\OriginalIntegrityFixture(['correction'=>$correction,'composition'=>integrityComposition(['status'=>$status,'identity'=>null,'hash'=>null,'ids'=>[],'engineer'=>null])]);$r=$f->run();if($status===O\AssignmentOrderCompositionLookupStatus::NOT_FOUND)integrityCompositionRejected($f,$r,O\AssignmentOrderOriginalReason::ORDER_NOT_FOUND);else{integrityFailure($f,$r);assertSameValue(0,$f->clock->calls,'unavailable no clock');}});
    }
    $invalid=[
        'identity-null'=>['identity'=>null],'identity-other-order'=>['identity'=>'composition-82-v1'],
        'identity-zero-version'=>['identity'=>'composition-81-v0'],'identity-padded-version'=>['identity'=>'composition-81-v01'],
        'identity-overflow-version'=>['identity'=>'composition-81-v9223372036854775808'],'identity-arbitrary'=>['identity'=>'arbitrary'],
        'engineer-null'=>['engineer'=>null],'engineer-zero'=>['engineer'=>0],'engineer-negative'=>['engineer'=>-1],
        'empty-members'=>['ids'=>[]],'unordered-members'=>['ids'=>[7002,7001]],'duplicate-members'=>['ids'=>[7001,7001]],
        'zero-member'=>['ids'=>[0,7001]],'negative-member'=>['ids'=>[-1,7001]],'string-member'=>['ids'=>['7001',7002]],
        'float-member'=>['ids'=>[7001.0,7002]],'boolean-member'=>['ids'=>[true,7002]],'associative-members'=>['ids'=>[1=>7001,2=>7002]],
    ];
    foreach($invalid as $name=>$changes)integrityCase($prefix.'-invalid-composition-'.$name,function()use($correction,$changes){$f=new S\OriginalIntegrityFixture(['correction'=>$correction,'composition'=>integrityComposition($changes,true)]);integrityCompositionRejected($f,$f->run(),O\AssignmentOrderOriginalReason::INVALID_COMPOSITION);});
    foreach(['null'=>null,'short'=>'abcd','uppercase'=>str_repeat('A',64),'plausible-wrong'=>str_repeat('a',64)] as $name=>$hash)integrityCase($prefix.'-composition-hash-'.$name,function()use($correction,$hash){$f=new S\OriginalIntegrityFixture(['correction'=>$correction,'composition'=>integrityComposition(['hash'=>$hash])]);integrityCompositionRejected($f,$f->run(),O\AssignmentOrderOriginalReason::INVALID_COMPOSITION);});
    integrityCase($prefix.'-composition-getter-throw',function()use($correction){$f=new S\OriginalIntegrityFixture(['correction'=>$correction,'composition'=>new RuntimeException('composition query')]);integrityFailure($f,$f->run());assertSameValue(0,$f->clock->calls,'query failure no clock');});
}
foreach([false,true] as $correction)foreach([O\AssignmentOrderCompositionLookupStatus::NOT_FOUND,O\AssignmentOrderCompositionLookupStatus::UNAVAILABLE] as $status){
    foreach(['case-other'=>['case'=>9999],'case-zero'=>['case'=>0],'order-other'=>['order'=>82],'order-zero'=>['order'=>0]] as $name=>$changes)integrityCase('negative-echo-'.(int)$correction.'-'.$status->value.'-'.$name,function()use($correction,$status,$changes){
        $snapshot=integrityComposition($changes+['status'=>$status,'identity'=>null,'hash'=>null,'ids'=>[],'engineer'=>null]);
        $f=new S\OriginalIntegrityFixture(['correction'=>$correction,'composition'=>$snapshot]);integrityFailure($f,$f->run());assertSameValue(0,$f->clock->calls,'negative snapshot echo corruption does not become business absence');
    });
}
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_DATA_COMPOSITION_OK');
