<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\AssignmentOrderComposition as C;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001 / SELECT-001 sections6/9/10: native controls.
$cases=[
    'missing_worker'=>["DELETE FROM fm2_workforce_catalog WHERE installer_tab_id=7001",'rejected','installer_not_in_catalog'],
    'dismissed_worker'=>["UPDATE fm2_workforce_catalog SET employment_status='dismissed' WHERE installer_tab_id=7001",'rejected','installer_not_employed'],
    'future_worker'=>["UPDATE fm2_workforce_catalog SET employed_from='2026-09-06' WHERE installer_tab_id=7001",'rejected','installer_not_employed'],
    'inactive_engineer'=>["UPDATE fm2_pilot_users SET status=0 WHERE user_id=73",'rejected','control_engineer_not_eligible'],
    'completed_precedes_pto'=>["UPDATE fm_maintable SET workdatefinish='2026-09-04',ptoactdate='2026-09-03'",'rejected','object_completed'],
    'pto'=>["UPDATE fm_maintable SET ptoactdate='2026-09-03'",'rejected','object_has_pto_act'],
    'malformed_object_date'=>["UPDATE fm_maintable SET ptoactdate='not-a-date'",'failed','dependency_unavailable'],
    'missing_catalog'=>['RENAME TABLE fm2_workforce_catalog TO fixture_missing_catalog','failed','dependency_unavailable'],
    'registry_capacity'=>['ALTER TABLE fm2_assignment_order_identities AUTO_INCREMENT=9223372036854775808','failed','allocation_capacity_exhausted'],
    'event_capacity'=>['ALTER TABLE fm2_assignment_order_selection_events AUTO_INCREMENT=9223372036854775808','failed','allocation_capacity_exhausted'],
    'audit_capacity'=>['ALTER TABLE fm2_assignment_order_selection_audits AUTO_INCREMENT=9223372036854775808','failed','allocation_capacity_exhausted'],
];
$failed=0;
function outcomeUnchanged(array $before,array $after,bool $terminal):void {
    foreach($before as $table=>$rows)if(!$terminal||!in_array($table,['fm2_assignment_order_selection_requests','fm2_assignment_order_selection_audits'],true))assertSameValue($rows,$after[$table],"preserved $table");
    if($terminal)assertSameValue([1,1],[count($after['fm2_assignment_order_selection_requests']),count($after['fm2_assignment_order_selection_audits'])],'one terminal request and audit');
}
foreach($cases as $name=>[$setup,$status,$reason]) {
    $f=null;$errors=[];
    try{$f=new F();$f->db->query($setup);$before=$f->rows();echo "SETUP_OK $name\n";$r=$f->app()->selectAssignmentOrderComposition(F::command());
        assertSameValue([$status,$reason,$status==='failed'&&$reason!=='allocation_capacity_exhausted',null],[$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->success()],'exact native outcome');outcomeUnchanged($before,$f->rows(),$status==='rejected');
    }catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}
foreach(['pending','stale','no_changes','no_selection','corrupt_request'] as $name) {
    $f=null;$errors=[];
    try{$f=new F();$app=$f->app();
        if($name!=='no_selection')assertSameValue('selected',$app->selectAssignmentOrderComposition(F::command())->status()->value,'accepted state setup');
        if($name==='corrupt_request')$f->db->query("UPDATE fm2_assignment_order_selection_requests SET operation_fingerprint=REPEAT('0',64)");
        $command=match($name){'stale'=>F::command(2),'no_changes','no_selection'=>F::command(2,1),'corrupt_request'=>F::command(),default=>new C\SelectAssignmentOrderCompositionCommand(F::command(2)->requestId,C\AssignmentOrderCompositionMode::NEW_ORDER,new C\InstallationObjectId(4512),new C\UserId(18),new C\InstallerTabIdList([new C\InstallerTabId(7002)]),new C\UserId(73),new C\SelectionRevision(1))};
        if($name==='no_selection')$command=new C\SelectAssignmentOrderCompositionCommand($command->requestId,$command->mode,$command->installationObjectId,$command->actorUserId,$command->installerTabIds,$command->controlEngineerUserId,new C\SelectionRevision(0));
        $before=$f->rows();echo "SETUP_OK $name\n";$r=$app->selectAssignmentOrderComposition($command);
        $expected=match($name){'pending'=>['conflict','pending_selection_exists'],'stale'=>['conflict','stale_selection'],'no_changes'=>['rejected','no_changes'],'no_selection'=>['conflict','selection_not_found'],'corrupt_request'=>['failed','dependency_unavailable']};
        assertSameValue([...$expected,$name==='corrupt_request',null],[$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->success()],'exact locked state outcome');
        $after=$f->rows();foreach($before as $table=>$rows)if(!in_array($table,['fm2_assignment_order_selection_requests','fm2_assignment_order_selection_audits'],true)||$name==='corrupt_request')assertSameValue($rows,$after[$table],"state preserves $table");
        foreach(['fm2_assignment_order_selection_requests','fm2_assignment_order_selection_audits'] as $table)assertSameValue(count($before[$table])+($name==='corrupt_request'?0:1),count($after[$table]),'terminal footprint only');
    }catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}exit($failed===0?0:1);
