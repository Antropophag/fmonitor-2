<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;
use FMonitor2\Tests\Support\SelectedOriginalFixture as F;

function cooSetup():array
{
    $f=new F('co_');$db=$f->selection->db;
    try{
        assertSameValue('selected',$f->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'selected composition');
        assertSameValue(['applied'=>true],I\AssignmentOrderApplicationSchemaMigration::apply($db,'co_'),'application schema');
        I\ChecklistTemplateSchemaMigration::apply($db,'co_');$payload='{"sections":[{"id":1,"name":"Открытие","items":[{"id":28,"name":"Работа","weight":2}]}]}';$f->selection->schema->insert('co_fm2_checklist_template_snapshots',['snapshot_version'=>'confirmed-opening-v1','captured_at'=>'2026-09-01 00:00:00','valid_from'=>'2026-09-01 00:00:00','validity_scope'=>'active_baseline_and_future_native_only','source_label'=>'synthetic confirmed opening','content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'created_at'=>'2026-09-01 00:00:00']);
        $f->selection->schema->insert('co_fm2_pilot_users',['user_id'=>19,'full_name'=>'Открывающий сотрудник','email'=>'opener19@example.invalid','status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);$f->selection->schema->insert('co_fm2_pilot_user_roles',['user_id'=>19,'role_id'=>1,'origin'=>'fixture','assigned_at'=>'2026-09-01T06:00:00Z']);$f->selection->schema->insert('co_fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'installation.open']);
        $original=$f->app()->submitAssignmentOrderOriginal(F::command(new O\AssignmentOrderOriginalMemoryStream(F::pdf())));assertSameValue('accepted',$original->status()->value,'accepted original');$clock=new class implements C\SelectionClock{public function now():C\SelectionInstantLookup{return C\SelectionInstantLookup::found(new C\SelectionInstant('2026-09-05T10:00:00Z'));}};return[$f,$original,$clock];
    }catch(Throwable$e){$f->close();throw$e;}
}
function cooSnapshot(mysqli$db):array{$tables=['fm2_assignment_application_attempts','fm2_assignment_order_applications','fm2_process_events','fm2_installation_cases','fm2_checklist_template_associations'];$out=[];foreach($tables as$table)$out[$table]=$db->query('SELECT * FROM co_'.$table.' ORDER BY 1')->fetch_all(MYSQLI_ASSOC);return$out;}
function cooCommand(string$request,O\AssignmentOrderOriginalResult$original,string$date='2026-09-04',int$actor=19,?string$revision=null):object{return new C\OpenConfirmedOriginalCommand($request,4512,81,$revision??(string)$original->currentRevisionId(),0,$date,$actor);}

if(!class_exists(C\ProductionConfirmedOriginalOpeningFactory::class)||!class_exists(C\OpenConfirmedOriginalCommand::class))throw new TestFailure('INTENDED_RED: confirmed-original opening public seam is absent.');

[$f,$original,$clock]=cooSetup();try{$db=$f->selection->db;$standalone=C\ProductionAssignmentOrderApplicationFactory::create($db,'co_',$clock)->applyAssignmentOrderOriginal(new C\ApplyAssignmentOrderOriginalCommand('44444444-4444-4444-8444-000000000001',4512,81,(string)$original->currentRevisionId(),0,19));assertSameValue(['rejected','authorization_denied'],[$standalone->status,$standalone->reason],'opener cannot use standalone composition apply');$owner=C\ProductionConfirmedOriginalOpeningFactory::create($db,'co_',$clock);$command=cooCommand('55555555-5555-4555-8555-000000000001',$original);$result=$owner->openConfirmedOriginal($command);assertSameValue([true,'working','2026-09-04'],[$result['accepted'],$result['processState'],$result['actualStartDate']],'compound command applies confirmed original and opens');assertSameValue(true,(int)$result['applicationId']>0,'success returns application identity');assertSameValue([1,1,1],[count($db->query('SELECT * FROM co_fm2_assignment_order_applications')->fetch_all()),(int)$db->query("SELECT COUNT(*) n FROM co_fm2_process_events WHERE event_type='installation_opened_from_original'")->fetch_assoc()['n'],(int)$db->query('SELECT COUNT(*) n FROM co_fm2_checklist_template_associations')->fetch_assoc()['n']],'one application, opening and template association');assertSameValue([18,19,19],[(int)$db->query('SELECT actor_user_id FROM co_fm2_assignment_order_original_revisions LIMIT 1')->fetch_assoc()['actor_user_id'],(int)$db->query('SELECT applied_by_user_id FROM co_fm2_assignment_order_applications LIMIT 1')->fetch_assoc()['applied_by_user_id'],(int)$db->query('SELECT opened_by_user_id FROM co_fm2_installation_cases WHERE id=4512')->fetch_assoc()['opened_by_user_id']],'uploader remains distinct from authorized opener and application actor');$before=cooSnapshot($db);$replay=$owner->openConfirmedOriginal($command);assertSameValue($result,$replay,'exact retry returns durable result');assertSameValue($before,cooSnapshot($db),'exact retry duplicates no facts');echo"PASS confirmed original compound opening and replay\n";}finally{$f->close();}

[$f,$original,$clock]=cooSetup();try{$db=$f->selection->db;$before=cooSnapshot($db);$result=C\ProductionConfirmedOriginalOpeningFactory::create($db,'co_',$clock)->openConfirmedOriginal(cooCommand('55555555-5555-4555-8555-000000000002',$original,'2026-09-03'));assertSameValue(false,$result['accepted'],'date before original rejected');assertSameValue($before,cooSnapshot($db),'invalid date changes no business snapshot');echo"PASS confirmed original invalid date refusal\n";}finally{$f->close();}

[$f,$original,$clock]=cooSetup();try{$db=$f->selection->db;$before=cooSnapshot($db);$result=C\ProductionConfirmedOriginalOpeningFactory::create($db,'co_',$clock)->openConfirmedOriginal(cooCommand('55555555-5555-4555-8555-000000000003',$original,revision:'revision-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));assertSameValue(false,$result['accepted'],'stale original revision rejected');assertSameValue($before,cooSnapshot($db),'stale revision changes no business snapshot');echo"PASS confirmed original stale revision refusal\n";}finally{$f->close();}

[$f,$original,$clock]=cooSetup();try{$db=$f->selection->db;$f->selection->schema->insert('co_fm2_pilot_users',['user_id'=>20,'full_name'=>'Наблюдатель','email'=>'viewer20@example.invalid','status'=>1,'activation_state'=>'active','source_updated_at'=>'2026-09-01T06:00:00Z']);$before=cooSnapshot($db);$result=C\ProductionConfirmedOriginalOpeningFactory::create($db,'co_',$clock)->openConfirmedOriginal(cooCommand('55555555-5555-4555-8555-000000000004',$original,actor:20));assertSameValue(false,$result['accepted'],'actor without installation.open rejected');assertSameValue($before,cooSnapshot($db),'denied actor changes no business snapshot');echo"PASS confirmed original authorization refusal\n";}finally{$f->close();}

// A rejection after the internal application writes must roll back that application too.
[$f,$original,$clock]=cooSetup();try{
    $db=$f->selection->db;$db->query('DELETE FROM co_fm2_checklist_template_snapshots');$before=cooSnapshot($db);
    $result=C\ProductionConfirmedOriginalOpeningFactory::create($db,'co_',$clock)->openConfirmedOriginal(cooCommand('55555555-5555-4555-8555-000000000005',$original));
    assertSameValue(false,$result['accepted'],'missing template rejects after application preparation');
    assertSameValue($before,cooSnapshot($db),'late opening failure rolls back application, audit, events and opening');
    echo "PASS confirmed original late failure is atomic\n";
}finally{$f->close();}

// Existing manually applied cases must remain usable without a second UI application step.
foreach([false,true] as $correct){
    [$f,$original,$clock]=cooSetup();try{
        $db=$f->selection->db;$db->query("INSERT INTO co_fm2_pilot_role_permissions(role_id,permission) VALUES(1,'assignment_order.composition.apply')");
        $prior=C\ProductionAssignmentOrderApplicationFactory::create($db,'co_',$clock)->applyAssignmentOrderOriginal(new C\ApplyAssignmentOrderOriginalCommand('77777777-7777-4777-8777-000000000001',4512,81,(string)$original->currentRevisionId(),0,18));
        assertSameValue('applied',$prior->status,'existing application setup through public seam');
        $db->query("DELETE FROM co_fm2_pilot_role_permissions WHERE role_id=1 AND permission='assignment_order.composition.apply'");
        $before=$db->query('SELECT * FROM co_fm2_assignment_order_applications ORDER BY application_id')->fetch_all(MYSQLI_ASSOC);
        if($correct){$original=$f->app()->submitAssignmentOrderOriginal(new O\SubmitAssignmentOrderOriginalCommand('88888888-8888-4888-8888-000000000001',O\AssignmentOrderOriginalMode::CORRECTION,4512,81,18,'2026-09-03',true,$original->rootOriginalId(),$original->currentRevisionId(),$original->currentRevisionId(),'Исправлена дата',new O\AssignmentOrderOriginalUpload(new O\AssignmentOrderOriginalMemoryStream(F::pdf()),'signed.pdf','application/pdf')));assertSameValue('accepted',$original->status()->value,'corrected original setup');}
        $command=new C\OpenConfirmedOriginalCommand('99999999-9999-4999-8999-000000000001',4512,81,(string)$original->currentRevisionId(),1,'2026-09-04',19);
        $result=C\ProductionConfirmedOriginalOpeningFactory::create($db,'co_',$clock)->openConfirmedOriginal($command);assertSameValue(true,$result['accepted'],'open uses existing or corrected original internally');
        $after=$db->query('SELECT * FROM co_fm2_assignment_order_applications ORDER BY application_id')->fetch_all(MYSQLI_ASSOC);
        assertSameValue($correct?2:1,count($after),'only changed original adds an application');assertSameValue($before[0],$after[0],'prior application remains byte-identical');
        assertSameValue((int)end($after)['application_id'],$result['applicationId'],'opening result identifies current persisted application');
        if($correct)assertSameValue('reapplication',$after[1]['kind'],'correction has explicit reapplication lineage');
        echo $correct?"PASS confirmed corrected original reapplication and opening\n":"PASS confirmed existing application opening\n";
    }finally{$f->close();}
}
