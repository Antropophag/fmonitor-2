<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/ObjectQueueFixture.php';

// CURRENT-CHECKLIST-STAGE-001 A-J: real accepted operations through public queue/dashboard reads.
$f=null;
try {
    $f=new ObjectQueueFixture(dirname(__DIR__,2));
    $queue=$f->queue();$connection=$f->connection();$p=$f->p;
    $dashboard=FMonitor2\YiiRuntime\InstallationProcessFactory::dashboard($connection,$p,'');
    $stageValues=static fn(array$dto):array=>array_column($dto['charts']['stages'],'value','key');
    $ids=range(1,41);

    // 52 documentary-closeout candidates force a second page at the public queue seam.
    foreach(range(0,51)as$index){$object=451300+$index;$case=6300+$index;$f->object($object,$case,'working','2026-09-01','Полный объект '.$index);$f->order(7300+$index,$case,1,7301);foreach($ids as$revision=>$item)$f->checklist($case,$item,$revision+1);}
    $focusObject=451300;$focusCase=6300;

    // Adjacent incomplete, documentary-completed and needs-change cases retain their rules.
    $f->object(451400,6400,'working','2026-09-01','Незавершённый сосед');$f->order(7400,6400,1,7301);$f->checklist(6400,1,1);
    $f->object(451401,6401,'working','2026-09-01','Документально завершённый сосед');$f->order(7401,6401,1,7301);foreach($ids as$revision=>$item)$f->checklist(6401,$item,$revision+1);$f->completion(6401,'pto_act');$f->completion(6401,'declaration');
    $f->object(451402,6402,'needs_assignment_change','2026-09-01','Требуется изменение');$f->order(7402,6402,1,7301);

    $initial=$queue->read(9101,'','document_closeout',1);
    assertSameValue([52,2,50],[$initial['filters']['total'],$initial['filters']['pages'],count($initial['objects'])],'A/I full current set counted and paginated before rows');
    assertSameValue(2,count($queue->read(9101,'','document_closeout',2)['objects']),'I second page exact before correction');
    assertSameValue('Документарное закрытие',$queue->read(9101,(string)$focusObject,'',1)['objects'][0]['status'],'A card/row shared projection reaches closeout');
    $initialStages=$stageValues($dashboard->read(9101,'2026-09-22'));
    assertSameValue(52,$initialStages['document_closeout'],'A/J dashboard closeout count');
    assertSameValue($initial['filters']['total'],$initialStages['document_closeout'],'J queue/dashboard parity before correction');

    // Historical repeats and attribution-only operations do not change current completion.
    $f->checklist($focusCase,1,42);$f->checklist($focusCase,1,43,'item_installers_changed');
    assertSameValue(52,$queue->read(9101,'','document_closeout',1)['filters']['total'],'D/E duplicate completion and installer change preserve one completed item');

    // Equal accepted revisions are resolved by the later immutable row id.
    $f->insert($p.'fm2_checklist_operations',['installation_case_id'=>$focusCase,'client_operation_id'=>'87654321-4321-4321-8321-000000000044','device_installation_id'=>'12345678-1234-4234-8234-000000000000','operation_type'=>'item_completed','section_id'=>1,'item_id'=>1,'actor_user_id'=>9101,'device_time'=>'2026-09-10T09:30:00+03:00','server_received_at'=>'2026-09-10T09:30:00+03:00','base_revision'=>43,'accepted_revision'=>44,'payload_json'=>'{}']);
    $f->checklist($focusCase,1,44,'completion_retracted');
    $row=$queue->read(9101,(string)$focusObject,'',1)['objects'][0];
    assertSameValue('Монтажные работы',$row['status'],'B shared card/row progress returns to installation');
    assertSameValue([],$queue->read(9101,(string)$focusObject,'document_closeout',1)['objects'],'B closeout filter excludes retracted case before pagination');
    assertSameValue([$focusObject],array_map('intval',array_column($queue->read(9101,(string)$focusObject,'installation',1)['objects'],'id')),'B installation filter includes retracted case');
    $after=$queue->read(9101,'','document_closeout',1);$afterPage2=$queue->read(9101,'','document_closeout',2);
    assertSameValue([51,2,50,1],[$after['filters']['total'],$after['filters']['pages'],count($after['objects']),count($afterPage2['objects'])],'I corrected SQL total/limit/offset stay aligned');
    $afterStages=$stageValues($dashboard->read(9101,'2026-09-22'));
    assertSameValue($after['filters']['total'],$afterStages['document_closeout'],'J dashboard and drill-down parity after correction');
    assertSameValue($initialStages['installation']+1,$afterStages['installation'],'B dashboard moves retracted case into installation');
    assertSameValue('Работы завершены',$queue->read(9101,'451401','',1)['objects'][0]['status'],'G documentary completion never reopens');
    assertSameValue('Требуется изменение',$queue->read(9101,'451402','',1)['objects'][0]['status'],'H needs-change priority preserved');
    assertSameValue([451400],array_map('intval',array_column($queue->read(9101,'451400','installation',1)['objects'],'id')),'F incomplete neighbor unchanged');

    $f->checklist($focusCase,1,45);
    $restored=$queue->read(9101,'','document_closeout',1);$restoredStages=$stageValues($dashboard->read(9101,'2026-09-22'));
    assertSameValue('Документарное закрытие',$queue->read(9101,(string)$focusObject,'',1)['objects'][0]['status'],'C repeat completion restores card/row stage');
    assertSameValue([52,52],[$restored['filters']['total'],$restoredStages['document_closeout']],'C/J completion restores filter and dashboard parity');
    assertSameValue($initialStages['installation'],$restoredStages['installation'],'C dashboard removes recompleted case from installation');
    echo "PASS: CURRENT-CHECKLIST-STAGE-001 A-J current-state stage consistency\n";
} finally {if($f instanceof ObjectQueueFixture)$f->close();}
