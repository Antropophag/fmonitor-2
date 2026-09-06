<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\TemplateGenerationFixture as F;
use FMonitor2\Tests\Support\SelectedOriginalFixture;
use FMonitor2\Tests\Support\SelectedOriginalInput;

// ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001 v0.2: real renderer, only generation audit persists.
function templateSuccess(array $r,string $date):void {
    assertSameValue(['status','reasonCode','assignmentOrderId','templateDate','filename','mediaType','bytes'],array_keys($r),'closed response keys');
    assertSameValue(['generated',null,81,$date,'Распоряжение о закреплении монтажников.pdf','application/pdf'],array_slice(array_values($r),0,6),'exact generated envelope');assertSameValue(true,str_starts_with($r['bytes'],'%PDF-'),'actual PDF bytes');
    foreach(['Монтажник 7001','Инженер теста','Тестовая улица'] as $text)F::pdfMarker($r['bytes'],$text);
}
function templateOnlyAudit(array $before,array $after):void {
    assertSameValue(array_keys($before),array_keys($after),'no new schema');foreach($before as $table=>$rows)if($table!=='fm2_process_events')assertSameValue($rows,$after[$table],"no template/domain storage: $table");
}
$tests=[
    'today repeat failure and direct original'=>static function(F $f):void {
        $app=$f->app();$dates=$f->dates();assertSameValue(['status'=>'not_found','date'=>null],$dates->find(4512,81),'no prior generation');$before=$f->original->selection->rows();$files=$f->original->privateFiles();
        $r=$app->generateAssignmentOrderTemplate(4512,81,18);templateSuccess($r,'2026-09-06');F::pdfMarker($r['bytes'],'сентября');F::pdfMarker($r['bytes'],'06');
        assertSameValue('2026-09-06',$f->inputs[0]['assignmentOrderDate'],'Moscow date passed to existing renderer');assertSameValue('2026-12-20',$f->inputs[0]['installationObjectSnapshot']['plannedFinishDate'],'planned date fallback');
        $after=$f->original->selection->rows();templateOnlyAudit($before,$after);assertSameValue(1,count($after['fm2_process_events']),'one generation audit');$event=$after['fm2_process_events'][0];
        assertSameValue(['4512','assignment_order_template_generated','2026-09-05T21:30:00Z','18','{"assignmentOrderId":81,"assignmentOrderVersion":1,"compositionIdentity":"composition-81-v1","compositionSha256":"5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a","templateDate":"2026-09-06"}'],array_slice(array_values($event),1),'exact metadata-only audit');
        assertSameValue(['status'=>'found','date'=>'2026-09-06'],$dates->find(4512,81),'last date projection');assertSameValue($files,$f->original->privateFiles(),'no stored template PDF');
        $f->at='2026-09-06T21:30:00Z';$r=$app->generateAssignmentOrderTemplate(4512,81,18);templateSuccess($r,'2026-09-07');F::pdfMarker($r['bytes'],'07');$after2=$f->original->selection->rows();templateOnlyAudit($before,$after2);assertSameValue(2,count($after2['fm2_process_events']),'repeat creates a new audit, not a PDF version');assertSameValue($event,$after2['fm2_process_events'][0],'prior audit immutable');assertSameValue(['status'=>'found','date'=>'2026-09-07'],$dates->find(4512,81),'next-day date replaces prefill projection');assertSameValue(2,count($f->inputs),'real renderer called again');
        $f->at='2026-09-07T21:30:00Z';$f->renderFails=true;$failure=$app->generateAssignmentOrderTemplate(4512,81,18);
        assertSameValue(['status'=>'failed','reasonCode'=>'render_failure','assignmentOrderId'=>null,'templateDate'=>null,'filename'=>null,'mediaType'=>null,'bytes'=>null],$failure,'render failure has no output');assertSameValue($after2,$f->original->selection->rows(),'failure no event/date mutation');assertSameValue(['status'=>'found','date'=>'2026-09-07'],$dates->find(4512,81),'last successful date remains');assertSameValue($files,$f->original->privateFiles(),'no successful or failed template file storage');
        $original=$f->original->app()->submitAssignmentOrderOriginal(SelectedOriginalFixture::command(new SelectedOriginalInput(SelectedOriginalFixture::pdf())));assertSameValue('accepted',$original->status()->value,'direct original remains possible after renderer failure');
    },
    'production constructor uses native renderer and current date'=>static function(F $f):void {
        $before=$f->original->selection->rows();$dateBefore=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');$r=$f->app(true)->generateAssignmentOrderTemplate(4512,81,18);$dateAfter=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');
        assertSameValue(true,in_array($r['templateDate']??null,[$dateBefore,$dateAfter],true),'production current Moscow date');templateSuccess($r,$r['templateDate']);templateOnlyAudit($before,$f->original->selection->rows());assertSameValue(['status'=>'found','date'=>$r['templateDate']],$f->dates()->find(4512,81),'production audit projects date');assertSameValue([],$f->original->privateFiles(),'production creates no PDF files');
    },
];
$failed=0;foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F();echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}exit($failed===0?0:1);
