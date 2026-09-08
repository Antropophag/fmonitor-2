<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\Tests\Support\SelectionNativeAcceptedExample as Example;
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\InstallationProcess as I;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001 / SELECT-001 sections6/7/9: pre-write validation.
$failed=0;
foreach(['valid','wrong_date','future_employment','dismissed','bad_engineer','bad_source_time'] as $axis) {
    $f=null;$errors=[];
    try {
        $f=new F();$before=$f->rows();$insideBefore=null;$insideAfter=null;$status=null;
        $ports=C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($f->db,fn()=>$f->schema->source->connect($f->schema->source->name));
        $callback=function(C\SelectionTransactionSession $s)use($f,$axis,&$insideBefore,&$insideAfter,&$status):C\SelectionTransactionDecision {
            $allocation=$s->allocateIdentity(C\SelectionSourceKind::SELECTION,new C\SelectionInstant('2026-09-05T09:00:00Z'));
            assertSameValue(C\SelectionIdentityAllocationStatus::ALLOCATED,$allocation->status,'native reservation succeeded');
            $p=Example::payload($allocation->allocation,$axis);$insideBefore=$f->rows();$status=$s->stageAccepted($p)->status;$insideAfter=$f->rows();
            return C\SelectionTransactionDecision::commit($p->selectedResult);
        };
        $work=new class($callback) implements C\SelectionTransactionalWork {public function __construct(private Closure $callback){}public function run(C\SelectionTransactionSession $s):C\SelectionTransactionDecision{return ($this->callback)($s);}};
        echo "SETUP_OK $axis\n";$result=$ports->transactions->executeForCase(4512,$work);
        if($axis==='valid') {
            assertSameValue([C\SelectionStageStatus::STAGED,'committed','selected'],[$status,$result->kind(),$result->result()?->status()->value],'valid accepted control commits');
            assertSameValue(true,I\AssignmentOrderSelectionSchemaMigration::isReady($f->db),'valid accepted facts coherent');
        }else {
            assertSameValue(C\SelectionStageStatus::PERSISTENCE_ERROR,$status,"invalid $axis rejected");
            assertSameValue($insideBefore,$insideAfter,'invalid accepted payload performs no stage writes after reservation');
            assertSameValue(['rolledBack','persistence_failure'],[$result->kind(),$result->rollbackCause()?->value],'invalid accepted cannot commit');
            assertSameValue($before,$f->rows(),'reservation and all facts rolled back');
        }
    }catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $axis\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $axis: ".implode(' | ',$errors)."\n";}else echo "PASS $axis\n";
}exit($failed===0?0:1);
