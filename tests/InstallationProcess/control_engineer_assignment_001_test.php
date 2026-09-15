<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/PreopeningFixture.php';

use FMonitor2\InstallationProcess\ControlEngineerAssignmentClock;
use FMonitor2\InstallationProcess\ControlEngineerAssignmentCommand;
use FMonitor2\InstallationProcess\MariaDbControlEngineerAssignmentReader;
use FMonitor2\InstallationProcess\ProductionControlEngineerAssignmentFactory;

final class FixedAssignmentClock implements ControlEngineerAssignmentClock
{
    public int $calls=0;
    public function now(): string { $this->calls++; return '2026-09-15T09:30:00Z'; }
}

function assignmentOutcome(array $result): array { return [$result['status'],$result['reasonCode']]; }

$f=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
    assertSameValue(1,(int)$f->db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$p}fm2_control_engineer_assignments'")->fetch_column(),'one canonical history table');
    $ddl=$f->db->query("SHOW CREATE TABLE {$p}fm2_control_engineer_assignments")->fetch_row()[1];
    foreach(['assignment_sequence','previous_assignment_id','previous_engineer_user_id','bootstrap_application_id','request_fingerprint']as$column)assertSameValue(true,str_contains($ddl,$column),'schema '.$column);
    $f->db->query("DELETE FROM {$p}fm2_control_engineer_assignments");
    $f->insert($p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'control_engineer.assign']);
    foreach([[74,'Инженер B',2,1],[75,'Инженер C',2,1],[76,'Не инженер',5,1],[77,'Неактивный инженер',2,0]]as[$id,$name,$role,$active]){
        $f->insert($p.'fm2_pilot_users',['user_id'=>$id,'full_name'=>$name,'email'=>'engineer'.$id.'@example.test','status'=>$active,'activation_state'=>'active','session_version'=>1,'source_updated_at'=>'2026-09-15T09:00:00+03:00']);
        $f->insert($p.'fm2_pilot_user_roles',['user_id'=>$id,'role_id'=>$role,'origin'=>'fixture','assigned_at'=>'2026-09-15T09:00:00+03:00']);
    }
    $reader=new MariaDbControlEngineerAssignmentReader($f->db,$p);
    $emptyFacts=$f->facts();assertSameValue(['missing',0,null],[$reader->read(4512)['status'],$reader->read(4512)['revision'],$reader->read(4512)['engineer']],'no legacy/arbitrary fallback');assertSameValue($emptyFacts,$f->facts(),'missing read-only');

    $clock=new FixedAssignmentClock();$owner=ProductionControlEngineerAssignmentFactory::create($f->db,$p,$clock);
    $clockBeforeInvalid=$clock->calls;foreach([['bad',4512,73,0,18],['52525252-0001-4525-8525-000000000099',0,73,0,18],['52525252-0001-4525-8525-000000000099',4512,0,0,18],['52525252-0001-4525-8525-000000000099',4512,73,-1,18],['52525252-0001-4525-8525-000000000099',4512,73,2147483648,18],['52525252-0001-4525-8525-000000000099',4512,73,0,0]]as$args){$factsBeforeInvalid=$f->facts();$thrown=false;try{new ControlEngineerAssignmentCommand(...$args);}catch(InvalidArgumentException){$thrown=true;}assertSameValue(true,$thrown,'invalid command rejected at typed boundary');assertSameValue($factsBeforeInvalid,$f->facts(),'invalid command performs no SQL writes');}assertSameValue($clockBeforeInvalid,$clock->calls,'invalid commands do not call clock');assertSameValue($emptyFacts,$f->facts(),'invalid commands preserve all facts');
    $firstRequest='52525252-0001-4525-8525-000000000000';
    $a=$owner->assign(new ControlEngineerAssignmentCommand($firstRequest,4512,73,0,18));
    assertSameValue(['assigned',null],assignmentOutcome($a),'fixture prerequisite standalone A');
    assertSameValue([1,73,18,'2026-09-15T09:30:00Z'],[$a['assignment']['revision'],$a['assignment']['engineer']['userId'],$a['assignment']['assignedByUserId'],$a['assignment']['assignedAt']],'A payload');

    // Produce one historical native application through existing public HTTP, then remove only fixture-owned standalone setup to simulate pre-#52 data.
    $f->start();$cookies=[];assertSameValue(303,$f->login($cookies,18)['status'],'login');assertSameValue(303,$f->selection($cookies)['status'],'A selection');
    $receipt=$f->upload($cookies,$f->metadata($cookies));assertSameValue(201,$receipt['status'],'A original');$original=json_decode($receipt['body'],true,flags:JSON_THROW_ON_ERROR);
    assertSameValue(303,$f->form('/pilot/objects/4512/execution',['_csrf'=>$f->token($cookies),'action'=>'apply','requestId'=>'44444444-0052-4444-8444-000000000001','orderId'=>'81','revisionId'=>$original['currentRevisionId'],'sequence'=>'0'],$cookies)['status'],'A application');
    $application=$f->rows('fm2_assignment_order_applications')[0];$applicationBytes=json_encode($application,JSON_THROW_ON_ERROR);
    $f->db->query("DELETE FROM {$p}fm2_control_engineer_assignments");

    $beforeBootstrap=$f->facts();$bootstrap=$reader->read(4512);
    assertSameValue(['found',0,73,'native_application_bootstrap',(int)$application['application_id']],[$bootstrap['status'],$bootstrap['revision'],$bootstrap['engineer']['userId'],$bootstrap['provenance'],$bootstrap['bootstrapApplicationId']],'A application bootstrap');
    assertSameValue($beforeBootstrap,$f->facts(),'bootstrap read creates no facts');

    $badApplication=$application;$f->db->query("UPDATE {$p}fm2_assignment_order_applications SET control_engineer_user_id=74 WHERE application_id=".(int)$application['application_id']);
    assertSameValue('unavailable',$reader->read(4512)['status'],'malformed bootstrap fail closed');
    $f->db->query("UPDATE {$p}fm2_assignment_order_applications SET control_engineer_user_id=73 WHERE application_id=".(int)$application['application_id']);

    $requestB='52525252-0001-4525-8525-000000000001';
    $b=$owner->assign(new ControlEngineerAssignmentCommand($requestB,4512,74,0,18));
    $rowB=$f->rows('fm2_control_engineer_assignments')[0];
    $expectedFingerprint=hash('sha256',json_encode([$requestB,4512,74,0,18],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    assertSameValue(['assigned',1,74,73,null,(int)$application['application_id'],18,'2026-09-15 09:30:00',$expectedFingerprint],[$b['status'],(int)$rowB['assignment_sequence'],(int)$rowB['engineer_user_id'],(int)$rowB['previous_engineer_user_id'],$rowB['previous_assignment_id'],(int)$rowB['bootstrap_application_id'],(int)$rowB['assigned_by_user_id'],$rowB['assigned_at_utc'],$rowB['request_fingerprint']],'bootstrap A to standalone B exact row');
    assertSameValue('Инженер B',$rowB['engineer_fio_snapshot'],'B FIO snapshot');assertSameValue('Инженер строительного контроля',$rowB['engineer_position_snapshot'],'B position snapshot');
    assertSameValue($applicationBytes,json_encode($f->rows('fm2_assignment_order_applications')[0],JSON_THROW_ON_ERROR),'historical A application unchanged');
    $f->db->query("UPDATE {$p}fm2_control_engineer_assignments SET previous_engineer_user_id=74 WHERE assignment_id=".(int)$rowB['assignment_id']);assertSameValue('unavailable',$reader->read(4512)['status'],'bootstrap previous engineer must match referenced application snapshot');$f->db->query("UPDATE {$p}fm2_control_engineer_assignments SET previous_engineer_user_id=73 WHERE assignment_id=".(int)$rowB['assignment_id']);
    $f->db->query("UPDATE {$p}fm2_assignment_order_applications SET control_engineer_user_id=74 WHERE application_id=".(int)$application['application_id']);assertSameValue('unavailable',$reader->read(4512)['status'],'standalone bootstrap application integrity is revalidated');$f->db->query("UPDATE {$p}fm2_assignment_order_applications SET control_engineer_user_id=73 WHERE application_id=".(int)$application['application_id']);
    assertSameValue(['replayed',1],[$owner->assign(new ControlEngineerAssignmentCommand($requestB,4512,74,0,18))['status'],$owner->assign(new ControlEngineerAssignmentCommand($requestB,4512,74,0,18))['assignment']['revision']],'authorized replay');
    assertSameValue(1,count($f->rows('fm2_control_engineer_assignments')),'replay no row');

    $negative=[
        ['request conflict',new ControlEngineerAssignmentCommand($requestB,4512,75,1,18),['conflict','request_id_conflict']],
        ['stale',new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000002',4512,75,0,18),['conflict','assignment_changed']],
        ['unauthorized',new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000003',4512,75,1,95),['rejected','authorization_denied']],
        ['missing object',new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000004',999999,75,0,18),['rejected','object_not_found']],
        ['missing engineer',new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000005',4512,999999,1,18),['rejected','engineer_not_eligible']],
        ['wrong role',new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000006',4512,76,1,18),['rejected','engineer_not_eligible']],
        ['inactive',new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000007',4512,77,1,18),['rejected','engineer_not_eligible']],
        ['no changes',new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000008',4512,74,1,18),['rejected','no_changes']],
    ];
    foreach($negative as[$label,$command,$expected]){$before=$f->facts();assertSameValue($expected,assignmentOutcome($owner->assign($command)),$label);assertSameValue($before,$f->facts(),$label.' no facts');}
    $f->db->query("DELETE FROM {$p}fm2_pilot_role_permissions WHERE role_id=1 AND permission='control_engineer.assign'");$beforeGrant=$f->facts();assertSameValue(['rejected','authorization_denied'],assignmentOutcome($owner->assign(new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000010',4512,75,1,18))),'exact permission required');assertSameValue($beforeGrant,$f->facts(),'near permission denial no facts');$f->insert($p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'control_engineer.assign']);
    $f->db->query("UPDATE {$p}fm2_pilot_users SET status=0 WHERE user_id=18");$inactiveFacts=$f->facts();assertSameValue(['rejected','authorization_denied'],assignmentOutcome($owner->assign(new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000011',4512,75,1,18))),'inactive actor');assertSameValue($inactiveFacts,$f->facts(),'inactive actor no facts');$f->db->query("UPDATE {$p}fm2_pilot_users SET status=1 WHERE user_id=18");
    $hold=$table=$p.'fm2_control_engineer_assignments';$f->db->query("RENAME TABLE {$hold} TO {$hold}_hold");try{assertSameValue('unavailable',$reader->read(4512)['status'],'missing dependency read unavailable');assertSameValue(['failed','dependency_unavailable'],assignmentOutcome($owner->assign(new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000012',4512,75,1,18))),'missing dependency command unavailable');}finally{$f->db->query("RENAME TABLE {$hold}_hold TO {$hold}");}
    $f->db->query("ALTER TABLE {$hold} DROP INDEX uq_control_request");try{$driftFacts=$f->facts();assertSameValue('unavailable',$reader->read(4512)['status'],'schema key drift read unavailable');assertSameValue(['failed','dependency_unavailable'],assignmentOutcome($owner->assign(new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000014',4512,75,1,18))),'schema key drift command unavailable');assertSameValue($driftFacts,$f->facts(),'schema drift creates no facts');}finally{$f->db->query("ALTER TABLE {$hold} ADD UNIQUE KEY uq_control_request(request_id)");}
    $caseFk=$p.'control_assignment_case_fk';$f->db->query("ALTER TABLE {$hold} DROP FOREIGN KEY `{$caseFk}`");try{$fkDriftFacts=$f->facts();assertSameValue('unavailable',$reader->read(4512)['status'],'missing case FK read unavailable');assertSameValue(['failed','dependency_unavailable'],assignmentOutcome($owner->assign(new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000015',4512,75,1,18))),'missing case FK command unavailable');assertSameValue($fkDriftFacts,$f->facts(),'missing case FK creates no facts');}finally{$f->db->query("ALTER TABLE {$hold} ADD CONSTRAINT `{$caseFk}` FOREIGN KEY(installation_case_id) REFERENCES `{$p}fm2_installation_cases`(id)");}
    $f->db->query("CREATE TRIGGER {$p}fm2_control_assignment_fault BEFORE INSERT ON {$hold} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic persistence fault'");try{$beforeFault=$f->facts();assertSameValue(['failed','persistence_failure'],assignmentOutcome($owner->assign(new ControlEngineerAssignmentCommand('52525252-0001-4525-8525-000000000013',4512,75,1,18))),'insert failure safe');assertSameValue($beforeFault,$f->facts(),'persistence failure rollback');}finally{$f->db->query("DROP TRIGGER {$p}fm2_control_assignment_fault");}

    $requestC='52525252-0001-4525-8525-000000000009';$c=$owner->assign(new ControlEngineerAssignmentCommand($requestC,4512,75,1,18));$rows=$f->rows('fm2_control_engineer_assignments');
    assertSameValue([1,2],array_map('intval',array_column($rows,'assignment_sequence')),'B C sequences');assertSameValue([(int)$rows[0]['assignment_id'],74],[(int)$rows[1]['previous_assignment_id'],(int)$rows[1]['previous_engineer_user_id']],'C lineage to B');
    assertSameValue(['found',2,75,'standalone'],[$reader->read(4512)['status'],$reader->read(4512)['revision'],$reader->read(4512)['engineer']['userId'],$reader->read(4512)['provenance']],'current C');
    $bBytes=json_encode($rows[0],JSON_THROW_ON_ERROR);$f->db->query("UPDATE {$p}fm2_control_engineer_assignments SET previous_engineer_user_id=73 WHERE assignment_sequence=2");assertSameValue('unavailable',$reader->read(4512)['status'],'corrupt standalone lineage fail closed');$f->db->query("UPDATE {$p}fm2_control_engineer_assignments SET previous_engineer_user_id=74 WHERE assignment_sequence=2");assertSameValue($bBytes,json_encode($f->rows('fm2_control_engineer_assignments')[0],JSON_THROW_ON_ERROR),'B history unchanged');
    assertSameValue(1,$clock->calls>=3?1:0,'clock used only accepted attempts');
    echo "PASS: YII2-CONTROL-ENGINEER-ASSIGNMENT-001 bootstrap owner history failures\n";
} finally {if($f instanceof PreopeningFixture)$f->close();}
