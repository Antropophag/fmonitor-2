<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\Tests\Support\SelectionCommandFixture as F;

// ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.11, Gate3 v1 requested recovery/clock gaps only.
function recoveryCommand():C\SelectAssignmentOrderCompositionCommand
{
    return new C\SelectAssignmentOrderCompositionCommand(new C\SelectionRequestId('00000000-0000-4000-8000-000000000001'),C\AssignmentOrderCompositionMode::NEW_ORDER,
        new C\InstallationObjectId(4512),new C\UserId(18),new C\InstallerTabIdList([new C\InstallerTabId(7001)]),new C\UserId(73),new C\SelectionRevision(0));
}
function recoveryRecord(string $kind='rejected'):C\SelectionTerminalRequestRecord
{
    $request=new C\SelectionRequestId('00000000-0000-4000-8000-000000000001');$id=$kind==='mismatch'?7002:7001;
    $literal=$kind==='mismatch'
        ?'{"actorUserId":18,"controlEngineerUserId":73,"expectedSelectionRevision":0,"installationObjectId":4512,"installerTabIds":[7002],"mode":"new_order"}'
        :'{"actorUserId":18,"controlEngineerUserId":73,"expectedSelectionRevision":0,"installationObjectId":4512,"installerTabIds":[7001],"mode":"new_order"}';
    $intent=new C\SelectionNormalizedIntent(18,73,0,4512,new C\InstallerTabIdSet([new C\InstallerTabId($id)]),C\AssignmentOrderCompositionMode::NEW_ORDER,$literal,hash('sha256',$literal));
    $result=$kind==='conflict'?C\SelectionResult::conflict($request,C\AssignmentOrderCompositionReason::PENDING_SELECTION_EXISTS)
        :C\SelectionResult::rejected($request,C\AssignmentOrderCompositionReason::OBJECT_NOT_FOUND);
    return new C\SelectionTerminalRequestRecord($request,$intent,$result);
}
function recoveryResult(C\AssignmentOrderCompositionResult $result,string $status,string $reason,bool $retryable):void
{
    assertSameValue([$status,$reason,$retryable,'00000000-0000-4000-8000-000000000001',null],
        [$result->status()->value,$result->reasonCode()?->value,$result->retryable(),$result->requestId()->value,$result->success()], 'exact non-disclosing recovery result');
}
function recoveryAudit(F $f,bool $mismatch):void
{
    assertSameValue([[],[],[]],[$f->selections,$f->events,$f->requests],'no loser business facts');
    assertSameValue($mismatch?1:0,count($f->audits),'only mismatched tuple gets independent audit');
    if($mismatch){$a=$f->audits[0];assertSameValue(['00000000-0000-4000-8000-000000000001',18,4512,'new_order','conflict','request_id_conflict','2026-09-05T09:00:00Z'],
        [$a->requestId->value,$a->actor->value,$a->objectId->value,$a->mode->value,$a->status->value,$a->reason?->value,$a->attemptedAt->utcRfc3339Seconds],'safe conflict audit uses original invocation instant');}
}
$cases=[];
foreach(['unknown','race'] as $route){foreach(['rejected','conflict','mismatch'] as $kind){$cases[$route.' stored '.$kind]=static function()use($route,$kind):void{
    $f=new F();if($route==='unknown')$f->outcome='unknown-absent';else $f->stageResponse=C\SelectionStageResult::requestRace();
    $record=recoveryRecord($kind);$f->freshResponse=C\SelectionTerminalRequestLookup::found($record);$before=serialize($record);
    $result=$f->app()->selectAssignmentOrderComposition(recoveryCommand());
    recoveryResult($result,$kind==='rejected'?'rejected':'conflict',match($kind){'rejected'=>'object_not_found','conflict'=>'pending_selection_exists',default=>'request_id_conflict'},false);
    recoveryAudit($f,$kind==='mismatch');assertSameValue($before,serialize($record),'external winning terminal preserved');
    assertSameValue([1,1],[$f->allocations,$f->clockCalls],'one reservation and one attempt instant');
    assertSameValue(2,count(array_filter($f->trace,static fn($v)=>$v==='authorize')),'recovery reauthorizes');
    assertSameValue(1,count(array_filter($f->trace,static fn($v)=>$v==='fresh-close')),'fresh reader closes once');
};}}
foreach(['observed','race-match','race-mismatch','race-unavailable','unknown-absent','unknown-unavailable'] as $route){$cases['no-case '.$route]=static function()use($route):void{
    $f=new F();$f->caseExists=false;$record=recoveryRecord($route==='race-mismatch'?'mismatch':'rejected');
    $f->terminalOutcome=$route==='observed'?C\SelectionUnitOfWorkResult::observedTerminal($record)
        :(str_starts_with($route,'race')?C\SelectionUnitOfWorkResult::requestRace():C\SelectionUnitOfWorkResult::outcomeUnknown());
    $f->freshResponse=str_ends_with($route,'unavailable')?C\SelectionTerminalRequestLookup::unavailable()
        :($route==='unknown-absent'?C\SelectionTerminalRequestLookup::notFound():C\SelectionTerminalRequestLookup::found($record));
    $result=$f->app()->selectAssignmentOrderComposition(recoveryCommand());
    $status=match($route){'observed','race-match'=>'rejected','race-mismatch'=>'conflict',default=>'failed'};
    $reason=match($route){'observed','race-match'=>'object_not_found','race-mismatch'=>'request_id_conflict','unknown-absent'=>'persistence_failure',default=>'persistence_outcome_unknown'};
    recoveryResult($result,$status,$reason,$status==='failed');recoveryAudit($f,$route==='race-mismatch');
    assertSameValue([0,1],[$f->allocations,$f->clockCalls],'no fabricated case allocation or second clock');
    assertSameValue(false,in_array('begin',$f->trace,true),'never enters case UoW');
    assertSameValue($route==='observed'?0:1,count(array_filter($f->trace,static fn($v)=>$v==='fresh-close')),'observed terminal needs no second reader, recovery closes its reader');
};}
foreach(['unavailable','notFound','malformed'] as $kind){$cases['denied clock '.$kind]=static function()use($kind):void{
    $f=new F();$f->authorization=C\SelectionAuthorizationStatus::DENIED;
    $f->clockResponse=$kind==='malformed'?C\SelectionInstantLookup::found(new C\SelectionInstant('2026-02-31T00:00:00Z')):C\SelectionInstantLookup::$kind();
    recoveryResult($f->app()->selectAssignmentOrderComposition(recoveryCommand()),'failed','dependency_unavailable',true);
    assertSameValue(['authorize','clock'],$f->trace,'unavailable attempt time before any audit or confidential read');
    recoveryAudit($f,false);assertSameValue(1,$f->clockCalls,'one failed clock acquisition');
};}
$failures=0;foreach($cases as $name=>$test){try{assertSameValue(true,is_callable([C\AssignmentOrderCompositionFactory::class,'create']),'RED_ASSERTION: public selection application factory is missing');$test();echo "PASS $name\n";}catch(Throwable $e){$failures++;echo "FAIL $name: ".$e->getMessage()."\n";}}
echo 'SELECTION_COMMAND_RECOVERY passed='.(count($cases)-$failures).' failed='.$failures."\n";exit($failures===0?0:1);
