<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support\RegisteredCompositionTestFixture as Fixture;

// ASSIGNMENT-ORDER-REGISTERED-COMPOSITION-READER-001 v0.1; exact public factory/reader only.
$passed=0;$failed=0;
function registeredCase(string $name, callable $test, string $kind='selection'):void
{
    global $passed,$failed;$f=null;$errors=[];
    try{$f=new Fixture($kind);$test($f);}catch(Throwable $e){$errors[]=$e->getMessage();}
    if($f!==null){try{$f->close();}catch(Throwable $e){$errors[]='CLEANUP: '.$e->getMessage();}}
    if($errors===[]){$passed++;echo "PASS $name\n";}else{$failed++;echo "FAIL $name: ".implode(' | ',$errors)."\n";}
}
function registeredReader(Fixture $f, ?mysqli $db=null):O\AssignmentOrderCompositionReader
{
    assertSameValue(true,is_callable([O\AssignmentOrderRegisteredCompositionReaderFactory::class,'create']),'RED_ASSERTION: registered composition reader is missing after native registry/schema setup');
    return O\AssignmentOrderRegisteredCompositionReaderFactory::create($db??$f->db);
}
function registeredTuple(O\AssignmentOrderCompositionSnapshot $s):array
{return[$s->status->value,$s->installationCaseId,$s->assignmentOrderId,$s->identity,$s->sha256,$s->installerIds,$s->controlEngineerUserId];}
function registeredEmpty(string $status,int $case=4512,int $order=81):array{return[$status,$case,$order,null,null,[],null];}
function registeredFound(int $order=81):array
{return['found',4512,$order,'composition-'.$order.'-v'.($order===81?'1':'2'),$order===81?'5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a':'1e6e0030b9f3cca92c36a741f87331f652490391ae773386673dc32f838d8bda',[$order===81?7001:7002],73];}
foreach(['selection','legacy'] as $kind){
    registeredCase('canonical '.$kind,static function(Fixture $f)use($kind):void{
        $before=$f->state();$reader=registeredReader($f);
        assertSameValue(registeredFound(),registeredTuple($reader->find(caseId:4512,orderId:81)),'independent fixed composition');
        if($kind==='selection'){assertSameValue(registeredFound(82),registeredTuple($reader->find(4512,82)),'replace_pending composition');}
        assertSameValue($before,$f->state(),'read/repeat preserves all rows schema and counters');
        assertSameValue(registeredFound(),registeredTuple($reader->find(4512,81)),'repeat read stable');
    },$kind);
}
registeredCase('absent registry and absent sources',static function(Fixture $f):void{
    $before=$f->state();assertSameValue(registeredEmpty('not_found',4512,99),registeredTuple(registeredReader($f)->find(4512,99)),'true absence');assertSameValue($before,$f->state(),'absence no repair');
},'empty');
foreach([
    'orphan selection'=>['selection',"SET FOREIGN_KEY_CHECKS=0","DELETE FROM fm2_assignment_order_identities WHERE assignment_order_id=81","SET FOREIGN_KEY_CHECKS=1"],
    'orphan legacy'=>['legacy',"DELETE FROM fm2_assignment_order_identities WHERE assignment_order_id=81"],
    'missing registered source'=>['selection',"SET FOREIGN_KEY_CHECKS=0","DELETE FROM fm2_assignment_order_selections WHERE assignment_order_id=81","SET FOREIGN_KEY_CHECKS=1"],
    'wrong discriminator'=>['selection',"UPDATE fm2_assignment_order_identities SET source_kind='legacy_order' WHERE assignment_order_id=81"],
    'wrong version'=>['selection',"UPDATE fm2_assignment_order_identities SET order_version=3 WHERE assignment_order_id=81"],
    'wrong source case'=>['selection',"SET FOREIGN_KEY_CHECKS=0","UPDATE fm2_assignment_order_selections SET installation_case_id=4513 WHERE assignment_order_id=81","SET FOREIGN_KEY_CHECKS=1"],
    'wrong hash'=>['selection',"UPDATE fm2_assignment_order_selections SET composition_sha256=REPEAT('a',64) WHERE assignment_order_id=81"],
    'member period'=>['selection',"UPDATE fm2_assignment_order_selection_members SET employed_from_snapshot='2026-09-03' WHERE assignment_order_id=81"],
    'member source instant'=>['selection',"UPDATE fm2_assignment_order_selection_members SET workforce_source_updated_at_snapshot='2026-02-31T00:00:00Z' WHERE assignment_order_id=81"],
    'legacy malformed temporal'=>['legacy',"UPDATE fm2_order_installers SET valid_from='2026-09-03' WHERE installer_tab_id=7001"],
    'legacy empty effective'=>['legacy',"DELETE FROM fm2_order_installers WHERE installer_tab_id=7001"],
    'missing registry table'=>['selection',"SET FOREIGN_KEY_CHECKS=0","DROP TABLE fm2_assignment_order_identities","SET FOREIGN_KEY_CHECKS=1"],
    'member query failure'=>['selection',"RENAME TABLE fm2_assignment_order_selection_members TO hidden_members"],
] as $name=>$commands){
    $kind=array_shift($commands);registeredCase($name,static function(Fixture $f)use($commands):void{
        foreach($commands as $sql){$f->db->query($sql);}$before=$f->state();
        assertSameValue(registeredEmpty('unavailable'),registeredTuple(registeredReader($f)->find(4512,81)),'exact source failure without fallback');assertSameValue($before,$f->state(),'no repairs');
    },$kind);
}
registeredCase('dual source',static function(Fixture $f):void{
    $f->db->query("INSERT INTO fm2_assignment_orders(id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id) VALUES(81,4512,1,'initial','prepared','2026-09-02',73,'Инженер','Инженер','individual','Адрес','2','77','2026-10-05','2026-12-20','2026-09-02T07:00:00Z',18)");
    $before=$f->state();assertSameValue(registeredEmpty('unavailable'),registeredTuple(registeredReader($f)->find(4512,81)),'dual source refused');assertSameValue($before,$f->state(),'dual history kept');
});
registeredCase('numeric member ordering',static function(Fixture $f):void{
    $f->db->query('DELETE FROM fm2_assignment_order_selection_members WHERE assignment_order_id=81');
    foreach(['10','9'] as $id){$row=$f->schema->example['rows']['fm2_assignment_order_selection_members'][0];$row['installer_tab_id']=$id;$f->schema->insert('fm2_assignment_order_selection_members',$row);}
    $hash=hash('sha256','{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":73,"installers":[9,10],"orderId":81}');
    $f->db->query("UPDATE fm2_assignment_order_selections SET composition_sha256='$hash' WHERE assignment_order_id=81");
    $before=$f->state();assertSameValue(['found',4512,81,'composition-81-v1',$hash,[9,10],73],registeredTuple(registeredReader($f)->find(4512,81)),'numeric rather than lexical order, no global request proof');assertSameValue($before,$f->state(),'no global history repair');
});
registeredCase('foreign-case no source authority',static function(Fixture $f):void{
    $restricted=$f->schema->source->restricted('SELECT');$admin=$f->schema->source->connect();
    try{
        $user=explode('@',$restricted->query('SELECT CURRENT_USER() n')->fetch_assoc()['n'])[0];
        assertSameValue(1,preg_match('/^aoir_ro_[0-9a-f]{12}$/D',$user),'exact owned grant target');$database=$f->schema->source->name;
        $admin->query("REVOKE SELECT ON `$database`.* FROM `$user`@`%`");$admin->query("GRANT SELECT ON `$database`.fm2_assignment_order_identities TO `$user`@`%`");$restricted->select_db($database);
        try{$restricted->query('SELECT * FROM fm2_assignment_order_selections');throw new TestFailure('source unexpectedly readable');}catch(mysqli_sql_exception $e){assertSameValue(1142,$e->getCode(),'native source read denied');}
        $reader=registeredReader($f,$restricted);assertSameValue(registeredEmpty('not_found',4513),registeredTuple($reader->find(4513,81)),'foreign case never probes denied source');
        assertSameValue(registeredEmpty('unavailable'),registeredTuple($reader->find(4512,81)),'same case observes denied source');
    }finally{$restricted->close();$admin->close();}
});
registeredCase('invalid prefix before connection use',static function(Fixture $f):void{
    $closed=$f->schema->source->connect();$closed->close();registeredReader($f);
    try{O\AssignmentOrderRegisteredCompositionReaderFactory::create($closed,str_repeat('p',26));throw new TestFailure('invalid prefix accepted');}
    catch(InvalidArgumentException $e){assertSameValue('Invalid registered composition reader configuration.',$e->getMessage(),'fixed prefix outcome');}
});
registeredCase('invalid IDs and closed connection',static function(Fixture $f):void{
    $closed=$f->schema->source->connect();$closed->close();$reader=registeredReader($f,$closed);
    assertSameValue(registeredEmpty('unavailable',0,-1),registeredTuple($reader->find(0,-1)),'invalid IDs echoed');
    assertSameValue(registeredEmpty('unavailable'),registeredTuple($reader->find(4512,81)),'closed native connection');
});
registeredCase('active caller transaction',static function(Fixture $f):void{
    $reader=registeredReader($f);$f->db->begin_transaction();try{
        $f->db->query("UPDATE other_prefix_marker SET value='pending' WHERE id=1");$f->db->query('SAVEPOINT caller_owned');
        assertSameValue(registeredEmpty('unavailable'),registeredTuple($reader->find(4512,81)),'caller txn rejected');
        $f->db->query('ROLLBACK TO SAVEPOINT caller_owned');assertSameValue('pending',$f->db->query('SELECT value FROM other_prefix_marker WHERE id=1')->fetch_assoc()['value'],'caller write still pending');
    }finally{$f->db->rollback();}
});
foreach(['registry_read','source_read','members_read','before_release'] as $phase){
    registeredCase('observer failure '.$phase,static function(Fixture $f)use($phase):void{
        registeredReader($f);$before=$f->state();$observer=new class($phase) implements O\AssignmentOrderRegisteredCompositionReadObserver{
            public array $seen=[];public function __construct(private string $stop){}
            public function observe(O\AssignmentOrderRegisteredCompositionReadPhase $phase):void{$this->seen[]=$phase->value;if($phase->value===$this->stop){throw new RuntimeException('synthetic observer error');}}
        };
        $reader=O\AssignmentOrderRegisteredCompositionReaderVerificationFactory::create($f->db,'',$observer);
        assertSameValue(registeredEmpty('unavailable'),registeredTuple($reader->find(4512,81)),'observer cannot publish partial result');
        $all=['registry_read','source_read','members_read'];$index=array_search($phase,$all,true);$expected=$index===false?$all:array_slice($all,0,$index+1);$expected[]='before_release';
        assertSameValue($expected,$observer->seen,'exact once-only release transcript');assertSameValue('0',(string)$f->db->query('SELECT @@in_transaction n')->fetch_assoc()['n'],'owned txn released');assertSameValue($before,$f->state(),'observer no data effects');
    });
}
registeredCase('consistent snapshot across source mutation',static function(Fixture $f):void{
    registeredReader($f);$other=$f->schema->source->connect($f->schema->source->name);
    try{$observer=new class($other) implements O\AssignmentOrderRegisteredCompositionReadObserver{
        public function __construct(private mysqli $other){}
        public function observe(O\AssignmentOrderRegisteredCompositionReadPhase $phase):void{if($phase===O\AssignmentOrderRegisteredCompositionReadPhase::REGISTRY_READ){$this->other->query("UPDATE fm2_assignment_order_selections SET composition_sha256=REPEAT('a',64) WHERE assignment_order_id=81");}}
    };
    $reader=O\AssignmentOrderRegisteredCompositionReaderVerificationFactory::create($f->db,'',$observer);
    assertSameValue(registeredFound(),registeredTuple($reader->find(4512,81)),'current consistent read sees prior immutable source');
    assertSameValue(registeredEmpty('unavailable'),registeredTuple(registeredReader($f)->find(4512,81)),'next snapshot detects external corruption');
    }finally{$other->close();}
});
echo "REGISTERED_COMPOSITION_READER passed=$passed failed=$failed\n";exit($failed===0?0:1);
