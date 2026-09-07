<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{OriginalHttpFixture as F,SelectionHttpAssertions as A,SelectedOriginalFixture};
// ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 v0.3: actual selected native identity and raw HTTP.
$f=null;
try {
    $f=new F();$native=$f->http->original->selection;$today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');
    $before=$native->rows();$form=$f->http->request('GET',F::FORM);
    assertSameValue(200,$form['status'],'INTENDED_RED original submission form missing after native selection');
    $csp="default-src 'none'; style-src 'self'; script-src 'self'; connect-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
    assertSameValue($csp,$form['headers']['content-security-policy']??null,'exact narrow form CSP');
    $head=$f->http->request('HEAD',F::FORM);assertSameValue(200,$head['status'],'HEAD status');
    foreach(['content-type','content-length','cache-control','x-content-type-options','content-security-policy','referrer-policy','x-frame-options','permissions-policy','cross-origin-opener-policy'] as $header)
        assertSameValue($form['headers'][$header]??null,$head['headers'][$header]??null,'GET/HEAD header parity '.$header);
    assertSameValue('',$head['body'],'HEAD emits no bytes');
    assertSameValue($today,A::field($form['body'],'documentDate'),'direct original defaults today');
    assertSameValue('initial',A::field($form['body'],'mode'),'initial form');
    assertSameValue(true,str_contains($form['body'],'Монтажник 7001'),'selected immutable crew visible');
    assertSameValue('',$f->http->request('HEAD',F::FORM)['body'],'HEAD empty');assertSameValue($before,$native->rows(),'form writes nothing');
    $metadata=$f->fields();$result=F::json($f->post($metadata),201);
    assertSameValue(['status','reasonCode','retryable','requestId','rootOriginalId','currentRevisionId','revisionNumber','documentDate','sha256','byteSize','uploadedAt'],array_keys($result),'exact11 field domain result');
    assertSameValue(['accepted',null,false,$metadata['requestId'],1,'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327],
        [$result['status'],$result['reasonCode'],$result['retryable'],$result['requestId'],$result['revisionNumber'],$result['documentDate'],$result['sha256'],$result['byteSize']],'independent command example');
    assertSameValue(1,preg_match('/^root-[0-9a-f]{32}$/D',$result['rootOriginalId']),'native generated root identity');
    assertSameValue(1,preg_match('/^revision-[0-9a-f]{32}$/D',$result['currentRevisionId']),'native generated revision identity');
    $allowed=['fm2_assignment_order_original_roots','fm2_assignment_order_original_revisions','fm2_assignment_order_original_requests','fm2_assignment_order_original_audits','fm2_assignment_order_original_events'];
    $accepted=$native->rows();A::rowsPreserved($before,$accepted,$allowed);$stored=$f->http->original->privateFiles();
    $replay=F::json($f->post($metadata),200);$expected=$result;$expected['status']='replayed';assertSameValue($expected,$replay,'semantic replay exact fields');
    assertSameValue($accepted,$native->rows(),'accepted replay preserves all facts');assertSameValue($stored,$f->http->original->privateFiles(),'replay preserves original bytes');
    $form=$f->http->request('GET',F::FORM);assertSameValue(200,$form['status'],'accepted form');
    assertSameValue(['correction','2026-09-01',$result['currentRevisionId']],[A::field($form['body'],'mode'),A::field($form['body'],'documentDate'),A::field($form['body'],'targetRevisionId')],'correction prefill exact current original');
    $correction=$f->fields(2);$correction['mode']='correction';$correction['documentDate']='2026-09-02';$correction['rootOriginalId']=$result['rootOriginalId'];
    $correction['targetRevisionId']=$correction['expectedCurrentRevisionId']=$result['currentRevisionId'];$correction['correctionReason']='Исправлена дата по оригиналу';
    $next=F::json($f->post($correction),201);assertSameValue(['accepted',2,'2026-09-02',$result['rootOriginalId']],[$next['status'],$next['revisionNumber'],$next['documentDate'],$next['rootOriginalId']],'append-only correction');
    assertSameValue(true,in_array($accepted['fm2_assignment_order_original_revisions'][0],$native->rows()['fm2_assignment_order_original_revisions'],true),'revision1 preserved');
    A::rowsPreserved($before,$native->rows(),$allowed);
    $correction['requestId']=$f->fields(3)['requestId'];$stale=F::json($f->post($correction),409);assertSameValue('stale_revision',$stale['reasonCode'],'stale correction exact native reason');
    // New pending selection must not prevent historical accepted-root correction.
    assertSameValue(303,$f->http->request('POST',A::PATH,A::body(A::input($f->http,8,1,7002,'new_order')))['status'],'next pending selected through HTTP');
    $correction['requestId']=$f->fields(4)['requestId'];$correction['targetRevisionId']=$correction['expectedCurrentRevisionId']=$next['currentRevisionId'];$correction['documentDate']='2026-09-03';
    assertSameValue(3,F::json($f->post($correction),201)['revisionNumber'],'historical original correction after new pending');
    echo "PASS direct original/replay/correction/historical binding/no apply-or-open\n";
}finally{if($f!==null)$f->close();}
