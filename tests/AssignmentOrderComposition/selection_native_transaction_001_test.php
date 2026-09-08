<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\AssignmentOrderComposition as C;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001 / SELECT-001 section9: real typed UoW contract.
function transactionPorts(F $f):C\SelectionDependencies {return C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($f->db,fn()=>$f->schema->source->connect($f->schema->source->name));}
function transactionWork(Closure $run):C\SelectionTransactionalWork {return new class($run) implements C\SelectionTransactionalWork {
    public function __construct(private Closure $callback){} public function run(C\SelectionTransactionSession $s):C\SelectionTransactionDecision{return ($this->callback)($s);}
};}
function transactionTerminal():C\SelectionTerminalAttemptPersistence {
    $c=F::command(2);$c=new C\SelectAssignmentOrderCompositionCommand($c->requestId,$c->mode,$c->installationObjectId,$c->actorUserId,new C\InstallerTabIdList([]),$c->controlEngineerUserId,$c->expectedSelectionRevision);
    $r=C\SelectionResult::rejected($c->requestId,C\AssignmentOrderCompositionReason::INSTALLER_REQUIRED);
    $a=new C\SelectionSafeAttemptAudit($c->requestId,$c->actorUserId,$c->installationObjectId,$c->mode,$r->status(),$r->reasonCode(),new C\SelectionInstant('2026-09-05T09:00:00Z'));
    return new C\SelectionTerminalAttemptPersistence($c->requestId,C\SelectionIntent::fromCommand($c),$r,$a);
}
function transactionRolledBack(C\SelectionUnitOfWorkResult $r,string $cause='persistence_failure'):void {assertSameValue(['rolledBack',$cause,null],[$r->kind(),$r->rollbackCause()?->value,$r->result()],'exact confirmed rollback');}
$tests=[
    'terminal stage rejects selected result before mutation'=>static function(F $f):void {
        $accepted=$f->app()->selectAssignmentOrderComposition(F::command());assertSameValue('selected',$accepted->status()->value,'real selected reference');$c=F::command(2);
        $r=C\SelectionResult::selected($c->requestId,$accepted->success());$a=new C\SelectionSafeAttemptAudit($c->requestId,$c->actorUserId,$c->installationObjectId,$c->mode,$r->status(),null,new C\SelectionInstant('2026-09-05T09:00:00Z'));
        $p=new C\SelectionTerminalAttemptPersistence($c->requestId,C\SelectionIntent::fromCommand($c),$r,$a);$before=$f->rows();$status=null;$inside=null;
        $unit=transactionPorts($f)->transactions->executeForCase(4512,transactionWork(function($s)use($f,$p,&$status,&$inside){$status=$s->stageTerminalAttempt($p)->status;$inside=$f->rows();return C\SelectionTransactionDecision::commit($p->terminalResult);}));
        assertSameValue(C\SelectionStageStatus::PERSISTENCE_ERROR,$status,'terminal-only stage must reject selected');assertSameValue($before,$inside,'invalid stage never inserts even uncommitted rows');transactionRolledBack($unit);assertSameValue($before,$f->rows(),'no false selected request committed');
    },
    'commit without stage rejected'=>static function(F $f):void {
        $p=transactionTerminal();$before=$f->rows();$r=transactionPorts($f)->transactions->executeForCase(4512,transactionWork(fn($s)=>C\SelectionTransactionDecision::commit($p->terminalResult)));
        transactionRolledBack($r);assertSameValue($before,$f->rows(),'no unstaged commit facts');
    },
    'valid staged terminal rolls back atomically with typed cause'=>static function(F $f):void {
        $p=transactionTerminal();$before=$f->rows();$status=null;$staged=null;
        $r=transactionPorts($f)->transactions->executeForCase(4512,transactionWork(function($s)use($f,$p,&$status,&$staged){$status=$s->stageTerminalAttempt($p)->status;$staged=$f->rows();return C\SelectionTransactionDecision::rollback(C\SelectionRollbackCause::DEPENDENCY_UNAVAILABLE);}));
        assertSameValue(C\SelectionStageStatus::STAGED,$status,'real terminal staged');assertSameValue([1,1],[count($staged['fm2_assignment_order_selection_requests']),count($staged['fm2_assignment_order_selection_audits'])],'both rows existed inside transaction');
        transactionRolledBack($r,'dependency_unavailable');assertSameValue($before,$f->rows(),'rollback removes request and audit');
    },
    'second stage invalidates commit'=>static function(F $f):void {
        $p=transactionTerminal();$before=$f->rows();$statuses=[];
        $r=transactionPorts($f)->transactions->executeForCase(4512,transactionWork(function($s)use($p,&$statuses){$statuses[]=$s->stageTerminalAttempt($p)->status;$statuses[]=$s->stageTerminalAttempt($p)->status;return C\SelectionTransactionDecision::commit($p->terminalResult);}));
        assertSameValue([C\SelectionStageStatus::STAGED,C\SelectionStageStatus::PERSISTENCE_ERROR],$statuses,'exactly one successful stage');transactionRolledBack($r);assertSameValue($before,$f->rows(),'double stage cannot retain first write');
    },
    'wrong audit echo rejected before mutation'=>static function(F $f):void {
        $p=transactionTerminal();$a=$p->audit;$bad=new C\SelectionSafeAttemptAudit($a->requestId,new C\UserId(19),$a->objectId,$a->mode,$a->status,$a->reason,$a->attemptedAt);$p=new C\SelectionTerminalAttemptPersistence($p->requestId,$p->intent,$p->terminalResult,$bad);$before=$f->rows();$status=null;$inside=null;
        $r=transactionPorts($f)->transactions->executeForCase(4512,transactionWork(function($s)use($f,$p,&$status,&$inside){$status=$s->stageTerminalAttempt($p)->status;$inside=$f->rows();return C\SelectionTransactionDecision::commit($p->terminalResult);}));
        assertSameValue(C\SelectionStageStatus::PERSISTENCE_ERROR,$status,'audit actor must echo intent');assertSameValue($before,$inside,'bad echo has no staged writes');transactionRolledBack($r);assertSameValue($before,$f->rows(),'bad echo rollback');
    },
    'no-case UoW rejects other business reason'=>static function(F $f):void {
        $before=$f->rows();transactionRolledBack(transactionPorts($f)->terminalAttempts->execute(transactionTerminal()));assertSameValue($before,$f->rows(),'no-case port restricted to object_not_found');
    },
    'ambient caller transaction remains caller-owned'=>static function(F $f):void {
        $app=$f->app();$before=$f->rows();$f->db->begin_transaction();
        try{$f->db->query("INSERT INTO other_prefix_marker VALUES(2,'caller-transaction')");$r=$app->selectAssignmentOrderComposition(F::command());
            assertSameValue(['failed','dependency_unavailable'],[$r->status()->value,$r->reasonCode()?->value],'ambient invocation unavailable');
            assertSameValue('1',(string)$f->db->query('SELECT @@in_transaction')->fetch_row()[0],'caller transaction still active');assertSameValue('2',(string)$f->db->query('SELECT COUNT(*) FROM other_prefix_marker')->fetch_row()[0],'caller uncommitted row retained');
        }finally{$f->db->rollback();}
        assertSameValue($before,$f->rows(),'caller rollback still controls its write');
    },
];
$failed=0;foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F();echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}exit($failed===0?0:1);
