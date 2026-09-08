<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;
use FMonitor2\PilotHttp\MariaDbObjectQueue;
use FMonitor2\PilotHttp\InstallationStatusLabels;
use FMonitor2\Tests\Support\SelectedOriginalFixture as F;
use FMonitor2\Tests\Support\SelectionNativeFixture as S;

// Owner feedback 2026-09-07: accepted original makes both card and queue ready,
// without a separate composition application. Filters must use the same fact.
$f=new F('ready_queue_');
try {
    $native=$f->selection;$db=$native->db;$p=$native->prefix;
    foreach(I\ProductionPilotMigrationCatalogue::migrations() as $migration) {
        $result=is_string($migration)?$migration::apply($db,$p):$migration($db,$p);
        assertSameValue(false,isset($result['reason']),'canonical queue prerequisites');
    }
    $db->query("UPDATE `{$p}fm_maintable` SET entrance='2',workdatestart='2026-10-05',plan_finish_date='2026-12-20' WHERE id=4512");
    $detail=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4512,'fields'=>[]],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
    $native->schema->insert($p.'fm2_pilot_object_details',['object_id'=>4512,'schema_version'=>'technical-object-detail-v1','content_sha256'=>hash('sha256',$detail),'payload_json'=>$detail,'captured_at'=>'2026-09-01T06:00:00Z']);
    assertSameValue('selected',$native->app()->selectAssignmentOrderComposition(S::command())->status()->value,'composition selected through public application');
    $queue=new MariaDbObjectQueue($db,$p,$p);
    $expect=static function(string $label,string $filter)use($queue,$native,$f):void {
        $before=[$native->schema->state(),$f->privateFiles()];
        [$rows,$all]=$queue->read('4512','',1);
        assertSameValue([4512,$label,1],[$rows[0]['id'],InstallationStatusLabels::canonical($rows[0]['status']),$all['total']],'queue reflects current original readiness');
        [$filtered,$meta]=$queue->read('4512',$filter,1,1);
        assertSameValue([[4512],1,1],[array_column($filtered,'id'),$meta['total'],$meta['pages']],'status filter and count agree with visible status');
        [$opposite,$other]=$queue->read('4512',$filter==='ready_to_open'?'needs_assignment_order':'ready_to_open',1);
        assertSameValue([[],0],[$opposite,$other['total']],'opposite status excludes this object');
        assertSameValue($before,[$native->schema->state(),$f->privateFiles()],'queue and filters write no facts or original bytes');
    };
    $expect('Требуется распоряжение','needs_assignment_order');
    $original=$f->app()->submitAssignmentOrderOriginal(F::command(new O\AssignmentOrderOriginalMemoryStream(F::pdf())));
    assertSameValue('accepted',$original->status()->value,'accepted original through public application');
    assertSameValue(0,(int)$db->query("SELECT COUNT(*) n FROM `{$p}fm2_assignment_order_applications`")->fetch_assoc()['n'],'no manual application needed');
    $expect('Готов к открытию','ready_to_open');
    $native->schema->insert($p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'assignment_order.composition.apply']);
    $applied=C\ProductionAssignmentOrderApplicationFactory::create($db,$p)->applyAssignmentOrderOriginal(new C\ApplyAssignmentOrderOriginalCommand('44444444-4444-4444-8444-000000000001',4512,81,(string)$original->currentRevisionId(),0,18));
    assertSameValue('applied',$applied->status,'legacy standalone application remains a retained historical fact');
    $applicationBefore=$db->query("SELECT * FROM `{$p}fm2_assignment_order_applications` ORDER BY application_id")->fetch_all(MYSQLI_ASSOC);
    $expect('Готов к открытию','ready_to_open');
    $command=S::command(2,1,7002);
    $next=new C\SelectAssignmentOrderCompositionCommand($command->requestId,C\AssignmentOrderCompositionMode::NEW_ORDER,$command->installationObjectId,$command->actorUserId,$command->installerTabIds,$command->controlEngineerUserId,$command->expectedSelectionRevision);
    assertSameValue('selected',$native->app()->selectAssignmentOrderComposition($next)->status()->value,'new pending composition uses public application');
    $expect('Требуется распоряжение','needs_assignment_order');
    assertSameValue(1,(int)$db->query("SELECT COUNT(*) n FROM `{$p}fm2_assignment_order_original_revisions`")->fetch_assoc()['n'],'old original retained but cannot authorize new composition');
    assertSameValue($applicationBefore,$db->query("SELECT * FROM `{$p}fm2_assignment_order_applications` ORDER BY application_id")->fetch_all(MYSQLI_ASSOC),'old application retained but cannot authorize pending selection');
    echo "PASS original readiness agrees with queue filters without applying or changing facts\n";
} finally {$f->close();}
