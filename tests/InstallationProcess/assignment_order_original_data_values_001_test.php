<?php

declare(strict_types=1);
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

// DATA-INTEGRITY-001 v0.6 sections2–5: foreign public values, no private implementation call.
integrityCase('public-complete-lineage-contract',function(){
    assertSameValue(true,interface_exists(O\AssignmentOrderOriginalCompleteLineageLookup::class),'INTENDED_RED: complete metadata interface');
    $r=new ReflectionClass(O\AssignmentOrderOriginalCompleteLineageLookup::class);
    foreach(['installationCaseId'=>'?int','assignmentOrderId'=>'?int','revisionIds'=>'array'] as $name=>$type)assertSameValue($type,(string)$r->getMethod($name)->getReturnType(),'exact metadata type '.$name);
});
integrityCase('literal-pdf-control',function(){assertSameValue(327,strlen(S\OriginalIntegrityFixture::pdf()),'fixed independent PDF size');assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',hash('sha256',S\OriginalIntegrityFixture::pdf()),'fixed independent PDF hash');});
foreach(['terminal','fingerprint'] as $position){
    foreach(O\AssignmentOrderOriginalLookupStatus::cases() as $status)foreach([false,true] as $payload){
        integrityCase($position.'-closed-'.$status->value.'-'.(int)$payload,function()use($position,$status,$payload){
            $lookup=new S\OriginalIntegrityLookup($status,$payload?new S\OriginalIntegrityResult():null);$f=new S\OriginalIntegrityFixture([$position=>$lookup]);$r=$f->run();
            if(($status===O\AssignmentOrderOriginalLookupStatus::FOUND)!==$payload||$status===O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE)integrityFailure($f,$r,streamed:$position==='fingerprint');
            else{assertSameValue($payload?O\AssignmentOrderOriginalStatus::REPLAYED:O\AssignmentOrderOriginalStatus::ACCEPTED,$r->status(),'valid lookup control outcome');assertSameValue(1,$f->stream->closeCalls,'control closes supplied stream');}
            assertSameValue(1,$lookup->statusCalls,'status snapshot once');assertSameValue(1,$lookup->resultCalls,'payload snapshot once even negative');
        });
    }
    foreach(['status','result'] as $getter)foreach(O\AssignmentOrderOriginalLookupStatus::cases() as $status){
        integrityCase($position.'-lookup-throw-'.$status->value.'-'.$getter,function()use($position,$getter,$status){$lookup=new S\OriginalIntegrityLookup($status,$status===O\AssignmentOrderOriginalLookupStatus::FOUND?new S\OriginalIntegrityResult():null,$getter);$f=new S\OriginalIntegrityFixture([$position=>$lookup]);integrityFailure($f,$f->run(),streamed:$position==='fingerprint');});
    }
    $bad=[
        'failed-status'=>['status'=>O\AssignmentOrderOriginalStatus::FAILED], 'stored-replayed'=>['status'=>O\AssignmentOrderOriginalStatus::REPLAYED],
        'accepted-reason'=>['reason'=>O\AssignmentOrderOriginalReason::INVALID_PDF], 'retryable'=>['retryable'=>true],
        'bad-request'=>['request'=>'not-a-uuid'],'upper-request'=>['request'=>'ABCDEF00-0000-4000-8000-000000000001'],
        'root-empty'=>['root'=>''],'root-slash'=>['root'=>'root/bad'],'root-too-long'=>['root'=>str_repeat('r',81)],
        'revision-empty'=>['revision'=>''],'revision-backslash'=>['revision'=>'revision\\bad'],
        'number-zero'=>['number'=>0],'number-overflow'=>['number'=>4294967296],
        'date-normalized'=>['date'=>'2026-02-31'],'date-zero'=>['date'=>'0000-01-01'],
        'hash-uppercase'=>['sha'=>str_repeat('A',64)],'hash-short'=>['sha'=>'abcd'],
        'size-zero'=>['size'=>0],'size-overflow'=>['size'=>20971521],
        'time-normalized'=>['at'=>'2026-02-31T09:15:30Z'],'time-fraction'=>['at'=>'2026-09-02T09:15:30.000Z'],'time-offset'=>['at'=>'2026-09-02T09:15:30+00:00'],
    ];
    foreach(['root','revision','number','date','sha','size','at'] as $field)$bad['null-'.$field]=[$field=>null];
    foreach($bad as $name=>$values)integrityCase($position.'-result-'.$name,function()use($position,$values){$v=new S\OriginalIntegrityResult($values);$f=new S\OriginalIntegrityFixture([$position=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);integrityFailure($f,$f->run(),streamed:$position==='fingerprint');});
    foreach(['status','reason','retryable','request','root','revision','number','date','sha','size','at'] as $field)integrityCase($position.'-getter-throw-'.$field,function()use($position,$field){$v=new S\OriginalIntegrityResult([],$field);$f=new S\OriginalIntegrityFixture([$position=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);integrityFailure($f,$f->run(),streamed:$position==='fingerprint');});
    integrityCase($position.'-valid-snapshot-once',function()use($position){$v=new S\OriginalIntegrityResult();$f=new S\OriginalIntegrityFixture([$position=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);assertSameValue(['replayed',null,false,'00000000-0000-4000-8000-000000000001','original-0001','revision-0001',1,'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z'],integrityTuple($f->run()),'exact immutable winner snapshot');$counts=$v->gets;ksort($counts);$expected=array_fill_keys(['status','reason','retryable','request','root','revision','number','date','sha','size','at'],1);ksort($expected);assertSameValue($expected,$counts,'each Result getter exactly once');assertSameValue([], $f->repository->accepted,'no write on replay');assertSameValue([], $f->repository->attempts,'no audit on replay');});
}
integrityCase('terminal-foreign-UUID-fails',function(){$v=new S\OriginalIntegrityResult(['request'=>'00000000-0000-4000-8000-000000000999']);$f=new S\OriginalIntegrityFixture(['terminal'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);integrityFailure($f,$f->run());});
integrityCase('fingerprint-foreign-UUID-replays-current-request',function(){$v=new S\OriginalIntegrityResult(['request'=>'00000000-0000-4000-8000-000000000999']);$f=new S\OriginalIntegrityFixture(['fingerprint'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);$r=$f->run();assertSameValue(O\AssignmentOrderOriginalStatus::REPLAYED,$r->status(),'cross-request replay');assertSameValue($f->command->requestId,$r->requestId(),'current request correlation');assertSameValue([], $f->repository->accepted,'no loser terminal');assertSameValue([], $f->repository->attempts,'no loser audit');});
$rejected=[O\AssignmentOrderOriginalReason::AUTHORIZATION_DENIED,O\AssignmentOrderOriginalReason::ORDER_NOT_FOUND,O\AssignmentOrderOriginalReason::COMPOSITION_NOT_CONFIRMED,O\AssignmentOrderOriginalReason::INVALID_COMPOSITION,O\AssignmentOrderOriginalReason::FILE_TOO_LARGE,O\AssignmentOrderOriginalReason::NOT_PDF,O\AssignmentOrderOriginalReason::INVALID_PDF,O\AssignmentOrderOriginalReason::UNSAFE_PDF,O\AssignmentOrderOriginalReason::FUTURE_DOCUMENT_DATE,O\AssignmentOrderOriginalReason::NO_CHANGES];
$conflicts=[O\AssignmentOrderOriginalReason::SEMANTIC_COLLISION,O\AssignmentOrderOriginalReason::STALE_REVISION,O\AssignmentOrderOriginalReason::TARGET_NOT_FOUND,O\AssignmentOrderOriginalReason::TARGET_NOT_CURRENT,O\AssignmentOrderOriginalReason::INITIAL_ALREADY_EXISTS];
foreach(array_merge($rejected,$conflicts) as $reason)integrityCase('terminal-reason-control-'.$reason->value,function()use($reason,$conflicts){$v=S\OriginalIntegrityResult::rejected($reason);if(in_array($reason,$conflicts,true))$v->values['status']=O\AssignmentOrderOriginalStatus::CONFLICT;$f=new S\OriginalIntegrityFixture(['terminal'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);$r=$f->run();assertSameValue($v->values['status'],$r->status(),'stored terminal status');assertSameValue($reason,$r->reasonCode(),'stored terminal reason');assertSameValue(0,$f->clock->calls,'replay no clock');assertSameValue(0,$f->stream->readCalls,'replay no read');assertSameValue(1,$f->stream->closeCalls,'replay close');assertSameValue([], $f->repository->attempts,'no replay audit');});
foreach(['invalid-command','wrong-rejected-reason','wrong-conflict-reason','rejected-retryable','rejected-evidence','rejected-no-reason'] as $mode)integrityCase('terminal-invalid-'.$mode,function()use($mode){$v=S\OriginalIntegrityResult::rejected();switch($mode){case 'invalid-command':$v->values['reason']=O\AssignmentOrderOriginalReason::INVALID_COMMAND;break;case 'wrong-rejected-reason':$v->values['reason']=O\AssignmentOrderOriginalReason::STALE_REVISION;break;case 'wrong-conflict-reason':$v->values['status']=O\AssignmentOrderOriginalStatus::CONFLICT;break;case 'rejected-retryable':$v->values['retryable']=true;break;case 'rejected-evidence':$v->values['root']='original-0001';break;case 'rejected-no-reason':$v->values['reason']=null;break;}$f=new S\OriginalIntegrityFixture(['terminal'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);integrityFailure($f,$f->run());});
foreach([O\AssignmentOrderOriginalStatus::REJECTED,O\AssignmentOrderOriginalStatus::CONFLICT] as $status)integrityCase('fingerprint-nonaccepted-'.$status->value,function()use($status){$v=S\OriginalIntegrityResult::rejected($status===O\AssignmentOrderOriginalStatus::CONFLICT?O\AssignmentOrderOriginalReason::STALE_REVISION:O\AssignmentOrderOriginalReason::INVALID_PDF);$v->values['status']=$status;$f=new S\OriginalIntegrityFixture(['fingerprint'=>new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::FOUND,$v)]);integrityFailure($f,$f->run(),streamed:true);});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_DATA_VALUES_OK');
