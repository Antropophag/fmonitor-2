<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/DocumentaryFixture.php';

// YII2-COMPLETION-FORM-RECOVERY-001 A1-A4.
// Public seam: authenticated Yii completion POST and the rendered object card.
$fixture=null;$incomplete=null;
try {
    $fixture=new DocumentaryFixture(dirname(__DIR__,2));
    $fixture->open();
    $fixture->progress();
    $http=$fixture->http;
    $future=(new DateTimeImmutable('tomorrow',new DateTimeZone('Europe/Moscow')))->format('Y-m-d');

    $incomplete=new DocumentaryFixture(dirname(__DIR__,2));$incomplete->open();$incomplete->progress(false);$incompleteBefore=$incomplete->http->facts();
    $incompleteResponse=$incomplete->post('record_pto',['ptoActDate'=>'2026-09-05']);assertSameValue(409,$incompleteResponse['status'],'CHECKLIST_INCOMPLETE status');assertSameValue(true,str_contains($incompleteResponse['body'],'Сначала завершите монтажные работы до 85%.'),'CHECKLIST_INCOMPLETE explanation');assertSameValue(true,str_contains(implode(' ',$incompleteResponse['headers']['content-type']??[]),'text/html'),'CHECKLIST_INCOMPLETE stays on card');assertSameValue($incompleteBefore,$incomplete->http->facts(),'CHECKLIST_INCOMPLETE creates no facts');$incomplete->close();$incomplete=null;

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
        $panel=$xpath->query('//*[@id="completion"]/ancestor::*[@role="tabpanel"][1]');
        assertSameValue(1,$panel->length,$action.' completion panel exists');assertSameValue(false,$panel->item(0)->hasAttribute('hidden'),$action.' completion panel is visible');
    };

    $before=$http->facts();
    $ptoRequired=$fixture->post('record_declaration',['declarationDate'=>'2026-09-04','declarationDetails'=>'Д-ПОРЯДОК']);
    assertSameValue(409,$ptoRequired['status'],'PTO_REQUIRED status');assertSameValue(true,str_contains($ptoRequired['body'],'Сначала зафиксируйте дату акта ПТО.'),'PTO_REQUIRED explanation');assertSameValue($before,$http->facts(),'PTO_REQUIRED creates no facts');
    $ptoInvalid=['ptoActDate'=>$future];
    $assertRejectedForm($fixture->post('record_pto',$ptoInvalid),'record_pto',$ptoInvalid,'ptoActDate','Укажите дату акта ПТО не позже сегодняшней.');
    assertSameValue($before,$http->facts(),'invalid PTO creates no facts');
    DocumentaryFixture::accepted($fixture->post('record_pto',['ptoActDate'=>'2026-09-05']));

    $declarationInvalid=['declarationDate'=>'2026-09-04','declarationDetails'=>'  '];
    $assertRejectedForm($fixture->post('record_declaration',$declarationInvalid),'record_declaration',$declarationInvalid,'declarationDetails','Укажите реквизиты декларации.');
    DocumentaryFixture::accepted($fixture->post('record_declaration',['declarationDate'=>'2026-09-04','declarationDetails'=>'Д-RECOVERY-001']));
    $roots=$http->rows('fm2_pilot_completion_facts');$immutableRoots=$roots;$byType=array_column($roots,null,'fact_type');

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
    assertSameValue($immutableRoots,$http->rows('fm2_pilot_completion_facts'),'successful corrections preserve exact immutable roots');

    // Recognized domain conflicts stay in the submitted form and remain non-success.
    $before=$http->facts();$conflict=$fixture->post('record_declaration',['declarationDate'=>'2026-09-01','declarationDetails'=>'Д-CONFLICT']);
    assertSameValue(409,$conflict['status'],'domain conflict status preserved');
    assertSameValue(true,str_contains(implode(' ',$conflict['headers']['content-type']??[]),'text/html'),'domain conflict renders card');
    assertSameValue(true,str_contains($conflict['body'],'Документ уже зафиксирован.'),'domain reason visible');
    assertSameValue(true,str_contains($conflict['body'],'value="Д-CONFLICT"'),'conflict retains submitted details');
    $conflictDom=new DOMDocument();@$conflictDom->loadHTML('<?xml encoding="UTF-8">'.$conflict['body']);$conflictXpath=new DOMXPath($conflictDom);
    assertSameValue(1,$conflictXpath->query('//*[@data-completion-form="record_declaration"]//*[@data-completion-focus="true" and @role="alert"]')->length,'general conflict focus target');
    assertSameValue($before,$http->facts(),'confirmed conflict creates no facts');

    foreach([
        [999999,'correct_declaration',['factId'=>(string)$byType['declaration']['id'],'declarationDate'=>'2026-09-01','declarationDetails'=>'Д-OTHER','reason'=>'Проверка'],404,'Объект не найден.'],
        [4512,'correct_declaration',['factId'=>'999999','declarationDate'=>'2026-09-01','declarationDetails'=>'Д-NOT-FOUND','reason'=>'Проверка'],409,'Исправляемая запись не найдена.'],
    ] as[$id,$action,$fields,$status,$message]){
        $before=$http->facts();$response=$fixture->post($action,$fields,id:$id);assertSameValue($status,$response['status'],$message.' status');assertSameValue(true,str_contains($response['body'],$message),$message.' visible');assertSameValue($before,$http->facts(),$message.' no facts');
        if($id!==4512)assertSameValue(false,str_contains($response['body'],'Д-OTHER'),'another object does not receive submitted values');
    }
    $http->db->query("UPDATE {$http->p}fm2_installation_cases SET process_state='needs_assignment_change' WHERE id=6101");
    $before=$http->facts();$notWorking=$fixture->post('correct_pto',['factId'=>(string)$byType['pto_act']['id'],'ptoActDate'=>'2026-09-03','reason'=>'Проверка']);assertSameValue(409,$notWorking['status'],'CASE_NOT_WORKING status');assertSameValue(true,str_contains($notWorking['body'],'Работы по объекту не открыты.'),'CASE_NOT_WORKING explanation');assertSameValue($before,$http->facts(),'CASE_NOT_WORKING no facts');$http->db->query("UPDATE {$http->p}fm2_installation_cases SET process_state='working' WHERE id=6101");

    $http->db->query("DELETE FROM {$http->p}fm2_pilot_role_permissions WHERE role_id=1 AND permission='installation.completion.pto.correct'");
    $before=$http->facts();$denied=$fixture->post('correct_pto',['factId'=>(string)$byType['pto_act']['id'],'ptoActDate'=>'2026-09-03','reason'=>'Сохранить нельзя']);assertSameValue(403,$denied['status'],'lost capability remains access denial');assertSameValue(false,str_contains($denied['body'],'Д-RECOVERY-001'),'access denial discloses no document data');assertSameValue($before,$http->facts(),'access denial no facts');$http->insert($http->p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'installation.completion.pto.correct']);
    $guest=[];$session=$http->form('/pilot/objects/4512/completion',['action'=>'correct_pto','factId'=>(string)$byType['pto_act']['id'],'ptoActDate'=>'2026-09-03','reason'=>'Session'],$guest);assertSameValue(true,in_array($session['status'],[303,400,401,403],true),'expired session is not a field error');assertSameValue(false,str_contains($session['body'],'Д-RECOVERY-001'),'session response discloses no document data');
    foreach([
        $http->form('/pilot/objects/4512/completion',['_csrf'=>'wrong','action'=>'correct_pto','factId'=>(string)$byType['pto_act']['id'],'ptoActDate'=>'2026-09-03','reason'=>'CSRF'],$fixture->cookies),
        $fixture->post('unknown',['ptoActDate'=>'2026-09-03']),
        $http->request('POST','/pilot/objects/4512/completion',[],$fixture->cookies,['Content-Type: application/x-www-form-urlencoded'],'_csrf='.rawurlencode($fixture->csrf).'&action=record_pto&ptoActDate=%ZZ'),
    ] as $boundary){assertSameValue(true,in_array($boundary['status'],[400,422],true),'transport boundary rejected');assertSameValue(false,str_contains($boundary['body'],'Д-RECOVERY-001'),'transport boundary renders no protected card');}

    $http->noLegacy();
    echo "PASS: YII2-COMPLETION-FORM-RECOVERY-001 HTTP contract\n";
} finally {
    if($incomplete instanceof DocumentaryFixture)$incomplete->close();
    if($fixture instanceof DocumentaryFixture)$fixture->close();
}
