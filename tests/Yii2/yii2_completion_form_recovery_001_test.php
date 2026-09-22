<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/DocumentaryFixture.php';

// YII2-COMPLETION-FORM-RECOVERY-001 A1-A4.
// Public seam: authenticated Yii completion POST and the rendered object card.
$fixture=null;
try {
    $fixture=new DocumentaryFixture(dirname(__DIR__,2));
    $fixture->open();
    $fixture->progress();
    $http=$fixture->http;
    $future=(new DateTimeImmutable('tomorrow',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');

    $assertRejectedForm=static function(array $response,string $action,array $values,string $field,string $message,bool $open=false):void {
        assertSameValue(422,$response['status'],$action.' keeps validation HTTP status');
        assertSameValue(true,str_contains(implode(' ',$response['headers']['content-type']??[]),'text/html'),$action.' returns object-card HTML');
        $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$response['body']);$xpath=new DOMXPath($dom);
        $forms=$xpath->query('//form[@data-completion-form="'.$action.'"]');
        assertSameValue(1,$forms->length,$action.' exact submitted form is rendered');
        $form=$forms->item(0);
        foreach($values as $name=>$value){
            $nodes=$xpath->query('.//*[@name="'.$name.'"]',$form);assertSameValue(1,$nodes->length,$action.' retains '.$name);
            $actual=$nodes->item(0)->nodeName==='textarea'?$nodes->item(0)->textContent:$nodes->item(0)->getAttribute('value');
            assertSameValue($value,$actual,$action.' exact retained '.$name);
        }
        $target=$xpath->query('.//*[@name="'.$field.'" and @aria-invalid="true" and @aria-describedby]',$form);
        assertSameValue(1,$target->length,$action.' field error association');
        $errorId=$target->item(0)->getAttribute('aria-describedby');
        $error=$xpath->query('.//*[@id="'.$errorId.'" and (@role="alert" or @role="status")]',$form);
        assertSameValue(1,$error->length,$action.' accessible field error');assertSameValue(true,str_contains($error->item(0)->textContent,$message),$action.' useful Russian error');
        assertSameValue('true',$target->item(0)->getAttribute('data-completion-focus'),$action.' focus target');
        if($open){$details=$xpath->query('ancestor::details[@open]',$form);assertSameValue(1,$details->length,$action.' correction details open');}
        $other=$xpath->query('//form[@data-completion-form and @data-completion-form!="'.$action.'"]//*[@value="'.str_replace('"','&quot;',$values[array_key_first($values)]).'" or text()="'.$values[array_key_first($values)].'"]');
        assertSameValue(0,$other->length,$action.' values do not bleed into sibling forms');
        assertSameValue(true,str_contains($response['body'],'id="completion"'),$action.' remains in completion section');
    };

    $before=$http->facts();
    $ptoInvalid=['ptoActDate'=>$future];
    $assertRejectedForm($fixture->post('record_pto',$ptoInvalid),'record_pto',$ptoInvalid,'ptoActDate','Укажите дату акта ПТО не позже сегодняшней.');
    assertSameValue($before,$http->facts(),'invalid PTO creates no facts');
    DocumentaryFixture::accepted($fixture->post('record_pto',['ptoActDate'=>'2026-09-05']));

    $declarationInvalid=['declarationDate'=>'2026-09-04','declarationDetails'=>'  '];
    $assertRejectedForm($fixture->post('record_declaration',$declarationInvalid),'record_declaration',$declarationInvalid,'declarationDetails','Укажите реквизиты декларации.');
    DocumentaryFixture::accepted($fixture->post('record_declaration',['declarationDate'=>'2026-09-04','declarationDetails'=>'Д-RECOVERY-001']));
    $roots=$http->rows('fm2_pilot_completion_facts');$byType=array_column($roots,null,'fact_type');

    $invalidCorrections=[
        ['correct_pto',['factId'=>(string)$byType['pto_act']['id'],'ptoActDate'=>'2026-09-03','reason'=>'  '],['ptoActDate'=>'2026-09-03','reason'=>'  ']],
        ['correct_declaration',['factId'=>(string)$byType['declaration']['id'],'declarationDate'=>'2026-09-02','declarationDetails'=>'Д-RECOVERY-002','reason'=>''],['declarationDate'=>'2026-09-02','declarationDetails'=>'Д-RECOVERY-002','reason'=>'']],
    ];
    foreach($invalidCorrections as[$action,$payload,$retained]){
        $before=$http->facts();$response=$fixture->post($action,$payload);
        $assertRejectedForm($response,$action,$retained,'reason','Укажите причину исправления.',true);
        assertSameValue($before,$http->facts(),$action.' invalid reason preserves old facts');
    }
    DocumentaryFixture::accepted($fixture->post('correct_pto',['factId'=>(string)$byType['pto_act']['id'],'ptoActDate'=>'2026-09-03','reason'=>'Дата сверена']));
    DocumentaryFixture::accepted($fixture->post('correct_declaration',['factId'=>(string)$byType['declaration']['id'],'declarationDate'=>'2026-09-02','declarationDetails'=>'Д-RECOVERY-002','reason'=>'Реквизиты сверены']));
    assertSameValue(2,count($http->rows('fm2_pilot_completion_fact_corrections')),'two successful retries append one history row each');

    // A recognized domain conflict stays in the submitted form and remains non-success.
    $before=$http->facts();$conflict=$fixture->post('record_declaration',['declarationDate'=>'2026-09-01','declarationDetails'=>'Д-CONFLICT']);
    assertSameValue(409,$conflict['status'],'domain conflict status preserved');
    assertSameValue(true,str_contains(implode(' ',$conflict['headers']['content-type']??[]),'text/html'),'domain conflict renders card');
    assertSameValue(true,str_contains($conflict['body'],'Документ уже зафиксирован.'),'domain reason visible');
    assertSameValue(true,str_contains($conflict['body'],'value="Д-CONFLICT"'),'conflict retains submitted details');
    assertSameValue($before,$http->facts(),'confirmed conflict creates no facts');

    // Completion enhancement is address-scoped; the browser matrix exercises its
    // in-flight guard, unknown-result copy/value retention, and successful retry.
    $asset=dirname(__DIR__,2).'/app/YiiRuntime/Assets/completion-form.js';
    assertSameValue(true,is_file($asset),'INTENDED_RED completion browser asset exists');
    $source=(string)file_get_contents($asset);
    foreach(['data-completion-form','Результат сохранения не подтверждён','fetch'] as $needle)assertSameValue(true,str_contains($source,$needle),'completion asset contract '.$needle);

    $http->noLegacy();
    echo "PASS: YII2-COMPLETION-FORM-RECOVERY-001 HTTP/browser contract\n";
} finally {
    if($fixture instanceof DocumentaryFixture)$fixture->close();
}
