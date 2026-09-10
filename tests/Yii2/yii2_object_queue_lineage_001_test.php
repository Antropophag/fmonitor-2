<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/ObjectQueueFixture.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
// YII2-OBJECT-QUEUE-001: independently seeded status buckets, actual native original lifecycle.
$f=null;$original=null;
try {
 $f=new ObjectQueueFixture(dirname(__DIR__,2));$owner=$f->queue();$p=$f->p;
 foreach([[451202,6102,'needs_assignment_order'],[451203,6103,'assignment_order_prepared'],[451204,6104,'needs_assignment_order'],[451205,6105,'working'],[451206,6106,'working'],[451207,6107,'working'],[451208,6108,'needs_assignment_change']]as[$id,$case,$state])$f->object($id,$case,$state);
 foreach([[6121,6103,'prepared'],[6141,6104,'registered'],[6151,6105,'registered'],[6161,6106,'registered'],[6171,6107,'registered'],[6181,6108,'registered']]as[$id,$case,$status])$f->order($id,$case,1,7301,$status);
 foreach([6105,6106,6107]as$case)for($item=1;$item<=41;$item++)$f->checklist($case,$item,$item);
 $f->completion(6106,'pto_act');$f->completion(6107,'pto_act');$f->completion(6107,'declaration');
 $expected=['needs_assignment_order'=>[451202,451203],'ready_to_open'=>[451204],'installation'=>[451201,451208],'document_closeout'=>[451205,451206],'completed'=>[451207],'needs_assignment_change'=>[451208]];
 $before=$f->facts();foreach($expected as$filter=>$ids)assertSameValue($ids,array_map('intval',array_column($owner->read(9101,'',$filter,1)['objects'],'id')),'exact filter bucket '.$filter);assertSameValue($before,$f->facts(),'all filters read only');
 $objects=[];foreach($owner->read(9101,'','',1)['objects']as$row)$objects[(int)$row['id']]=$row;
 foreach([451201=>['Монтажные работы','Продолжить монтажные работы'],451205=>['Документарное закрытие','Зафиксировать дату акта ПТО'],451206=>['Документарное закрытие','Добавить декларацию'],451207=>['Работы завершены','Монтаж закрыт актом ПТО и декларацией'],451208=>['Требуется изменение','Откройте карточку объекта монтажа']]as$id=>$pair)assertSameValue($pair,[$objects[$id]['status'],$objects[$id]['nextStep']],'status and action '.$id);
 $h=$f->http;$h->start();$cookies=[];assertSameValue(303,$h->login($cookies)['status'],'lineage actor login');$response=$h->request('GET','/pilot/objects',[],$cookies);assertSameValue(200,$response['status'],'all status buckets render');$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$response['body']);$xp=new DOMXPath($dom);$buttons=[];foreach($xp->query('//*[@data-inspection-schedule]')as$button)$buttons[]=(int)$button->getAttribute('data-object-id');assertSameValue([451201],$buttons,'schedule action absent on all non-working displayed statuses');$f->noLegacy();$h->stop();
 $f->checklist(6105,8,42,'completion_retracted');$row=$owner->read(9101,'451205','',1)['objects'][0];assertSameValue('Монтажные работы',$row['status'],'retracted weight reduces displayed progress');assertSameValue([451205,451206],array_map('intval',array_column($owner->read(9101,'','document_closeout',1)['objects'],'id')),'legacy filter retains characterized historical count');
 foreach(['assignment_order.prepare','assignment_order.confirm_registration']as$cap){$f->insert($p.'fm2_pilot_role_permissions',['role_id'=>9201,'permission'=>$cap]);assertSameValue('Загрузить оригинал распоряжения',$owner->read(9101,'451202','',1)['objects'][0]['nextStep'],'OR capability '.$cap);$f->db->query("DELETE FROM {$p}fm2_pilot_role_permissions WHERE permission='$cap'");}
 $f->insert($p.'fm2_pilot_role_permissions',['role_id'=>9201,'permission'=>'installation.open']);assertSameValue('Открыть работы',$owner->read(9101,'451204','',1)['objects'][0]['nextStep'],'open capability next step');
 $f->object(451300,6300);$f->db->query("UPDATE {$p}fm2_pilot_object_details SET content_sha256=REPEAT('0',64) WHERE object_id=451300");
 $f->insert($p.'fm2_migration_classification_provenance',['output_kind'=>'operational_case','legacy_object_id'=>451300,'output_id'=>6300,'source_cutoff_at'=>'2026-09-01 09:00:00','classification_version'=>'fixture-v1','category'=>'native_candidate','reason_codes_json'=>'[]','classification_sha256'=>str_repeat('a',64),'created_at'=>'2026-09-01 09:00:00']);
 assertSameValue([451300],array_map('intval',array_column($owner->read(9101,'451300','',1)['objects'],'id')),'native provenance admits independently of imported hash');
 $f->db->query("UPDATE {$p}fm2_migration_classification_provenance SET category='excluded' WHERE legacy_object_id=451300");assertSameValue([],$owner->read(9101,'451300','',1)['objects'],'excluded provenance not admitted');
 $f->close();$f=null;
 // Native lineage comes from already-approved public selection and original commands.
 $original=new FMonitor2\Tests\Support\SelectedOriginalFixture();$db=$original->selection->db;
 $migration=FMonitor2\InstallationProcess\CanonicalMigrationApplication::run($db,'',FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue::migrations());assertSameValue(0,$migration['exitCode'],'canonical v24 native fixture');
 $db->query("UPDATE fm_maintable SET entrance='1',workdatestart='2026-10-01',plan_finish_date='2026-10-31' WHERE id=4512");
 $db->query("INSERT INTO fm2_pilot_role_permissions(role_id,permission) VALUES(1,'objects.read')");
 assertSameValue('selected',$original->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'native selection prerequisite');
 $payload=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4512,'fields'=>[]],JSON_THROW_ON_ERROR);$hash=hash('sha256',$payload);$s=$db->prepare("INSERT INTO fm2_pilot_object_details(object_id,schema_version,content_sha256,payload_json,captured_at) VALUES(4512,'technical-object-detail-v1',?,?,'2026-09-01T09:00:00Z')");$s->bind_param('ss',$hash,$payload);$s->execute();
 $connection=new yii\db\Connection(['dsn'=>'mysql:host='.(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('FMONITOR_TEST_DB_PORT')?:23306).';dbname='.$original->selection->schema->source->name,'username'=>getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root','password'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local','charset'=>'utf8mb4']);
 $owner=FMonitor2\YiiRuntime\InstallationProcessFactory::queue($connection,'','');
 assertSameValue('Требуется распоряжение',$owner->read(18,'4512','',1)['objects'][0]['status'],'selection without original');
 $accepted=$original->app()->submitAssignmentOrderOriginal(FMonitor2\Tests\Support\SelectedOriginalFixture::command(new FMonitor2\Tests\Support\SelectedOriginalInput(FMonitor2\Tests\Support\SelectedOriginalFixture::pdf())));assertSameValue('accepted',$accepted->status()->value,'real accepted original');
 assertSameValue([4512],array_map('intval',array_column($owner->read(18,'','ready_to_open',1)['objects'],'id')),'original ready without application');assertSameValue(0,(int)$db->query('SELECT COUNT(*) FROM fm2_assignment_order_applications')->fetch_column(),'read never applies');
 $db->query("INSERT IGNORE INTO fm2_pilot_role_permissions(role_id,permission) VALUES(1,'assignment_order.composition.apply')");
 $applied=FMonitor2\AssignmentOrderComposition\ProductionAssignmentOrderApplicationFactory::create($db)->applyAssignmentOrderOriginal(new FMonitor2\AssignmentOrderComposition\ApplyAssignmentOrderOriginalCommand('66666666-6666-4666-8666-666666666666',4512,81,(string)$accepted->currentRevisionId(),0,18));assertSameValue('applied',$applied->status,'real application prerequisite');assertSameValue('Готов к открытию',$owner->read(18,'4512','',1)['objects'][0]['status'],'application preserves separate opening');
 $history=$db->query('SELECT * FROM fm2_assignment_order_applications ORDER BY application_id')->fetch_all(MYSQLI_ASSOC);$files=$original->privateFiles();
 $command=FMonitor2\Tests\Support\SelectionNativeFixture::command(2,1,7002);
 $next=new FMonitor2\AssignmentOrderComposition\SelectAssignmentOrderCompositionCommand($command->requestId,FMonitor2\AssignmentOrderComposition\AssignmentOrderCompositionMode::NEW_ORDER,$command->installationObjectId,$command->actorUserId,$command->installerTabIds,$command->controlEngineerUserId,$command->expectedSelectionRevision);
 assertSameValue('selected',$original->selection->app()->selectAssignmentOrderComposition($next)->status()->value,'new pending composition');
 assertSameValue('Требуется распоряжение',$owner->read(18,'4512','',1)['objects'][0]['status'],'old original/application does not authorize new selection');assertSameValue([],$owner->read(18,'4512','ready_to_open',1)['objects'],'new selection excluded from ready filter');
 assertSameValue($history,$db->query('SELECT * FROM fm2_assignment_order_applications ORDER BY application_id')->fetch_all(MYSQLI_ASSOC),'old application history exact');assertSameValue($files,$original->privateFiles(),'original bytes preserved');
 echo "PASS: YII2-OBJECT-QUEUE-001 native original lineage status filters and retraction\n";
} finally {if($f instanceof ObjectQueueFixture)$f->close();if($original instanceof FMonitor2\Tests\Support\SelectedOriginalFixture)$original->close();}
