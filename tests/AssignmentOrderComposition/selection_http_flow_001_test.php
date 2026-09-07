<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{SelectionHttpFixture as F,SelectionHttpAssertions as A,SelectedOriginalFixture,SelectedOriginalInput,TemplateGenerationFixture};
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\AssignmentOrderComposition as C;
// ASSIGNMENT-ORDER-COMPOSITION-HTTP-001 v0.1: real public HTTP/native owners.
$f=null;
try {
    $f=new F();$native=$f->original->selection;$before=$native->rows();$files=$f->original->privateFiles();
    $form=$f->request('GET',A::PATH);assertSameValue(200,$form['status'],'INTENDED_RED native selection form missing after real session setup');A::html($form);
    foreach(['Инженер теста','data-installer-dialog','data-installer-search','data-installer-results','data-search-url="/pilot/objects/4512/assignment-order/installers"','shlz-checkbox','shlz-radio'] as $marker)assertSameValue(true,str_contains($form['body'],$marker),'lazy picker/public control '.$marker);
    foreach(['Монтажник 7001','Монтажник 7002','synthetic-hr']as$marker)assertSameValue(false,str_contains($form['body'],$marker),'initial form does not dump catalogue '.$marker);
    assertSameValue(['0','new_order'],[A::field($form['body'],'expectedSelectionRevision'),A::field($form['body'],'mode')],'empty form exact intent');
    assertSameValue(1,preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',A::field($form['body'],'requestId')),'fresh request key');
    assertSameValue($before,$native->rows(),'GET writes no facts');
    assertSameValue('',$f->request('HEAD',A::PATH)['body'],'HEAD empty');
    $submitted=A::submission($form['body'],['installerTabIds[]'=>['7001'],'controlEngineerUserId'=>['73'],'controlEngineerConfirmed'=>['yes']]);
    $selected=$f->request('POST',A::PATH,$submitted);assertSameValue(303,$selected['status'],'native select redirect');
    assertSameValue(A::PATH,$selected['headers']['location']??null,'PRG destination');
    $read=O\AssignmentOrderRegisteredCompositionReaderFactory::create($native->db)->find(4512,81);
    assertSameValue(['found',81,[7001],73],[$read->status->value,$read->assignmentOrderId,$read->installerIds,$read->controlEngineerUserId],'worked example native identity');
    $after=$native->rows();$allowed=['fm2_assignment_order_identities','fm2_assignment_order_selections','fm2_assignment_order_selection_members','fm2_assignment_order_selection_requests','fm2_assignment_order_selection_events','fm2_assignment_order_selection_audits'];
    A::rowsPreserved($before,$after,$allowed);assertSameValue($files,$f->original->privateFiles(),'selection creates no files');
    assertSameValue(303,$f->request('POST',A::PATH,$submitted)['status'],'semantic HTTP replay');assertSameValue($after,$native->rows(),'replay silent');
    $form=$f->request('GET',A::PATH);A::html($form);assertSameValue(['1','replace_pending'],[A::field($form['body'],'expectedSelectionRevision'),A::field($form['body'],'mode')],'pending form exact intent');
    assertSameValue(true,str_contains($form['body'],'/assignment-orders/81/template'),'optional PDF separate endpoint');
    assertSameValue(303,$f->request('POST',A::PATH,A::body(A::input($f,2,1,7002,'replace_pending')))['status'],'pending replacement');
    $read=O\AssignmentOrderRegisteredCompositionReaderFactory::create($native->db)->find(4512,82);
    assertSameValue(['found',82,[7002]],[$read->status->value,$read->assignmentOrderId,$read->installerIds],'replacement immutable identity');
    $new=$native->rows();foreach($allowed as $table)assertSameValue(true,in_array($after[$table][0],$new[$table],true),'prior fact retained '.$table);
    A::rowsPreserved($before,$new,$allowed);
    $stale=$f->request('POST',A::PATH,A::body(A::input($f,3,0)));assertSameValue(409,$stale['status'],'stale selection');assertSameValue(true,str_contains($stale['body'],'stale_selection'),'domain reason retained');
    $prePdf=$native->rows();$today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');
    $pdf=$f->request('POST','/pilot/objects/4512/assignment-orders/82/template',http_build_query(['csrfToken'=>$f->csrf]));
    assertSameValue(200,$pdf['status'],'real native template HTTP');assertSameValue('application/pdf',$pdf['headers']['content-type']??null,'PDF media');
    assertSameValue((string)strlen($pdf['body']),$pdf['headers']['content-length']??null,'exact PDF length');assertSameValue(true,str_starts_with($pdf['body'],'%PDF-'),'real PDF');
    assertSameValue(true,str_contains($pdf['headers']['content-disposition']??'',"filename*=UTF-8''"),'RFC5987 attachment');
    TemplateGenerationFixture::pdfMarker($pdf['body'],'Монтажник 7002');TemplateGenerationFixture::pdfMarker($pdf['body'],'Инженер теста');
    assertSameValue(['status'=>'found','date'=>$today],C\ProductionAssignmentOrderTemplateFactory::dateReader($native->db)->find(4512,82),'authorized HTTP generated date');
    A::rowsPreserved($prePdf,$native->rows(),['fm2_process_events']);assertSameValue($files,$f->original->privateFiles(),'PDF not stored');
    assertSameValue(true,str_contains($f->request('GET',A::PATH)['body'],$today),'last generation date visible');
    $pdf2=$f->request('POST','/pilot/objects/4512/assignment-orders/82/template',http_build_query(['csrfToken'=>$f->csrf]));assertSameValue(200,$pdf2['status'],'repeated PDF');
    assertSameValue(count($prePdf['fm2_process_events'])+2,count($native->rows()['fm2_process_events']),'each template audited once');
    $rows=$native->rows();assertSameValue(409,$f->request('POST','/pilot/objects/4512/assignment-orders/81/template',http_build_query(['csrfToken'=>$f->csrf]))['status'],'stale template target');assertSameValue($rows,$native->rows(),'stale template no facts');
    // Existing public original command supplies the predecessor fact for next new_order.
    $command=new O\SubmitAssignmentOrderOriginalCommand('22222222-2222-4222-8222-000000000082',O\AssignmentOrderOriginalMode::INITIAL,4512,82,18,'2026-09-04',true,null,null,null,null,new O\AssignmentOrderOriginalUpload(new SelectedOriginalInput(SelectedOriginalFixture::pdf()),'signed.pdf','application/pdf'));
    $accepted=$f->original->app()->submitAssignmentOrderOriginal($command);assertSameValue('accepted',$accepted->status()->value,'accepted original prerequisite');
    $form=$f->request('GET',A::PATH);assertSameValue('new_order',A::field($form['body'],'mode'),'accepted original permits next new order intent');
    assertSameValue(303,$f->request('POST',A::PATH,A::body(A::input($f,4,2,7001,'new_order')))['status'],'new_order after accepted original');
    assertSameValue('found',O\AssignmentOrderRegisteredCompositionReaderFactory::create($native->db)->find(4512,83)->status->value,'next native identity');
    $nextForm=$f->request('GET',A::PATH);A::html($nextForm);assertSameValue(false,str_contains($nextForm['body'],$today),'new83 has no inherited previous-order template date');
    echo "PASS selection/replay/replace_pending/PDF/accepted-root new_order native HTTP\n";
}finally{if($f!==null)$f->close();}
