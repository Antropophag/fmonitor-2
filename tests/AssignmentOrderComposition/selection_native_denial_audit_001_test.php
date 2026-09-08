<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\AssignmentOrderComposition as C;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001 inherits SELECT-001 sections5/8/10:
// every denied invocation audits independently, never reads a confidential terminal result.
function denialOutcome(C\AssignmentOrderCompositionResult $r):array {return [$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->success()===null?null:get_object_vars($r->success())];}
function denialRowsUnchanged(array $before,array $after):void {
    foreach($before as $table=>$rows)if($table!=='fm2_assignment_order_selection_audits')assertSameValue($rows,$after[$table],"no domain/request mutation: $table");
}
function denyGrant(F $f):void {$f->db->query("DELETE FROM fm2_pilot_role_permissions WHERE role_id=1 AND permission='assignment_order.composition.select'");}
function deniedAuditFacts(F $f,array $before):void {
    $after=$f->rows();denialRowsUnchanged($before,$after);$audits=$after['fm2_assignment_order_selection_audits'];
    assertSameValue(3,count($audits),'one accepted and two independent denial audits');
    assertSameValue($before['fm2_assignment_order_selection_audits'][0],$audits[0],'accepted audit preserved');
    foreach(array_slice($audits,1) as $a)assertSameValue(['11111111-1111-4111-8111-000000000001','18','4512','new_order','rejected','authorization_denied','2026-09-05 09:00:00.000000'],array_slice(array_values($a),1),'exact safe denial audit, no composition/result details');
}
$tests=[
    'revocation audits each invocation and restoration silently replays'=>static function(F $f):void {
        $app=$f->app();$command=F::command();$selected=$app->selectAssignmentOrderComposition($command);assertSameValue('selected',$selected->status()->value,'setup accepted selection');
        denyGrant($f);$before=$f->rows();
        for($n=0;$n<2;$n++)assertSameValue(['rejected','authorization_denied',false,null],denialOutcome($app->selectAssignmentOrderComposition($command)),'denied invocation');
        deniedAuditFacts($f,$before);$f->schema->insert('fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'assignment_order.composition.select']);$before=$f->rows();$clock=$f->clockCalls;
        $r=$app->selectAssignmentOrderComposition($command);assertSameValue(['replayed',null,false,get_object_vars($selected->success())],denialOutcome($r),'restored exact replay');
        assertSameValue($before,$f->rows(),'restored replay silent');assertSameValue($clock,$f->clockCalls,'restored replay has no clock');
    },
    'denial audit independent of malformed confidential request'=>static function(F $f):void {
        $app=$f->app();$command=F::command();assertSameValue('selected',$app->selectAssignmentOrderComposition($command)->status()->value,'setup accepted selection');
        // Controlled corruption in a task-owned fixture; never repair it as part of the command.
        $f->db->query("UPDATE fm2_assignment_order_selection_requests SET operation_fingerprint=REPEAT('0',64)");denyGrant($f);$before=$f->rows();
        for($n=0;$n<2;$n++)assertSameValue(['rejected','authorization_denied',false,null],denialOutcome($app->selectAssignmentOrderComposition($command)),'denial must not depend on stored request integrity');
        deniedAuditFacts($f,$before);
    },
    'changed intent appends safe conflict audit only'=>static function(F $f):void {
        $app=$f->app();assertSameValue('selected',$app->selectAssignmentOrderComposition(F::command())->status()->value,'setup accepted selection');$before=$f->rows();
        assertSameValue(['conflict','request_id_conflict',false,null],denialOutcome($app->selectAssignmentOrderComposition(F::command(1,0,7002))),'changed tuple no disclosure');
        $after=$f->rows();denialRowsUnchanged($before,$after);assertSameValue(2,count($after['fm2_assignment_order_selection_audits']),'one extra conflict audit');
        assertSameValue(['conflict','request_id_conflict'],array_values(array_intersect_key($after['fm2_assignment_order_selection_audits'][1],array_flip(['status','reason_code']))),'exact conflict audit');
    },
    'audit schema drift never acknowledges successful denial audit'=>static function(F $f):void {
        $app=$f->app();denyGrant($f);$f->db->query('ALTER TABLE fm2_assignment_order_selection_audits ADD COLUMN fixture_drift INT NULL');$before=$f->rows();
        assertSameValue(['failed','persistence_failure',true,null],denialOutcome($app->selectAssignmentOrderComposition(F::command())),'cannot acknowledge incompatible audit storage');
        assertSameValue($before,$f->rows(),'failed audit leaves no facts');
    },
];
$failed=0;
foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F();echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}
exit($failed===0?0:1);
