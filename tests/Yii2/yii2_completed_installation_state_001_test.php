<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/DocumentaryFixture.php';
// PERSIST-COMPLETED-INSTALLATION-STATE-001 A1-A5: public HTTP completion and checklist seams.
$f=null;
try{
 $f=new DocumentaryFixture(dirname(__DIR__,2));$f->open();$f->progress();$h=$f->http;
 DocumentaryFixture::accepted($f->post('record_pto',['ptoActDate'=>'2026-09-05']));
 $before=$h->facts();DocumentaryFixture::accepted($f->post('record_declaration',['declarationDate'=>'2026-09-06','declarationDetails'=>'Д-001']));
 $case=$h->db->query("SELECT process_state,lock_version FROM {$h->p}fm2_installation_cases WHERE id=6101")->fetch_assoc();
 $events=array_values(array_filter($h->rows('fm2_process_events'),static fn(array$r):bool=>$r['event_type']==='installation_completed'));
 assertSameValue(1,count($events),'INTENDED_RED exactly one completion event');assertSameValue('completed',$case['process_state'],'first declaration persists completed state');
 assertSameValue(['6101','18'],[$events[0]['installation_case_id'],$events[0]['actor_user_id']],'completion event case and declaration actor');
 $payload=json_decode($events[0]['payload_json'],true,flags:JSON_THROW_ON_ERROR);assertSameValue((int)$h->rows('fm2_pilot_completion_facts')[1]['id'],(int)($payload['declarationFactId']??0),'completion event links declaration identity');
 $f->unchangedExcept($before,['fm2_pilot_completion_facts','fm2_installation_cases','fm2_process_events']);
 $page=$f->page();assertSameValue(true,str_contains($page['body'],'Работы завершены')&&str_contains($page['body'],'100%'),'completed card projection');
 $queue=$h->request('GET','/pilot/objects',[],$f->cookies);assertSameValue(true,str_contains($queue['body'],'Работы завершены'),'completed queue projection');
 $roots=array_column($h->rows('fm2_pilot_completion_facts'),null,'fact_type');$eventBefore=$events[0];
 DocumentaryFixture::accepted($f->post('correct_pto',['factId'=>$roots['pto_act']['id'],'ptoActDate'=>'2026-09-04','reason'=>'Сверено после завершения']));
 DocumentaryFixture::accepted($f->post('correct_declaration',['factId'=>$roots['declaration']['id'],'declarationDate'=>'2026-09-05','declarationDetails'=>'Д-002','reason'=>'Исправлены реквизиты']));
 assertSameValue(2,count($h->rows('fm2_pilot_completion_fact_corrections')),'corrections remain append-only after completion');
 assertSameValue('completed',$h->db->query("SELECT process_state FROM {$h->p}fm2_installation_cases WHERE id=6101")->fetch_column(),'correction keeps completed');
 $afterEvents=array_values(array_filter($h->rows('fm2_process_events'),static fn(array$r):bool=>$r['event_type']==='installation_completed'));assertSameValue([$eventBefore],$afterEvents,'corrections do not duplicate completion event');
 $check=$f->inspection->page();$operation=InspectionFixture::operation(990,41,32);$operation['sectionId']=1;$operation['type']='completion_retracted';$operation['originalClientOperationId']=InspectionFixture::operation(532)['clientOperationId'];$operation['reason']='Недопустимо после завершения';
 InspectionFixture::result($f->inspection->send($operation,InspectionFixture::csrf($check)),409,'rejected');
 $beforeReplay=$h->facts();DocumentaryFixture::rejected($f->post('record_declaration',['declarationDate'=>'2026-09-06','declarationDetails'=>'Д-003']),409,'Документ уже зафиксирован.');assertSameValue($beforeReplay,$h->facts(),'repeat declaration changes nothing');
 echo "PASS: PERSIST-COMPLETED-INSTALLATION-STATE-001 HTTP state\n";
}finally{if($f instanceof DocumentaryFixture)$f->close();}
