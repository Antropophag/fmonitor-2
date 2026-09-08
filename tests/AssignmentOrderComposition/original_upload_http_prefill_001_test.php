<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{OriginalHttpFixture as F,SelectionHttpAssertions as A};
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\InstallationProcess\ProductionPdfAssignmentOrderRenderer;
// ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001: prior-day native template fact is a suggestion.
$f=null;
try {
    $f=new F();$native=$f->http->original->selection;$yesterday=(new DateTimeImmutable('yesterday',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');
    $clock=new class($yesterday) implements C\SelectionClock {public function __construct(private string $date){}public function now():C\SelectionInstantLookup{return C\SelectionInstantLookup::found(new C\SelectionInstant($this->date.'T09:00:00Z'));}};
    $renderer=new ProductionPdfAssignmentOrderRenderer();$template=C\AssignmentOrderTemplateVerificationFactory::create($native->db,'',$clock,$renderer->renderAssignmentOrder(...))->generateAssignmentOrderTemplate(4512,81,18);
    assertSameValue('generated',$template['status'],'approved native template clock fixture');assertSameValue($yesterday,$template['templateDate'],'prior-day date independent from current HTTP day');
    $form=$f->http->request('GET',F::FORM,'',31);assertSameValue(200,$form['status'],'INTENDED_RED manager original form');assertSameValue($yesterday,A::field($form['body'],'documentDate'),'actual last template date, not today');
    $dom=new DOMDocument();$dom->loadHTML($form['body'],LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);$x=new DOMXPath($dom);
    assertSameValue(1,$x->query('//form[@data-original-upload-form]')->length,'client handler attaches to actual form');
    assertSameValue(1,$x->query('//input[@type="file"][@name="original"][not(@multiple)]')->length,'single real file control');
    assertSameValue(1,$x->query('//input[@name="compositionConfirmed"][@type="checkbox"]')->length,'actual confirmation control');
    assertSameValue(true,str_contains($form['body'],'/pilot/assets/original-upload.js'),'real client asset wired');
    assertSameValue(200,$f->http->request('GET','/pilot/assets/original-upload.js')['status'],'client asset served');
    foreach(['csrfToken','requestId','mode','rootOriginalId','targetRevisionId','expectedCurrentRevisionId'] as $name)A::field($form['body'],$name);
    $native->db->query("DELETE FROM fm2_process_user_capabilities WHERE user_id=31 AND capability IN('assignment_order.original.upload','assignment_order.original.correct')");$fields=$f->fields();$fields['requestId']='22222222-2222-1222-8222-000000000001';$fields['documentDate']='2026-09-01';$r=F::json($f->post($fields,null,[],31),201);assertSameValue('accepted',$r['status'],'manager local role is native authority without legacy process capabilities');
    $form=$f->http->request('GET',F::FORM,'',31);assertSameValue('2026-09-01',A::field($form['body'],'documentDate'),'correction defaults original date, not template');
    echo "PASS native yesterday template prefill/manager/form asset contract\n";
}finally{if($f!==null)$f->close();}
