<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{OriginalHttpFixture as F,SelectedOriginalFixture,AssignmentOrderOriginalPdfCorpus as P,SelectionHttpAssertions as A};
use FMonitor2\AssignmentOrderOriginal as O;
// ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 v0.2; SAPI rejection != successful upload or skip.
$f=null;
try {
    $maxFixture=SelectedOriginalFixture::pdf().str_repeat(' ',20971520-strlen(SelectedOriginalFixture::pdf()));
    assertSameValue(O\AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,(new O\FMonitorPassivePdfInspector())->inspect($maxFixture)->status,'SETUP_OK exact20MiB passive PDF');unset($maxFixture);
    $f=new F();$native=$f->http->original->selection;$fields=$f->fields();
    foreach([[strlen(SelectedOriginalFixture::pdf())+1,SelectedOriginalFixture::pdf()],[3,SelectedOriginalFixture::pdf()]] as [$declared,$body]){
        $before=$native->rows();$r=$f->post($fields,$body,['Content-Length'=>(string)$declared]);
        assertSameValue([0,true],[$r['status'],$r['connectionClosed']??false],'native malformed frame closed without timeout');
        assertSameValue(303,$f->http->request('GET','/pilot/login')['status'],'server healthy after framing rejection');assertSameValue($before,$native->rows(),'framing never reaches command');
    }
    $before=$native->rows();$frame=$f->post($fields,null,['Transfer-Encoding'=>'chunked']);
    assertSameValue([0,true],[$frame['status'],$frame['connectionClosed']??false],'malformed chunked frame closed before PHP');
    assertSameValue(303,$f->http->request('GET','/pilot/login')['status'],'server healthy after malformed chunking');assertSameValue($before,$native->rows(),'chunking rejection no command');
    $before=$native->rows();$r=$f->post($fields,str_repeat('x',20971521));assertSameValue(['error'=>'REQUEST_TOO_LARGE'],F::json($r,413),'exact one byte over HTTP cap');assertSameValue($before,$native->rows(),'oversize no native facts');
    foreach([
        ['not_pdf','not a PDF',[]],
        ['unsafe_pdf',P::forbidden('JavaScript'),[]],
        ['future_document_date',SelectedOriginalFixture::pdf(),['documentDate'=>'2099-01-01']],
        ['composition_not_confirmed',SelectedOriginalFixture::pdf(),['compositionConfirmed'=>false]],
    ] as $i=>[$reason,$bytes,$override]){
        $before=$native->rows();$data=array_replace($f->fields(10+$i),$override);$r=F::json($f->post($data,$bytes),422);assertSameValue($reason,$r['reasonCode'],'native rejection mapping');
        A::rowsPreserved($before,$native->rows(),['fm2_assignment_order_original_requests','fm2_assignment_order_original_audits']);
    }
    $private=$f->http->original->privateRoot;$held=dirname($private).'/held-private';rename($private,$held);
    try{assertSameValue(['error'=>'SERVICE_UNAVAILABLE'],F::json($f->post($f->fields(20)),503),'unavailable owned storage configuration');}finally{rename($held,$private);}
    // Independently built valid corpus at the exact accepted boundary.
    $bytes=SelectedOriginalFixture::pdf().str_repeat(' ',20971520-strlen(SelectedOriginalFixture::pdf()));
    assertSameValue(O\AssignmentOrderOriginalPdfStatus::PASSIVE_PDF,(new O\FMonitorPassivePdfInspector())->inspect($bytes)->status,'max-size fixture remains a real passive PDF');
    $r=F::json($f->post($f->fields(21),$bytes),201);assertSameValue(20971520,$r['byteSize'],'exact20MiB accepted');assertSameValue(hash('sha256',$bytes),$r['sha256'],'received bytes unchanged');
    echo "PASS framing/body cap/native rejections/storage unavailable/exact20MiB\n";
}finally{if($f!==null)$f->close();}
