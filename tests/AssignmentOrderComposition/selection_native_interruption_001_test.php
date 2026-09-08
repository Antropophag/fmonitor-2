<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\Tests\Support\SelectionNativeAcceptedExample as Example;
use FMonitor2\AssignmentOrderComposition as C;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001: real connection interruption, no proxy/fault port.
$failed=0;
foreach([true,false] as $strict){$f=null;$runtime=null;$errors=[];$report=(new mysqli_driver())->report_mode;
    try {
        $f=new F();$before=$f->rows();$runtime=$f->schema->source->connect($f->schema->source->name);$thread=$runtime->thread_id;
        $ports=C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($runtime,fn()=>$f->schema->source->connect($f->schema->source->name));$stage=null;$killed=false;$warnings=[];
        mysqli_report($strict?MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT:MYSQLI_REPORT_OFF);
        set_error_handler(static function(int $level,string $message)use(&$warnings):bool{$warnings[]=$level;return true;});
        try {
            $callback=function(C\SelectionTransactionSession $s)use($f,$thread,&$stage,&$killed):C\SelectionTransactionDecision {
                $a=$s->allocateIdentity(C\SelectionSourceKind::SELECTION,new C\SelectionInstant('2026-09-05T09:00:00Z'));assertSameValue(C\SelectionIdentityAllocationStatus::ALLOCATED,$a->status,'real identity staged');
                $p=Example::payload($a->allocation);$stage=$s->stageAccepted($p)->status;
                // Only this fixture-created, dedicated runtime connection is interrupted.
                $killed=$f->db->query('KILL CONNECTION '.$thread)===true;
                return C\SelectionTransactionDecision::commit($p->selectedResult);
            };
            $work=new class($callback) implements C\SelectionTransactionalWork {public function __construct(private Closure $callback){}public function run(C\SelectionTransactionSession $s):C\SelectionTransactionDecision{return ($this->callback)($s);}};
            echo 'SETUP_OK '.($strict?'exception':'boolean')."\n";$r=$ports->transactions->executeForCase(4512,$work);
        }finally{restore_error_handler();mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);}
        assertSameValue([C\SelectionStageStatus::STAGED,true],[$stage,$killed],'native writes precede actual owned connection interruption');
        assertSameValue('outcomeUnknown',$r->kind(),'unconfirmed native commit/rollback never claims known result');
        assertSameValue($before,$f->rows(),'server rollback leaves no partial business facts');
        $reader=$ports->freshReaders->open();try{assertSameValue(C\SelectionLookupStatus::NOT_FOUND,$reader->findTerminalRequest(F::command()->requestId)->status,'fresh independent snapshot proves absent terminal');}finally{$reader->close();}
        assertSameValue($before,$f->rows(),'recovery lookup is read-only, no blind allocation retry');
        $retry=$f->app()->selectAssignmentOrderComposition(F::command());assertSameValue(['selected',82],[$retry->status()->value,$retry->success()?->assignmentOrderId],'explicit later invocation uses next identity after one native gap');
    }catch(Throwable $e){$errors[]=$e->getMessage();}
    if($runtime!==null)try{$runtime->close();}catch(Throwable $e){$errors[]='runtime cleanup: '.$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK\n";}catch(Throwable $e){$errors[]='db cleanup: '.$e->getMessage();}
    mysqli_report($report);
    if($errors!==[]){$failed++;echo 'FAIL '.($strict?'exception':'boolean').': '.implode(' | ',$errors)."\n";}else echo 'PASS '.($strict?'exception':'boolean')."\n";
}exit($failed===0?0:1);
