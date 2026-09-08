<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectedOriginalFixture as F;
use FMonitor2\Tests\Support\SelectedOriginalInput as Input;
use FMonitor2\Tests\Support\SelectionNativeFixture;

// ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001 v0.1: real selection -> original.
$failed=0;
foreach([false,true] as $production){$f=null;$errors=[];$name=$production?'production constructor':'fixed-clock verification constructor';
    try {
        $f=new F();$selected=$f->selection->app()->selectAssignmentOrderComposition(SelectionNativeFixture::command());
        assertSameValue(['selected',81,'composition-81-v1','5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a'],[$selected->status()->value,$selected->success()?->assignmentOrderId,$selected->success()?->compositionIdentity,$selected->success()?->compositionSha256],'real native selection prerequisite');
        $before=$f->selection->rows();assertSameValue([], $before['fm2_assignment_orders'],'no physical preparation');assertSameValue([], $f->privateFiles(),'no template files');
        echo "SETUP_OK $name\n";$app=$f->app($production);$stream=new Input(F::pdf());$start=time();$r=$app->submitAssignmentOrderOriginal(F::command($stream));$end=time();
        assertSameValue(['accepted',null,false,1,'2026-09-04','78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62',327],[$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize()],'direct original accepted from selected composition');
        assertSameValue(true,$stream->reads>0,'real PDF consumed');assertSameValue(1,$stream->closes,'original input closed');
        if(!$production)assertSameValue('2026-09-05T09:00:00Z',$r->uploadedAt(),'injected original clock');else assertSameValue(true,strtotime($r->uploadedAt())>=$start&&strtotime($r->uploadedAt())<=$end,'production uses real system clock');
        $after=$f->selection->rows();$allowed=['fm2_assignment_order_original_roots','fm2_assignment_order_original_revisions','fm2_assignment_order_original_requests','fm2_assignment_order_original_events','fm2_assignment_order_original_audits'];
        foreach($before as $table=>$rows)if(!in_array($table,$allowed,true))assertSameValue($rows,$after[$table],"original does not mutate selection/opening/assignment: $table");
        foreach($allowed as $table)assertSameValue(1,count($after[$table]),"one atomic original fact: $table");
        $root=$after[$allowed[0]][0];$revision=$after[$allowed[1]][0];assertSameValue([$r->rootOriginalId(),'4512','81',$r->currentRevisionId(),'composition-81-v1','5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a'],array_slice(array_values($root),0,6),'root owns exact immutable selected composition');
        assertSameValue([$r->currentRevisionId(),$r->rootOriginalId(),'1',null,'2026-09-04'],array_slice(array_values($revision),0,5),'initial revision lineage and document date');
        $files=$f->privateFiles();$pdfs=array_filter($files,fn($hash,$path)=>str_ends_with($path,'.pdf'),ARRAY_FILTER_USE_BOTH);assertSameValue(['78162c8976f51bd62ed4c49dc4d9dc8884b839de770f6b447449c55305e1bb62'],array_values($pdfs),'one stored signed original with exact bytes');
        $retry=new Input('must not be inspected');$clock=$f->clockCalls;$replay=$app->submitAssignmentOrderOriginal(F::command($retry));
        assertSameValue(['replayed',$r->rootOriginalId(),$r->currentRevisionId(),$r->uploadedAt()],[$replay->status()->value,$replay->rootOriginalId(),$replay->currentRevisionId(),$replay->uploadedAt()],'silent exact request replay');
        assertSameValue([0,1],[$retry->reads,$retry->closes],'replay closes without reading stream');assertSameValue($clock,$f->clockCalls,'replay has no new clock');assertSameValue($after,$f->selection->rows(),'replay no duplicate facts/audit');assertSameValue($files,$f->privateFiles(),'replay no duplicate storage');
    }catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}exit($failed===0?0:1);
