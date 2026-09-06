<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\Tests\Support\SelectionCommandFixture as Fixture;

// ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.11, fresh-launch core tracer.
// Scope: both modes, immutable replay, no changes, and no-case terminal persistence.
function selectionCommand(int $request=1,string $mode='new_order',array $ids=[7001],int $revision=0): C\SelectAssignmentOrderCompositionCommand
{
    return new C\SelectAssignmentOrderCompositionCommand(new C\SelectionRequestId(sprintf('00000000-0000-4000-8000-%012d',$request)),C\AssignmentOrderCompositionMode::from($mode),
        new C\InstallationObjectId(4512),new C\UserId(18),new C\InstallerTabIdList(array_map(static fn($id)=>new C\InstallerTabId($id),$ids)),new C\UserId(73),new C\SelectionRevision($revision));
}
function selectionTuple(C\AssignmentOrderCompositionResult $r):array
{
    $p=$r->success();return[$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->requestId()->value,
        $p?->caseId,$p?->assignmentOrderId,$p?->assignmentOrderVersion,$p?->selectionRevision,$p?->compositionIdentity,$p?->compositionSha256,$p?->selectionDate,$p?->selectedAt];
}
$cases=[];
$cases['new order and immutable replay']=static function():void{
    $f=new Fixture();$app=$f->app();assertSameValue([],$f->trace,'construction performs no I/O');$command=selectionCommand();$result=$app->selectAssignmentOrderComposition($command);
    assertSameValue(['selected',null,false,'00000000-0000-4000-8000-000000000001',4512,81,1,1,'composition-81-v1','5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a','2026-09-05','2026-09-05T09:00:00Z'],selectionTuple($result),'fixed initial selection');
    assertSameValue([1,1,1,1,1],[count($f->selections),count($f->events),count($f->audits),$f->allocations,$f->clockCalls],'one atomic selected fact set');
    $stored=$f->selections[0];assertSameValue('62ee3be1c62ff977fc0e508a4743d85b8f5bc83386b3b8b0c26f542d321aff6c',$stored->intent->fingerprint,'independent intent digest');
    assertSameValue([null,null],[$stored->previousSelectionOrderId,$stored->replacesSelectionOrderId],'first selection has no predecessors');
    assertSameValue('2026-09-05T09:00:00Z',$stored->allocation->allocatedAt->utcRfc3339Seconds,'allocation shares invocation instant');
    $before=serialize([$f->selections,$f->events,$f->audits,$f->requests]);$f->trace=[];$replay=$app->selectAssignmentOrderComposition($command);
    $expected=selectionTuple($result);$expected[0]='replayed';assertSameValue($expected,selectionTuple($replay),'replay returns original frozen result');
    assertSameValue(['authorize','request'],$f->trace,'replay bypasses clock facts transaction and allocation');
    assertSameValue($before,serialize([$f->selections,$f->events,$f->audits,$f->requests]),'replay writes nothing');
};
$cases['replace pending keeps prior selection']=static function():void{
    $f=new Fixture();$app=$f->app();$app->selectAssignmentOrderComposition(selectionCommand());$before=serialize($f->selections[0]);
    $result=$app->selectAssignmentOrderComposition(selectionCommand(2,'replace_pending',[7002],1));
    assertSameValue(['selected',null,false,'00000000-0000-4000-8000-000000000002',4512,82,2,2,'composition-82-v2','1e6e0030b9f3cca92c36a741f87331f652490391ae773386673dc32f838d8bda','2026-09-05','2026-09-05T09:00:00Z'],selectionTuple($result),'fixed replacement');
    assertSameValue([81,81],[$f->selections[1]->previousSelectionOrderId,$f->selections[1]->replacesSelectionOrderId],'replacement points to exact previous pending identity');
    assertSameValue($before,serialize($f->selections[0]),'old selection immutable');assertSameValue([2,2,2,2],[count($f->selections),count($f->events),count($f->audits),$f->allocations],'replacement appends one fact set');
};
$cases['same pending composition rejected without allocation']=static function():void{
    $f=new Fixture();$app=$f->app();$app->selectAssignmentOrderComposition(selectionCommand());$before=serialize([$f->selections,$f->events]);
    $result=$app->selectAssignmentOrderComposition(selectionCommand(2,'replace_pending',[7001],1));
    assertSameValue(['rejected','no_changes',false,'00000000-0000-4000-8000-000000000002',null,null,null,null,null,null,null,null],selectionTuple($result),'same composition is not a new selection');
    assertSameValue($before,serialize([$f->selections,$f->events]),'no new selection/event');assertSameValue([1,2,2],[$f->allocations,count($f->requests),count($f->audits)],'rejection stores terminal and audit only');
};
$cases['absent case caches terminal without fabricated case']=static function():void{
    $f=new Fixture();$f->caseExists=false;$app=$f->app();$command=selectionCommand();$result=$app->selectAssignmentOrderComposition($command);
    assertSameValue(['rejected','object_not_found',false,'00000000-0000-4000-8000-000000000001',null,null,null,null,null,null,null,null],selectionTuple($result),'no-case business result');
    assertSameValue(['authorize','request','clock','case','terminal-without-case'],$f->trace,'dedicated atomic no-case path');
    assertSameValue([0,0,1,1],[$f->allocations,count($f->selections),count($f->requests),count($f->audits)],'only request and audit');
    $f->trace=[];assertSameValue(selectionTuple($result),selectionTuple($app->selectAssignmentOrderComposition($command)),'no-case rejection replay');assertSameValue(['authorize','request'],$f->trace,'cached rejection does not probe object again');
};
$failed=0;
foreach($cases as $name=>$case){
    try{assertSameValue(true,is_callable([C\AssignmentOrderCompositionFactory::class,'create']),'RED_ASSERTION: public selection application factory is missing');$case();echo "PASS $name\n";}
    catch(Throwable $error){$failed++;echo "FAIL $name: ".$error->getMessage()."\n";}
}
echo 'SELECTION_COMMAND_TRACER passed='.(count($cases)-$failed).' failed='.$failed."\n";exit($failed===0?0:1);
