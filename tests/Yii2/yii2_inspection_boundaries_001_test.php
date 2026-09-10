<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// YII2-INSPECTION-JOURNEY-001 A2/A8/A9: public DB-component and projection regressions.
final class InspectionObservedYiiConnection extends yii\db\Connection
{
    public int $commands=0;
    public function createCommand($sql=null,$params=[])
    {
        $this->commands++;
        return parent::createCommand($sql,$params);
    }
}

function inspectionConfiguredDatabase():void
{
    $f=null;$yii=null;$prior=[];
    try {
        $f=new InspectionFixture(dirname(__DIR__,2));$f->open();$h=$f->http;$env=$h->environment();
        $yii=new InspectionObservedYiiConnection([
            'dsn'=>'mysql:host='.$env['FMONITOR_DB_HOST'].';port='.$env['FMONITOR_DB_PORT'].';dbname='.$env['FMONITOR_DB_NAME'],
            'username'=>$h->dmlUser,'password'=>$h->dmlPassword,'charset'=>'utf8mb4',
        ]);
        assertSameValue('1',(string)$yii->createCommand('SELECT 1')->queryScalar(),'configured Yii connection works before target');
        $events=['begin'=>0,'commit'=>0,'rollback'=>0];
        foreach(['begin'=>yii\db\Connection::EVENT_BEGIN_TRANSACTION,'commit'=>yii\db\Connection::EVENT_COMMIT_TRANSACTION,'rollback'=>yii\db\Connection::EVENT_ROLLBACK_TRANSACTION]as$key=>$event)$yii->on($event,static function()use(&$events,$key):void{$events[$key]++;});
        // The injected component is valid; unrelated process environment deliberately is not.
        foreach(['FMONITOR_DB_HOST'=>'127.0.0.1','FMONITOR_DB_PORT'=>'1','FMONITOR_DB_NAME'=>'not_the_injected_database','FMONITOR_DB_USER'=>'not_the_injected_user','FMONITOR_DB_PASSWORD'=>'not_a_credential']as$key=>$value){$prior[$key]=getenv($key);putenv($key.'='.$value);}
        try{$owner=new FMonitor2\InspectionEvidence\MariaDbYiiChecklist($yii,$h->p,$h->p,$h->base->privateRoot,'2026-09-10T09:00:00+03:00');}
        catch(Throwable $error){throw new TestFailure('INTENDED_RED owner must use provided Yii DB, not environment discovery: '.get_class($error));}
        $before=$h->facts();$commands=$yii->commands;
        assertSameValue(0,$owner->projection(4512)['revision'],'query supplied database');
        assertSameValue(true,$yii->commands>$commands,'read crosses configured Yii DAO');assertSameValue($before,$h->facts(),'DAO read has no process writes');
        $bytes=InspectionFixture::png(42);$photo=array_replace(InspectionFixture::operation(501),['type'=>'photo_uploaded','mime'=>'image/png','size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes),'originalName'=>'dao.png']);unset($photo['itemId'],$photo['installerTabIds']);
        $commands=$yii->commands;$r=$owner->accept(4512,73,$photo,$bytes);assertSameValue(['accepted',1],[$r['status'],$r['revision']],'non-item mutation on supplied database');
        assertSameValue(true,$yii->commands>$commands,'write crosses configured Yii DAO');assertSameValue(['begin'=>1,'commit'=>1,'rollback'=>0],$events,'one Yii transaction commits the photo operation');
        assertSameValue('6101',$h->rows('fm2_checklist_photos')[0]['installation_case_id'],'fact lands in injected database/case');
        // Authorization belongs to this public owner, including replay, not only the HTTP guard.
        foreach([[0,'active'],[1,'invited']]as[$status,$activation]){
            $h->db->prepare("UPDATE {$h->p}fm2_pilot_users SET status=?,activation_state=? WHERE user_id=73")->execute([$status,$activation]);
            try{$before=$h->facts();$denied=$owner->accept(4512,73,$photo,$bytes);assertSameValue('forbidden',$denied['status'],'INTENDED_RED public owner rejects inactive/invited replay');assertSameValue($before,$h->facts(),'denied replay no facts');assertSameValue(['begin'=>1,'commit'=>1,'rollback'=>0],$events,'denied replay opens no mutation transaction');}
            finally{$h->db->query("UPDATE {$h->p}fm2_pilot_users SET status=1,activation_state='active' WHERE user_id=73");}
        }
        $trigger=$h->p.'dao_photo_failure';$h->db->query("CREATE TRIGGER $trigger BEFORE INSERT ON {$h->p}fm2_checklist_operations FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic DAO failure'");
        try {
            $before=$h->facts();$bytes=InspectionFixture::png(43);$photo=array_replace($photo,['clientOperationId'=>InspectionFixture::operation(502)['clientOperationId'],'baseRevision'=>1,'size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)]);
            $failed=false;try{$r=$owner->accept(4512,73,$photo,$bytes);$failed=($r['status']??'')==='retryable';}catch(Throwable){$failed=true;}
            assertSameValue(true,$failed,'storage failure never accepted');assertSameValue($before,$h->facts(),'entire Yii mutation rolls back');assertSameValue(['begin'=>2,'commit'=>1,'rollback'=>1],$events,'rollback belongs to same configured Yii transaction');
        }finally{$h->db->query("DROP TRIGGER $trigger");}
    }finally{foreach($prior as$key=>$value)putenv($value===false?$key:$key.'='.$value);if($yii!==null)$yii->close();if($f!==null)$f->close();}
}

function inspectionHistoricalAndCurrentCrew():void
{
    $f=null;
    try {
        $f=new InspectionFixture(dirname(__DIR__,2));$h=$f->http;$p=$h->p;
        // Literal legacy registered case: no application snapshot. Canonical schema, no production data.
        $h->db->query("UPDATE {$p}fm2_installation_cases SET process_state='working',actual_start_date='2026-09-02',opened_at='2026-09-02T09:00:00+03:00',opened_by_user_id=18 WHERE id=6101");
        $h->insert($p.'fm2_assignment_orders',['id'=>8101,'installation_case_id'=>6101,'version_no'=>1,'kind'=>'initial','status'=>'registered','order_date'=>'2026-09-01','registration_number'=>'FIXTURE','registered_at'=>'2026-09-01T09:00:00+03:00','registration_actor_type'=>'user','registration_actor_id'=>'18','registration_source'=>'manual','control_engineer_user_id'=>73,'control_engineer_fio_snapshot'=>'Инженер теста','control_engineer_position_snapshot'=>'Инженер','organization_form'=>'brigade','object_address_snapshot'=>'Вымышленный объект','entrance_snapshot'=>'2','object_registration_number_snapshot'=>'TEST-4512','planned_start_date_snapshot'=>'2026-09-02','planned_finish_date_snapshot'=>'2026-12-01','prepared_at'=>'2026-09-01T08:00:00+03:00','prepared_by_user_id'=>18]);
        foreach([7001,7002]as$id)$h->insert($p.'fm2_order_installers',['assignment_order_id'=>8101,'installer_tab_id'=>$id,'fio_snapshot'=>'Монтажник '.$id,'position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2020-01-01','workforce_source_snapshot'=>'synthetic-hr','workforce_source_updated_at_snapshot'=>'2026-09-01T06:00:00Z','valid_from'=>'2026-09-01','change_action'=>'assign']);
        $template=$h->rows('fm2_checklist_template_snapshots')[0];
        $h->insert($p.'fm2_checklist_template_associations',['association_version'=>'inspection-regression-fixture','subject_kind'=>'operational_case','subject_id'=>'6101','effective_at'=>'2026-09-01 00:00:00','template_snapshot_id'=>$template['id'],'template_snapshot_version'=>$template['snapshot_version'],'template_content_sha256'=>$template['content_sha256'],'created_at'=>'2026-09-01 00:00:00']);
        $native=FMonitor2\InspectionEvidence\ProductionInspectionEvidenceFactory::create($h->db,new FMonitor2\InspectionEvidence\ProductionInspectionEvidenceConfig($p));
        $operation=InspectionFixture::operation(601);$accepted=$native->completeItem(new FMonitor2\InspectionEvidence\CompleteInspectionItem(73,6101,$operation['clientOperationId'],$operation['deviceInstallationId'],$operation['deviceTime'],0,1,28,[7001,7002]));assertSameValue('ACCEPTED',$accepted->status,'public native prerequisite');
        $operations=$h->rows('fm2_checklist_operations');$snapshots=$h->rows('fm2_checklist_operation_installers');
        $h->db->query("UPDATE {$p}fm2_workforce_catalog SET employment_status='dismissed',dismissal_effective_at='2026-09-09',fio='Changed current name' WHERE installer_tab_id=7001");
        $h->start();$h->login($f->cookies,73);$before=$h->facts();$projection=InspectionFixture::projection($f->page());$item=$projection['items']['28']['installers'][0];
        assertSameValue(['7001','Монтажник 7001','employed','dismissed','2026-09-09',true],[$item['tabId'],$item['fio'],$item['employmentStatusSnapshot'],$item['employmentStatus'],$item['dismissalEffectiveAt'],$item['currentlyAssigned']],'INTENDED_RED immutable snapshot plus current display overlay');
        assertSameValue($before,$h->facts(),'overlay GET is read-only');assertSameValue($operations,$h->rows('fm2_checklist_operations'),'recorded operations unchanged');assertSameValue($snapshots,$h->rows('fm2_checklist_operation_installers'),'recorded personnel unchanged');
        // Detaching one installer changes membership, never his stored accepted evidence.
        $newOrder=$h->rows('fm2_assignment_orders')[0];$newOrder['id']=8102;$newOrder['version_no']=2;$newOrder['kind']='change';$newOrder['previous_assignment_order_id']=8101;$h->insert($p.'fm2_assignment_orders',$newOrder);
        $retained=array_values(array_filter($h->rows('fm2_order_installers'),static fn($r)=>(int)$r['installer_tab_id']===7002))[0];$retained['assignment_order_id']=8102;$h->insert($p.'fm2_order_installers',$retained);
        $before=$h->facts();$projection=InspectionFixture::projection($f->page());$item=$projection['items']['28']['installers'][0];assertSameValue(['7001','employed',false],[$item['tabId'],$item['employmentStatus'],$item['currentlyAssigned']],'detached historical installer is not overwritten by current catalogue');assertSameValue($before,$h->facts(),'detached projection read only');assertSameValue($snapshots,$h->rows('fm2_checklist_operation_installers'),'detached accepted snapshots immutable');
        $h->noLegacy();
    }finally{if($f!==null)$f->close();}
}

$failures=[];
$scenarios=['configured Yii DB'=>inspectionConfiguredDatabase(...),'historical/current projection'=>inspectionHistoricalAndCurrentCrew(...)];
if(($argv[1]??'')==='projection')$scenarios=['historical/current projection'=>inspectionHistoricalAndCurrentCrew(...)];
foreach($scenarios as$name=>$scenario){try{$scenario();echo "PASS: $name\n";}catch(Throwable $error){$failures[]=$name.': '.$error->getMessage();fwrite(STDERR,"FAIL: ".end($failures)."\n");}}
if($failures!==[])throw new TestFailure(implode("\n",$failures));
echo "PASS: YII2-INSPECTION-JOURNEY-001 configured DB and projection boundaries\n";
