<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;

// ASSIGNMENT-ORDER-SELECTION-NATIVE-001: prefix and immutable original-root/state boundary.
function readinessNewCommand():C\SelectAssignmentOrderCompositionCommand {
    $c=F::command(2,1,7002);return new C\SelectAssignmentOrderCompositionCommand($c->requestId,C\AssignmentOrderCompositionMode::NEW_ORDER,$c->installationObjectId,$c->actorUserId,$c->installerTabIds,$c->controlEngineerUserId,$c->expectedSelectionRevision);
}
function readinessOriginal(F $f,bool $leaf=true):void {
    // Synthetic accepted lineage setup only: no claim about PDF upload/storage integration.
    $f->schema->insert('fm2_assignment_order_original_roots',['root_original_id'=>'native-root-81','installation_case_id'=>4512,'assignment_order_id'=>81,'current_revision_id'=>'native-revision-81-1','composition_identity'=>'composition-81-v1','composition_sha256'=>'5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a','created_at_utc'=>'2026-09-05 09:00:00.000000']);
    if($leaf)$f->schema->insert('fm2_assignment_order_original_revisions',['revision_id'=>'native-revision-81-1','root_original_id'=>'native-root-81','revision_number'=>1,'previous_revision_id'=>null,'document_date'=>'2026-09-04','uploaded_at_utc'=>'2026-09-05 09:00:00.000000','actor_user_id'=>18,'pdf_sha256'=>str_repeat('a',64),'byte_size'=>100,'private_content_identity'=>'native-content-81-1','correction_reason'=>null,'request_id'=>'22222222-2222-4222-8222-000000000081','operation_fingerprint'=>str_repeat('b',64),'event_type'=>'assignment_order_original_accepted']);
}
$tests=[
    'prefix25 two modes replay and registered reader'=>static function(F $f):void {
        $app=$f->app();$before=$f->rows();$a=$app->selectAssignmentOrderComposition(F::command());$b=$app->selectAssignmentOrderComposition(F::command(2,1,7002));
        assertSameValue(['selected',81,'selected',82],[$a->status()->value,$a->success()?->assignmentOrderId,$b->status()->value,$b->success()?->assignmentOrderId],'prefix identities');
        $after=$f->rows();assertSameValue('replayed',$app->selectAssignmentOrderComposition(F::command(2,1,7002))->status()->value,'prefix replay');assertSameValue($after,$f->rows(),'prefix replay silent');
        $r=O\AssignmentOrderRegisteredCompositionReaderFactory::create($f->db,$f->prefix)->find(4512,82);assertSameValue(['found',[7002]],[$r->status->value,$r->installerIds],'prefix reader handoff');
        assertSameValue(true,I\AssignmentOrderSelectionSchemaMigration::isReady($f->db,$f->prefix),'prefix canonical coherence');
        foreach($before as $table=>$rows)if(!str_starts_with($table,'fm2_assignment_order_selection')&&$table!=='fm2_assignment_order_identities')assertSameValue($rows,$after[$table],'prefix unrelated facts preserved');
    },
    'invalid prefix rejected before connection IO'=>static function(F $f):void {
        $closed=$f->schema->source->connect($f->schema->source->name);$closed->close();
        foreach([str_repeat('x',26),'bad-prefix'] as $prefix){$error=null;try{C\ProductionAssignmentOrderCompositionFactory::create($closed,static function(){throw new TestFailure('fresh connection must not open');},$prefix);}catch(InvalidArgumentException $e){$error=$e->getMessage();}assertSameValue('Invalid selection table prefix.',$error,'shape rejection precedes closed-connection IO');}
    },
    'missing selection audit table fails closed'=>static function(F $f):void {
        $f->db->query('RENAME TABLE fm2_assignment_order_selection_audits TO fixture_missing_audits');$before=$f->rows();$r=$f->app()->selectAssignmentOrderComposition(F::command());
        assertSameValue(['failed','dependency_unavailable',0],[$r->status()->value,$r->reasonCode()?->value,$f->clockCalls],'readiness before attempt clock');assertSameValue($before,$f->rows(),'no repair or facts');
    },
    'registry schema drift fails closed'=>static function(F $f):void {
        $f->db->query('ALTER TABLE fm2_assignment_order_identities ADD COLUMN fixture_drift INT NULL');$before=$f->rows();$r=$f->app()->selectAssignmentOrderComposition(F::command());assertSameValue(['failed','dependency_unavailable'],[$r->status()->value,$r->reasonCode()?->value],'registry drift unavailable');assertSameValue($before,$f->rows(),'no runtime registry repair');
    },
];
foreach(['accepted_new','accepted_replace','root_hash_mismatch','missing_leaf','missing_root_table'] as $axis)$tests[$axis]=static function(F $f)use($axis):void {
    $app=$f->app();assertSameValue('selected',$app->selectAssignmentOrderComposition(F::command())->status()->value,'native pending selection setup');
    if($axis!=='missing_root_table')readinessOriginal($f,$axis!=='missing_leaf');
    if($axis==='root_hash_mismatch')$f->db->query("UPDATE fm2_assignment_order_original_roots SET composition_sha256=REPEAT('0',64)");
    if($axis==='missing_root_table')$f->db->query('RENAME TABLE fm2_assignment_order_original_roots TO fixture_missing_roots');
    $before=$f->rows();$r=$app->selectAssignmentOrderComposition($axis==='accepted_replace'?F::command(2,1,7002):readinessNewCommand());
    $expected=match($axis){'accepted_new'=>['selected',null],'accepted_replace'=>['conflict','original_already_accepted'],default=>['failed','dependency_unavailable']};assertSameValue($expected,[$r->status()->value,$r->reasonCode()?->value],'locked accepted-root outcome');
    $after=$f->rows();foreach($before as $table=>$rows)if(!str_starts_with($table,'fm2_assignment_order_selection')&&$table!=='fm2_assignment_order_identities')assertSameValue($rows,$after[$table],'selection cannot alter originals/applicability');
    if($axis==='accepted_new')assertSameValue([82,2,2],[$r->success()?->assignmentOrderId,$r->success()?->assignmentOrderVersion,$r->success()?->selectionRevision],'new prospective identity after accepted root');
    elseif($axis==='accepted_replace')assertSameValue([1,2,2],[count($after['fm2_assignment_order_selections']),count($after['fm2_assignment_order_selection_requests']),count($after['fm2_assignment_order_selection_audits'])],'refusal terminal only');
    else assertSameValue($before,$after,'unavailable original state writes nothing');
};
$failed=0;foreach($tests as $name=>$test){$f=null;$errors=[];try{$f=new F(str_starts_with($name,'prefix25')?str_repeat('p',25):'');echo "SETUP_OK $name\n";$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null)try{$f->close();echo "CLEANUP_OK $name\n";}catch(Throwable $e){$errors[]='cleanup: '.$e->getMessage();}
    if($errors!==[]){$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}else echo "PASS $name\n";
}exit($failed===0?0:1);
