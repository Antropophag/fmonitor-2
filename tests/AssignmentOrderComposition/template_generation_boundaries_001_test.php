<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\TemplateGenerationFixture as F;
use FMonitor2\Tests\Support\SelectionNativeFixture as S;
use FMonitor2\AssignmentOrderComposition as C;

// ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001 v0.2: native failures and immutable projection.
function templateRefusal(array $r,string $status,string $reason):void {assertSameValue(['status'=>$status,'reasonCode'=>$reason,'assignmentOrderId'=>null,'templateDate'=>null,'filename'=>null,'mediaType'=>null,'bytes'=>null],$r,'closed refusal has no PDF');}
$tests=[];
foreach(['invalid','denied','missing','other_case','stale','completed','pto'] as $axis)$tests[$axis]=static function(F $f)use($axis):void {
    $db=$f->original->selection->db;$case=4512;$order=81;
    if($axis==='invalid')$case=0;
    if($axis==='denied')$db->query("DELETE FROM fm2_pilot_role_permissions WHERE permission='assignment_order.composition.select'");
    if($axis==='missing')$order=99;
    if($axis==='other_case'){$case=4513;$db->query("INSERT INTO fm2_installation_cases(id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(4513,4513,'needs_assignment_order','2026-09-01T00:00:00Z','2026-09-01T00:00:00Z',1)");}
    if($axis==='stale')assertSameValue('selected',$f->original->selection->app()->selectAssignmentOrderComposition(S::command(2,1,7002))->status()->value,'real replacement');
    if($axis==='completed')$db->query("UPDATE fm_maintable SET workdatefinish='2026-09-04',ptoactdate='2026-09-03'");
    if($axis==='pto')$db->query("UPDATE fm_maintable SET ptoactdate='2026-09-03'");
    $before=$f->original->selection->rows();$r=$f->app()->generateAssignmentOrderTemplate($case,$order,18);
    [$status,$reason]=match($axis){'invalid'=>['rejected','invalid_command'],'denied'=>['rejected','authorization_denied'],'missing','other_case'=>['rejected','order_not_found'],'stale'=>['conflict','target_not_current'],'completed'=>['rejected','object_completed'],'pto'=>['rejected','object_has_pto_act'],default=>['failed','dependency_unavailable']};
    templateRefusal($r,$status,$reason);assertSameValue(0,$f->clockCalls,'refusal precedes clock');assertSameValue($before,$f->original->selection->rows(),'refusal never creates generation/domain facts');assertSameValue([], $f->inputs,'refusal before renderer');assertSameValue([], $f->original->privateFiles(),'refusal stores no PDF');
};
foreach(['missing_object_date'=>null,'malformed_adjusted'=>'2026-12-20'] as $axis=>$finish)$tests[$axis]=static function(F $f)use($axis,$finish):void {
    $db=$f->original->selection->db;if($axis==='missing_object_date')$db->query('UPDATE fm_maintable SET plan_finish_date=NULL');else$db->query("UPDATE fm_maintable SET workdateendadjusted='not-a-date'");$before=$f->original->selection->rows();$files=$f->original->privateFiles();$r=$f->app()->generateAssignmentOrderTemplate(4512,81,18);
    assertSameValue(['generated',null,81,'2026-09-06','Распоряжение о закреплении монтажников.pdf','application/pdf'],array_slice(array_values($r),0,6),'planned-date gap returns exact generated envelope');assertSameValue(true,str_starts_with($r['bytes'],'%PDF-'),'planned-date gap returns real PDF');assertSameValue([$finish,1],[$f->inputs[0]['installationObjectSnapshot']['plannedFinishDate'],$f->clockCalls],'planned finish normalization and one generation clock');$after=$f->original->selection->rows();assertSameValue(array_keys($before),array_keys($after),'generation creates no schema');foreach($before as$table=>$rows)if($table!=='fm2_process_events')assertSameValue($rows,$after[$table],"planned-date generation changes no domain table: $table");assertSameValue(1,count($after['fm2_process_events']),'one metadata-only generation audit');assertSameValue(['4512','assignment_order_template_generated','2026-09-05T21:30:00Z','18','{"assignmentOrderId":81,"assignmentOrderVersion":1,"compositionIdentity":"composition-81-v1","compositionSha256":"5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a","templateDate":"2026-09-06"}'],array_slice(array_values($after['fm2_process_events'][0]),1),'exact planned-date generation audit');assertSameValue($files,$f->original->privateFiles(),'generated template PDF is returned but never stored');
};
foreach(['empty','multiple','extra','wrong_type','bad_bytes'] as $axis)$tests['renderer_'.$axis]=static function(F $f)use($axis):void {
    $before=$f->original->selection->rows();$render=function($input)use($f,$axis){$r=$f->render($input);return match($axis){'empty'=>[],'multiple'=>[$r[0],$r[0]],'extra'=>[array_merge($r[0],['version'=>1])],'wrong_type'=>[array_merge($r[0],['type'=>'appendix'])],default=>[array_merge($r[0],['bytes'=>'not a PDF'])]};};
    $app=C\AssignmentOrderTemplateVerificationFactory::create($f->original->selection->db,'',$f,$render);templateRefusal($app->generateAssignmentOrderTemplate(4512,81,18),'failed','render_failure');assertSameValue($before,$f->original->selection->rows(),'invalid renderer response not audited as success');assertSameValue(1,$f->clockCalls,'one renderer-attempt clock');
};
$tests['last event ID and matching event integrity']=static function(F $f):void {
    $app=$f->app();$f->at='2026-09-06T21:30:00Z';assertSameValue('generated',$app->generateAssignmentOrderTemplate(4512,81,18)['status'],'first generation');$f->at='2026-09-05T21:30:00Z';assertSameValue('generated',$app->generateAssignmentOrderTemplate(4512,81,18)['status'],'later event with earlier civil date');
    $dates=$f->dates();assertSameValue(['status'=>'found','date'=>'2026-09-06'],$dates->find(4512,81),'greatest event ID, not MAX(date)');assertSameValue(2,$f->clockCalls,'one clock for each generation, none for projection');
    $db=$f->original->selection->db;$f->original->selection->schema->insert('fm2_process_events',['installation_case_id'=>4512,'event_type'=>'assignment_order_template_generated','occurred_at'=>'bad','actor_user_id'=>18,'payload_json'=>'{"assignmentOrderId":82}']);assertSameValue(['status'=>'found','date'=>'2026-09-06'],$dates->find(4512,81),'unrelated identified event ignored');
    $bad=['assignmentOrderId'=>81,'assignmentOrderVersion'=>1,'compositionIdentity'=>'composition-81-v1','compositionSha256'=>'5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a','templateDate'=>'2026-09-08'];$statement=$db->prepare('UPDATE fm2_process_events SET payload_json=? WHERE id=2');$statement->execute([json_encode($bad,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$statement->close();assertSameValue(['status'=>'unavailable','date'=>null],$dates->find(4512,81),'matching malformed date never becomes old prefill');
};
$tests['adjusted finish takes precedence']=static function(F $f):void {
    $f->original->selection->db->query("UPDATE fm_maintable SET workdateendadjusted='2026-12-15'");assertSameValue('generated',$f->app()->generateAssignmentOrderTemplate(4512,81,18)['status'],'valid adjusted date');assertSameValue('2026-12-15',$f->inputs[0]['installationObjectSnapshot']['plannedFinishDate'],'existing adapter precedence');assertSameValue(1,$f->clockCalls,'one adjusted-date generation clock');
};
$tests['audit SQL failure rolls back']=static function(F $f):void {
    $f->original->selection->db->query('RENAME TABLE fm2_process_events TO fixture_missing_events');$before=$f->original->selection->rows();templateRefusal($f->app()->generateAssignmentOrderTemplate(4512,81,18),'failed','persistence_failure');assertSameValue(1,count($f->inputs),'real render before failed audit');assertSameValue($before,$f->original->selection->rows(),'confirmed rollback changes nothing');assertSameValue(1,$f->clockCalls,'one failed-persistence clock');
};
$tests['event capacity no acknowledged PDF']=static function(F $f):void {
    $f->original->selection->db->query('ALTER TABLE fm2_process_events AUTO_INCREMENT=9223372036854775808');$before=$f->original->selection->rows();templateRefusal($f->app()->generateAssignmentOrderTemplate(4512,81,18),'failed','allocation_capacity_exhausted');assertSameValue($before,$f->original->selection->rows(),'overflow rollback no business facts');assertSameValue(1,$f->clockCalls,'one capacity-attempt clock');
};
$tests['interrupted own transaction returns unknown without PDF']=static function(F $f):void {
    $control=$f->original->selection->db;$runtime=$f->original->selection->schema->source->connect($f->original->selection->schema->source->name);$thread=$runtime->thread_id;$killed=false;$before=$f->original->selection->rows();
    try{$render=function($input)use($f,$control,$thread,&$killed){$r=$f->render($input);$killed=$control->query('KILL CONNECTION '.$thread)===true;return $r;};$app=C\AssignmentOrderTemplateVerificationFactory::create($runtime,'',$f,$render);$r=$app->generateAssignmentOrderTemplate(4512,81,18);assertSameValue(true,$killed,'only dedicated native runtime killed after render');templateRefusal($r,'failed','persistence_outcome_unknown');assertSameValue($before,$f->original->selection->rows(),'no blind mutation retry');assertSameValue(1,$f->clockCalls,'no clock retry after interruption');}finally{$runtime->close();}
};
$tests['ambient transaction is not taken over']=static function(F $f):void {
    $app=$f->app();$dates=$f->dates();$db=$f->original->selection->db;$before=$f->original->selection->rows();$db->begin_transaction();
    try{$db->query("INSERT INTO other_prefix_marker VALUES(2,'caller-owned')");templateRefusal($app->generateAssignmentOrderTemplate(4512,81,18),'failed','dependency_unavailable');assertSameValue(['status'=>'unavailable','date'=>null],$dates->find(4512,81),'date reader rejects ambient snapshot');assertSameValue('1',(string)$db->query('SELECT @@in_transaction')->fetch_row()[0],'caller transaction retained');}finally{$db->rollback();}assertSameValue($before,$f->original->selection->rows(),'caller controls rollback');assertSameValue(0,$f->clockCalls,'ambient rejection before clock');
};
$tests['malformed clock stops before renderer']=static function(F $f):void {
    $before=$f->original->selection->rows();$f->at='not-an-instant';templateRefusal($f->app()->generateAssignmentOrderTemplate(4512,81,18),'failed','dependency_unavailable');assertSameValue([1,[]],[$f->clockCalls,$f->inputs],'one failed clock and no renderer');assertSameValue($before,$f->original->selection->rows(),'no generation fact');
};
$failed=0;foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F();echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}exit($failed===0?0:1);
