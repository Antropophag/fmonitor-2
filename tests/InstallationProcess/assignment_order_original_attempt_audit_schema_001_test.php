<?php

declare(strict_types=1);
// FMONITOR_TEST_DB: ATTEMPT-AUDIT-001 v0.4 sections6/8-11. Synthetic databases only.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditDatabase.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;
use FMonitor2\Tests\Support as S;
use FMonitor2\Tests\Support\OriginalAttemptAuditDatabase as A;

A::case('existing-v2-control',function($f){assertSameValue('unchanged',O\AssignmentOrderOriginalSchemaMigration::apply($f->db,$f->prefix)->status()->value,'existing v2 public setup really works');});
A::case('populated-v2-upgrade-repeat',function($f){
 $f->seedAccepted();$facts=$f->facts();$before=json_decode($f->catalog(),true,512,JSON_THROW_ON_ERROR);$table='fm2_assignment_order_original_audits';
 assertSameValue(['applied'=>true,'reason'=>null],A::migrate($f),'v2 to v3 exact result');assertSameValue($facts,$f->facts(),'all existing facts byte-identical');
 $after=json_decode($f->catalog(),true,512,JSON_THROW_ON_ERROR);assertSameValue($before[$table]['columns'],$after[$table]['columns'],'audit columns unchanged');
 $indexes=$f->db->query("SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) cols FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$f->prefix}{$table}' GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY INDEX_NAME")->fetch_all(MYSQLI_ASSOC);
 $mapped=array_values(array_filter($indexes,fn($v)=>$v['cols']==='request_id,status,reason_code'));assertSameValue(1,count($mapped),'one request index');assertSameValue(['idx_aoou_attempt_request',1],[$mapped[0]['INDEX_NAME'],(int)$mapped[0]['NON_UNIQUE']],'formerly unique audit key now exact nonunique');
 assertSameValue(0,(int)$f->db->query("SELECT COUNT(*) n FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$f->prefix}{$table}' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetch_assoc()['n'],'audit no longer requires terminal FK');
 $f->db->begin_transaction();
 try {
  foreach(['stream_failure','storage_failure'] as $reason)$f->db->query("INSERT INTO `{$f->prefix}{$table}` (request_id,actor_identity,mode,installation_case_id,assignment_order_id,status,reason_code,attempted_at_utc) VALUES ('00000000-0000-4000-8000-000000000702','18','initial',4512,81,'failed','{$reason}','2026-09-06 09:00:00')");
  $denied=false;try{$f->db->query("INSERT INTO `{$f->prefix}{$table}` (request_id,actor_identity,mode,installation_case_id,assignment_order_id,status,reason_code,attempted_at_utc) VALUES ('00000000-0000-4000-8000-000000000703','18','initial',4512,81,'failed','persistence_failure','2026-09-06 09:00:00')");}catch(mysqli_sql_exception $e){$denied=$e->getCode()===4025;}
  assertSameValue(true,$denied,'schema CHECK accepts only the two added retryable failure pairs');
 } finally { $f->db->rollback(); }
 unset($before[$table],$after[$table]);assertSameValue($before,$after,'other six tables structurally unchanged');
 $catalog=$f->catalog();assertSameValue(['applied'=>false,'reason'=>null],A::migrate($f),'v3 repeat exact');assertSameValue([$facts,$catalog],[$f->facts(),$f->catalog()],'repeat changes nothing');
});
foreach([false,true] as $partial)A::case($partial?'leading-root-partial':'empty-original-family',function($f)use($partial){
 $p=$partial?'partial_':'empty_';A::prefix($f,$p);
 if($partial)$f->db->query(O\AssignmentOrderOriginalSchemaMigration::ddl($p,'fm2_assignment_order_original_roots'));
 assertSameValue(['applied'=>true,'reason'=>null],A::migrate($f,$p),'empty/leading partial reaches full v3');
 assertSameValue(['applied'=>false,'reason'=>null],A::migrate($f,$p),'completed v3 repeat');
});
A::case('schema-other-drift-no-ddl',function($f){
 $f->db->query('ALTER TABLE `'.$f->prefix.'fm2_assignment_order_original_audits` ADD unexpected INT NULL');$before=[$f->facts(),$f->catalog()];
 assertSameValue(['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT'],A::migrate($f),'unknown column conflict');assertSameValue($before,[$f->facts(),$f->catalog()],'conflict preserves entire family');
});
A::case('active-caller-transaction',function($f){
 if(!class_exists(I\OriginalAttemptAuditSchemaMigration::class))throw new TestFailure('INTENDED_RED: public migration absent');
 $before=$f->facts();$f->db->begin_transaction();$f->db->query("UPDATE `{$f->prefix}fm2_process_tasks` SET due_date='2026-09-03' WHERE id=9001");$pending=$f->facts();assertSameValue(false,$before===$pending,'real pending control mutation');
 try{A::migrate($f);throw new TestFailure('Active transaction must refuse migration');}catch(I\DatabaseUnavailable $e){assertSameValue('Original attempt audit schema unavailable.',$e->getMessage(),'fixed active-transaction refusal');}
 assertSameValue(1,(int)$f->db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],'caller transaction still active');assertSameValue($pending,$f->facts(),'caller pending facts untouched');$f->db->rollback();assertSameValue($before,$f->facts(),'caller alone rolls back');
});
foreach(['before_audit_alter','after_audit_alter'] as $phase)A::case('interrupt-'.$phase,function($f)use($phase){
 if(!class_exists(I\OriginalAttemptAuditSchemaMigrationVerification::class))throw new TestFailure('INTENDED_RED: migration verification seam absent');
 $facts=$f->facts();$catalog=$f->catalog();$observer=new class($phase) implements I\OriginalAttemptAuditSchemaObserver {
  public array $events=[];public function __construct(private string $target){}public function observe(I\OriginalAttemptAuditSchemaPhase $phase):void{$this->events[]=$phase->value;if($phase->value===$this->target)throw new RuntimeException('synthetic interruption');}
 };
 try{I\OriginalAttemptAuditSchemaMigrationVerification::apply($f->db,$f->prefix,$observer);throw new TestFailure('Interruption must be unavailable');}catch(I\DatabaseUnavailable $e){assertSameValue('Original attempt audit schema unavailable.',$e->getMessage(),'fixed interruption');}
 assertSameValue($phase==='before_audit_alter'?['before_audit_alter']:['before_audit_alter','after_audit_alter'],$observer->events,'exact actual phases');assertSameValue($facts,$f->facts(),'interruption never rewrites facts');
 $lock=hash('sha256',$f->database."\0".$f->prefix."\0original-audit-v3");$other=$f->connect();try{assertSameValue(1,(int)$other->query("SELECT GET_LOCK('{$lock}',0) n")->fetch_assoc()['n'],'interrupted migration released named lock');$other->query("SELECT RELEASE_LOCK('{$lock}')");}finally{$other->close();}

 if($phase==='before_audit_alter')assertSameValue($catalog,$f->catalog(),'before ALTER preserves schema');
 assertSameValue(['applied'=>$phase==='before_audit_alter','reason'=>null],A::migrate($f),'retry resolves exact pre/post ALTER state');
});
A::case('named-lock-contention',function($f){
 if(!class_exists(I\OriginalAttemptAuditSchemaMigration::class))throw new TestFailure('INTENDED_RED: public migration absent');
 $lock=hash('sha256',$f->database."\0".$f->prefix."\0original-audit-v3");$other=$f->connect();$catalog=$f->catalog();$f->db->query('SET SESSION max_statement_time=8');
 try{assertSameValue(1,(int)$other->query("SELECT GET_LOCK('{$lock}',0) n")->fetch_assoc()['n'],'independent connection owns lock');$start=hrtime(true);try{A::migrate($f);throw new TestFailure('Contended migration must fail');}catch(I\DatabaseUnavailable $e){assertSameValue('Original attempt audit schema unavailable.',$e->getMessage(),'fixed contention failure');}assertSameValue(true,(hrtime(true)-$start)<5_500_000_000,'lock wait at most5 seconds plus0.5-second local scheduling allowance;6-second timeout cannot pass');assertSameValue($catalog,$f->catalog(),'contended migration no DDL');}finally{$other->query("SELECT RELEASE_LOCK('{$lock}')");$other->close();}
});
foreach([14,15,16,17,25] as $length)A::case('physical-prefix-'.$length,function($f)use($length){
 $p=str_repeat('p',$length);A::prefix($f,$p);$r=O\AssignmentOrderOriginalSchemaMigration::apply($f->db,$p);assertSameValue('applied',$r->status()->value,'public setup supports full configured prefix');
 $requestSuffix=$length===14?'fm2_assignment_order_original_maintenance_requests':'fm2_original_maintenance_requests';
 $auditSuffix=$length<=16?'fm2_assignment_order_original_maintenance_audits':'fm2_original_maintenance_audits';
 foreach([$requestSuffix,$auditSuffix] as $suffix)assertSameValue(true,O\AssignmentOrderOriginalSchemaMigration::exists($f->db,$p.$suffix),'literal physical suffix exists');
 $fks=$f->db->query("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND LEFT(TABLE_NAME,{$length})='{$p}'")->fetch_all(MYSQLI_ASSOC);assertSameValue(true,count($fks)>0,'real FK inventory is nonempty');foreach($fks as $fk)assertSameValue(true,strlen($fk['CONSTRAINT_NAME'])<=64,'all FK identifiers bounded');
 $physical=array_combine(S\OriginalIntegrityDatabase::ORIGINAL_TABLES,array_map(fn($suffix)=>$p.$suffix,S\OriginalIntegrityDatabase::ORIGINAL_TABLES));$physical['fm2_assignment_order_original_maintenance_requests']=$p.$requestSuffix;$physical['fm2_assignment_order_original_maintenance_audits']=$p.$auditSuffix;
 $literalFks=[['revisions','previous_revision_id','revisions','revision_id'],['revisions','root_original_id','roots','root_original_id'],['requests','current_revision_id','revisions','revision_id'],['requests','root_original_id','roots','root_original_id'],['events','revision_id','revisions','revision_id'],['events','root_original_id','roots','root_original_id'],['audits','request_id','requests','request_id'],['maintenance_audits','request_id','maintenance_requests','request_id']];$expectedFks=[];
 foreach($literalFks as [$owner,$column,$target,$targetColumn]){$logical='fm2_assignment_order_original_'.$owner;$ref='fm2_assignment_order_original_'.$target;$expectedFks[]=['fk_ao_'.substr(hash('sha256',$p."\0".$logical."\0".$column),0,48),$physical[$logical],$column,$physical[$ref],$targetColumn,'RESTRICT','RESTRICT'];}
 $rows=$f->db->query('SELECT k.CONSTRAINT_NAME,k.TABLE_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE()')->fetch_all(MYSQLI_ASSOC);
 $actualFks=array_map('array_values',array_values(array_filter($rows,fn($r)=>in_array($r['TABLE_NAME'],$physical,true))));sort($expectedFks);sort($actualFks);assertSameValue($expectedFks,$actualFks,'complete unique FK inventory and exact independently derived name/owner/column hash inputs');
 assertSameValue('unchanged',O\AssignmentOrderOriginalSchemaMigration::apply($f->db,$p)->status()->value,'mapped v2 semantic repeat');
 if($length===25){assertSameValue(['applied'=>true,'reason'=>null],A::migrate($f,$p),'v3 accepts mapped v2');assertSameValue(['applied'=>false,'reason'=>null],A::migrate($f,$p),'mapped v3 repeat');}
});
A::case('duplicate-physical-alias-conflicts',function($f){
 $f->db->query('CREATE TABLE `'.$f->prefix.'fm2_original_maintenance_requests` (unexpected INT) ENGINE=InnoDB');$catalog=$f->catalog();
 assertSameValue('conflict',O\AssignmentOrderOriginalSchemaMigration::apply($f->db,$f->prefix)->status()->value,'noncanonical duplicate alias cannot be selected by existence');assertSameValue($catalog,$f->catalog(),'canonical family unchanged on alias conflict');
});
A::case('capability-v5-predecessor-recognition',function($f){
 $before=[$f->facts(),$f->catalog()];$v3=I\ProcessUserCapabilitiesSchemaMigration::apply($f->db,$f->prefix);assertSameValue(['applied'=>false,'schemaVersion'=>3,'tablesCreated'=>[]],$v3,'v3 recognizes existing full v5');
 assertSameValue(['applied'=>false,'schemaVersion'=>4,'constraintsChanged'=>[]],I\ProcessCommandCapabilitiesSchemaMigration::apply($f->db,$f->prefix),'v4 never downgrades v5');
 assertSameValue(['state'=>'v5','capabilityConstraint'=>'ck_fm2_process_user_capability_v5'],I\ProcessCapabilityChecksClassifier::inspect($f->db,$f->prefix.'fm2_process_user_capabilities'),'exact v5 classifier');assertSameValue($before,[$f->facts(),$f->catalog()],'no data mutation');
});
foreach(['name','literal'] as $drift)A::case('capability-v5-'.$drift.'-drift',function($f)use($drift){
 $name=$drift==='name'?'unexpected_name':'ck_fm2_process_user_capability_v5';$extra=$drift==='literal'?",'unexpected.permission'":'';
 $f->db->query("ALTER TABLE `{$f->prefix}fm2_process_user_capabilities` DROP CONSTRAINT ck_fm2_process_user_capability_v5, ADD CONSTRAINT {$name} CHECK(capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer','assignment_order.original.upload','assignment_order.original.correct'{$extra}))");
 $before=$f->facts();assertSameValue('SCHEMA_MIGRATION_CONFLICT',I\ProcessCommandCapabilitiesSchemaMigration::apply($f->db,$f->prefix)['reason']??null,'only exact v5 successor allowed');assertSameValue($before,$f->facts(),'drift fail closed');
});
A::case('canonical-original-family13-repeat',function($f){
 [$exit,$result]=A::runCanonical($f);assertSameValue(0,$exit,'canonical applies original family');assertSameValue([true,19],[$result['ok']??null,$result['schemaVersion']??null],'canonical final version19, original remains step13');assertSameValue(true,in_array(13,$result['appliedVersions']??[],true),'original v3 actually applied');
 $facts=$f->facts();assertSameValue([0,['ok'=>true,'schemaVersion'=>19,'appliedVersions'=>[]]],A::runCanonical($f),'entire canonical runner repeats through current successors');assertSameValue($facts,$f->facts(),'repeat preserves original facts');
});
A::case('canonical-clean-prefix-isolation',function($f){
 $old=$f->prefix;$before=$f->facts();$f->prefix='fresh_';
 assertSameValue([0,['ok'=>true,'schemaVersion'=>19,'appliedVersions'=>range(1,19)]],A::runCanonical($f),'canonical clean installs every version including original');
 assertSameValue([0,['ok'=>true,'schemaVersion'=>19,'appliedVersions'=>[]]],A::runCanonical($f),'canonical clean repeat');
 $f->prefix=$old;assertSameValue($before,$f->facts(),'other prefix unchanged');
});
A::case('prefix25-maintenance-evidence-consumer',function($f){
 $p=str_repeat('m',25);A::prefix($f,$p);O\AssignmentOrderOriginalSchemaMigration::apply($f->db,$p);
 $clock=new O\AssignmentOrderOriginalFixedClock('2026-09-06T09:00:00Z');$faults=new class implements O\AssignmentOrderOriginalFaultInjector {public function before(O\AssignmentOrderOriginalFaultPoint $point):void{}};
 $maintenance=O\AssignmentOrderOriginalRealMaintenanceVerificationFactory::create($f->db,new O\AssignmentOrderOriginalProductionConfig($f->privateRoot,$p,$f->safeLog),new O\AssignmentOrderOriginalMaintenanceAuthorization('test-maintenance-01','assignment_order.original.storage.reconcile'),$clock,$faults);
 $c=new O\ReconcileAssignmentOrderOriginalPrivateOrphansCommand('00000000-0000-4000-8000-000000000709','test-maintenance-01','2026-09-06T07:00:00Z',10,null);
 $r=$maintenance->reconcileAssignmentOrderOriginalPrivateOrphans($c);assertSameValue(['completed',null,false,0,0,0,0,null],[$r->status()->value,$r->reason()?->value,$r->retryable(),$r->scanned(),$r->deleted(),$r->retained(),$r->failed(),$r->nextCursor()],'logical maintenance result at prefix25');
 $reader=O\AssignmentOrderOriginalEvidenceReaderFactory::create(new O\AssignmentOrderOriginalEvidenceReaderConfig($f->host,$f->port,$f->database,$f->user,$f->passwordFile,$p,$f->privateRoot,$f->safeLog));
 try{$requests=json_decode($reader->maintenanceRequestsCanonicalJson(),true,512,JSON_THROW_ON_ERROR);$audits=json_decode($reader->maintenanceAuditsCanonicalJson(),true,512,JSON_THROW_ON_ERROR);assertSameValue([1,1],[count($requests['items']),count($audits['items'])],'both logical evidence inventories visible');assertSameValue(['00000000-0000-4000-8000-000000000709','completed',0],[$requests['items'][0]['requestId'],$requests['items'][0]['status'],$audits['items'][0]['scanned']],'exact maintenance evidence');$before=[$requests,$audits];assertSameValue('replayed',$maintenance->reconcileAssignmentOrderOriginalPrivateOrphans($c)->status()->value,'public maintenance replay');assertSameValue($before,[json_decode($reader->maintenanceRequestsCanonicalJson(),true,512,JSON_THROW_ON_ERROR),json_decode($reader->maintenanceAuditsCanonicalJson(),true,512,JSON_THROW_ON_ERROR)],'replay keeps evidence');}finally{$reader->close();}
});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_SCHEMA_OK');
