<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001 v0.1; Gate1 0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09.
$failures=[];
function nativeResult(C\AssignmentOrderCompositionResult $result): array { return (new C\SelectionResultSerializer())->serialize($result); }
function nativeUnchanged(array $before,array $after,array $allowed): void {
    foreach($before as $table=>$rows) if(!in_array($table,$allowed,true)) assertSameValue($rows,$after[$table],"unrelated rows preserved: $table");
}
function nativeRead(F $f,int $order,array $installers): void {
    $r=O\AssignmentOrderRegisteredCompositionReaderFactory::create($f->db)->find(4512,$order);
    assertSameValue(['found',4512,$order,"composition-$order-v".($order-80),$installers,73],
        [$r->status->value,$r->installationCaseId,$r->assignmentOrderId,$r->identity,$r->installerIds,$r->controlEngineerUserId],'registered public reader exact composition');
}
$tests=[
    'new_order and silent replay'=>static function(F $f):void {
        $before=$f->rows();$app=$f->app();$command=F::command();$r=nativeResult($app->selectAssignmentOrderComposition($command));
        $expected=['status'=>'selected','reasonCode'=>null,'retryable'=>false,'requestId'=>'11111111-1111-4111-8111-000000000001',
            'caseId'=>4512,'assignmentOrderId'=>81,'assignmentOrderVersion'=>1,'selectionRevision'=>1,'compositionIdentity'=>'composition-81-v1',
            'compositionSha256'=>'5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a','selectionDate'=>'2026-09-05','selectedAt'=>'2026-09-05T09:00:00Z'];
        assertSameValue($expected,$r,'literal core section4 success');$after=$f->rows();
        $tables=['fm2_assignment_order_identities','fm2_assignment_order_selections','fm2_assignment_order_selection_members','fm2_assignment_order_selection_requests','fm2_assignment_order_selection_events','fm2_assignment_order_selection_audits'];
        foreach($tables as $table) assertSameValue(1,count($after[$table]),"one committed fact: $table");
        nativeUnchanged($before,$after,$tables);
        assertSameValue(['81','4512','1','selection','2026-09-05 09:00:00.000000'],array_values($after[$tables[0]][0]),'exact registry row');
        assertSameValue(['7001','Монтажник 7001','Монтажник','employed','2020-01-01',null,'synthetic-hr','2026-09-01T06:00:00Z'],array_slice(array_values($after[$tables[2]][0]),1),'immutable workforce snapshot');
        assertSameValue('62ee3be1c62ff977fc0e508a4743d85b8f5bc83386b3b8b0c26f542d321aff6c',$after[$tables[3]][0]['operation_fingerprint'],'literal request digest');
        assertSameValue(true,I\AssignmentOrderSelectionSchemaMigration::isReady($f->db),'persisted cross-table coherence');
        I\AssignmentOrderSelectionSchemaMigrationVerification::snapshot($f->db,'');nativeRead($f,81,[7001]);
        $expected['status']='replayed';assertSameValue($expected,nativeResult($app->selectAssignmentOrderComposition($command)),'exact replay');
        assertSameValue($after,$f->rows(),'replay changes no rows');assertSameValue(1,$f->clockCalls,'replay does not acquire clock');
    },
    'replace_pending retains first selection'=>static function(F $f):void {
        $app=$f->app();assertSameValue('selected',$app->selectAssignmentOrderComposition(F::command())->status()->value,'first selected');
        $before=$f->rows();$r=$app->selectAssignmentOrderComposition(F::command(2,1,7002));
        assertSameValue(['selected',null,82,2,2],[$r->status()->value,$r->reasonCode(),$r->success()?->assignmentOrderId,$r->success()?->assignmentOrderVersion,$r->success()?->selectionRevision],'replacement new immutable identity');
        $after=$f->rows();$allowed=['fm2_assignment_order_identities','fm2_assignment_order_selections','fm2_assignment_order_selection_members','fm2_assignment_order_selection_requests','fm2_assignment_order_selection_events','fm2_assignment_order_selection_audits'];
        nativeUnchanged($before,$after,$allowed);
        foreach($allowed as $table) { assertSameValue(2,count($after[$table]),"two facts: $table"); assertSameValue(true,in_array($before[$table][0],$after[$table],true),"old immutable fact: $table"); }
        $new=array_values(array_filter($after['fm2_assignment_order_selections'],fn($row)=>$row['assignment_order_id']==='82'))[0];
        assertSameValue(['replace_pending','81','81','7002'],[$new['mode'],$new['previous_selection_order_id'],$new['replaces_selection_order_id'],$after['fm2_assignment_order_selection_members'][1]['installer_tab_id']],'replacement links and new member');
        nativeRead($f,81,[7001]);nativeRead($f,82,[7002]);
        assertSameValue(true,I\AssignmentOrderSelectionSchemaMigration::isReady($f->db),'replacement ledger coherent');
    },
    'missing object terminal request without fake case'=>static function(F $f):void {
        $before=$f->rows();$app=$f->app();$command=F::command(3,0,7001,9999);$r=$app->selectAssignmentOrderComposition($command);
        assertSameValue(['rejected','object_not_found',false,null],[$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->success()],'missing object exact terminal');
        $after=$f->rows();nativeUnchanged($before,$after,['fm2_assignment_order_selection_requests','fm2_assignment_order_selection_audits']);
        $request=$after['fm2_assignment_order_selection_requests'];$audit=$after['fm2_assignment_order_selection_audits'];
        assertSameValue([1,1],[count($request),count($audit)],'one terminal and one audit');
        assertSameValue(['9999',null,null,'object_not_found'],[$request[0]['installation_object_id'],$request[0]['case_id'],$request[0]['assignment_order_id'],$request[0]['reason_code']],'no fictional case/success');
        assertSameValue(['18','9999','new_order','rejected','object_not_found','2026-09-05 09:00:00.000000'],array_slice(array_values($audit[0]),2),'exact safe audit fields');
        assertSameValue(nativeResult($r),nativeResult($app->selectAssignmentOrderComposition($command)),'cached missing object outcome');
        assertSameValue($after,$f->rows(),'terminal replay silent');assertSameValue(1,$f->clockCalls,'one attempt instant');
    },
];
foreach($tests as $name=>$test) {
    $f=null;$errors=[];
    try { $f=new F();echo "SETUP_OK $name\n";$test($f); } catch(Throwable $e) { $errors[]=$e->getMessage(); }
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]) {$failures[]=$name;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}
exit($failures===[]?0:1);
