<?php

declare(strict_types=1);
// FMONITOR_TEST_DB: ATTEMPT-AUDIT-001 v0.4, public audit writer and terminal reader.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditRace.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditWorker.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;
use FMonitor2\Tests\Support\OriginalAttemptAuditDatabase as A;

function auditExtraRow(S\OriginalIntegrityDatabase $f,array $changes=[]):void
{
 $f->insert('fm2_assignment_order_original_audits',$changes+['request_id'=>'00000000-0000-4000-8000-000000000001','actor_identity'=>'19','mode'=>'initial','installation_case_id'=>4512,'assignment_order_id'=>81,'status'=>'rejected','reason_code'=>'authorization_denied','attempted_at_utc'=>'2026-09-06 09:01:00.000000']);
}
function auditLookup(S\OriginalIntegrityDatabase $f,string $id):O\AssignmentOrderOriginalResultLookup
{return (new O\AssignmentOrderOriginalMariaDbRepository($f->db,$f->prefix))->findTerminalRequest($id);}
A::case('reader-baseline-accepted-control',function($f){$f->seedAccepted();assertSameValue('found',auditLookup($f,'00000000-0000-4000-8000-000000000001')->status()->value,'existing full accepted backing');});
A::case('reader-accepted-extra-denial',function($f){
 $f->seedAccepted();$id='00000000-0000-4000-8000-000000000001';$before=integrityTuple(auditLookup($f,$id)->result());auditExtraRow($f);
 $r=auditLookup($f,$id);assertSameValue('found',$r->status()->value,'new valid denial does not corrupt prior accepted terminal');assertSameValue($before,integrityTuple($r->result()),'all accepted evidence unchanged');
});
foreach(['stream_failure','storage_failure'] as $reason)A::case('reader-valid-orphan-'.$reason,function($f)use($reason){
 $id='00000000-0000-4000-8000-000000000701';assertSameValue('not_found',auditLookup($f,$id)->status()->value,'empty public read control');
 // Existing reviewed fixture owns isolated constraint suspension solely to construct
 // the newly specified v3 row while the v3 migration is still missing.
 $f->corrupt(fn()=>auditExtraRow($f,['request_id'=>$id,'status'=>'failed','reason_code'=>$reason]));$before=$f->facts();
 assertSameValue('not_found',auditLookup($f,$id)->status()->value,'valid failure audit is not a terminal result');assertSameValue($before,$f->facts(),'read-only classification');
});
A::case('reader-malformed-extra-denial-backing',function($f){
 $f->seedRejected('authorization_denied');$id='00000000-0000-4000-8000-000000000301';assertSameValue('found',auditLookup($f,$id)->status()->value,'original denial backing control');
 $f->corrupt(fn()=>auditExtraRow($f,['request_id'=>$id,'status'=>'failed','reason_code'=>'stream_failure','actor_identity'=>'not-a-user']));
 assertSameValue('unavailable',auditLookup($f,$id)->status()->value,'malformed additional audit cannot be ignored after matching original');
});
A::case('reader-nonfailure-orphan-control',function($f){
 $f->corrupt(fn()=>auditExtraRow($f));assertSameValue('unavailable',auditLookup($f,'00000000-0000-4000-8000-000000000001')->status()->value,'unpaired denial remains corruption');
});
A::case('native-two-denials-one-terminal',function($f){
 $writer=A::writer($f);A::migrate($f);assertSameValue('committed',$writer->recordDenied(A::dto())->value,'first denial committed');$first=$f->rows('fm2_assignment_order_original_requests');
 assertSameValue(1,count($first),'exactly one terminal');assertSameValue(['rejected','authorization_denied'],[$first[0]['status'],$first[0]['reason_code']],'terminal is denial');
 assertSameValue('committed',$writer->recordDenied(A::dto(['actorUserId'=>19,'attemptedAtUtc'=>'2026-09-06T09:01:00Z']))->value,'repeat denial committed');assertSameValue($first,$f->rows('fm2_assignment_order_original_requests'),'old terminal byte-identical');
 $rows=A::auditRows($f);assertSameValue([[1,'18','2026-09-06 09:00:00.000000'],[2,'19','2026-09-06 09:01:00.000000']],array_map(fn($r)=>[(int)$r['audit_id'],$r['actor_identity'],$r['attempted_at_utc']],$rows),'each attempt has its own audit identity/actor/time');
 assertSameValue('found',auditLookup($f,'00000000-0000-4000-8000-000000000701')->status()->value,'first denial still has original matching audit');
 foreach(['roots','revisions','events'] as $suffix)assertSameValue([],$f->rows('fm2_assignment_order_original_'.$suffix),'denial creates no '.$suffix);
});
A::case('native-denial-preserves-accepted',function($f){
 $writer=A::writer($f);A::migrate($f);$f->seedAccepted();$id='00000000-0000-4000-8000-000000000001';$before=[];foreach(['requests','roots','revisions','events'] as $suffix)$before[$suffix]=$f->rows('fm2_assignment_order_original_'.$suffix);
 assertSameValue('committed',$writer->recordDenied(A::dto(['requestId'=>$id,'actorUserId'=>19]))->value,'denial after acceptance committed');
 foreach($before as $suffix=>$rows)assertSameValue($rows,$f->rows('fm2_assignment_order_original_'.$suffix),'accepted '.$suffix.' immutable');assertSameValue(2,count(A::auditRows($f)),'one additional denied invocation');
 assertSameValue('accepted',auditLookup($f,$id)->result()?->status()->value,'accepted terminal still readable by authorized reader');
});
foreach(['stream_failure','storage_failure'] as $reason)A::case('native-failure-only-'.$reason,function($f)use($reason){
 $writer=A::writer($f);A::migrate($f);$dto=A::dto(['status'=>O\AssignmentOrderOriginalStatus::FAILED,'reason'=>O\AssignmentOrderOriginalReason::from($reason)]);
 assertSameValue('committed',$writer->appendFailure($dto)->value,'failure audit commits');$rows=A::auditRows($f);assertSameValue(1,count($rows),'one failure row');assertSameValue(['failed',$reason],[$rows[0]['status'],$rows[0]['reason_code']],'exact failure pair');assertSameValue([],$f->rows('fm2_assignment_order_original_requests'),'no terminal failure');assertSameValue('not_found',auditLookup($f,$dto->requestId)->status()->value,'same request remains retryable');
});
foreach(['recordDenied','appendFailure'] as $method)foreach(['requestId'=>'bad','actorUserId'=>0,'installationCaseId'=>0,'assignmentOrderId'=>0,'attemptedAtUtc'=>'2026-02-30T09:00:00Z'] as $field=>$bad){
 integrityCase('native-invalid-'.$method.'-'.$field,function()use($method,$field,$bad){
  if(!class_exists(O\AssignmentOrderOriginalMariaDbAttemptAuditWriter::class))throw new TestFailure('INTENDED_RED: native audit writer absent');
  $db=new S\OriginalIntegrityNoSql();$writer=new O\AssignmentOrderOriginalMariaDbAttemptAuditWriter($db,'data_');$changes=[$field=>$bad];if($method==='appendFailure')$changes+=['status'=>O\AssignmentOrderOriginalStatus::FAILED,'reason'=>O\AssignmentOrderOriginalReason::STREAM_FAILURE];
  assertSameValue('rolled_back',$writer->$method(A::dto($changes))->value,'invalid DTO rejected');assertSameValue([],$db->calls,'invalid DTO zero SQL/escaping/transaction');
 });
}
foreach(['recordDenied','appendFailure'] as $method)foreach(['bad-prefix',str_repeat('p',26)] as $prefix)integrityCase('native-invalid-prefix-'.$method.'-'.strlen($prefix),function()use($method,$prefix){
 if(!class_exists(O\AssignmentOrderOriginalMariaDbAttemptAuditWriter::class))throw new TestFailure('INTENDED_RED: native writer absent');$db=new S\OriginalIntegrityNoSql();$writer=new O\AssignmentOrderOriginalMariaDbAttemptAuditWriter($db,$prefix);
 $changes=$method==='appendFailure'?['status'=>O\AssignmentOrderOriginalStatus::FAILED,'reason'=>O\AssignmentOrderOriginalReason::STREAM_FAILURE]:[];assertSameValue('rolled_back',$writer->$method(A::dto($changes))->value,'invalid prefix rejected before connection state');assertSameValue([],$db->calls,'invalid prefix zero SQL/escaping/transaction');
});
foreach(['recordDenied','appendFailure'] as $method)integrityCase('native-wrong-pair-'.$method,function()use($method){
 if(!class_exists(O\AssignmentOrderOriginalMariaDbAttemptAuditWriter::class))throw new TestFailure('INTENDED_RED: native writer absent');$db=new S\OriginalIntegrityNoSql();$writer=new O\AssignmentOrderOriginalMariaDbAttemptAuditWriter($db,'data_');
 $changes=$method==='recordDenied'?['status'=>O\AssignmentOrderOriginalStatus::FAILED,'reason'=>O\AssignmentOrderOriginalReason::STREAM_FAILURE]:[];assertSameValue('rolled_back',$writer->$method(A::dto($changes))->value,'method-specific status/reason closure');assertSameValue([],$db->calls,'wrong pair no SQL');
});
foreach(['recordDenied','appendFailure'] as $method)A::case('native-caller-transaction-'.$method,function($f)use($method){
 $observer=new class implements O\AssignmentOrderOriginalPersistenceObserver {public array $events=[];public function observe(O\AssignmentOrderOriginalPersistenceEvent $event):void{$this->events[]=$event->value;}};
 $writer=A::writer($f,$observer);A::migrate($f);$before=$f->facts();$f->db->begin_transaction();$f->db->query("UPDATE `{$f->prefix}fm2_process_tasks` SET due_date='2026-09-03' WHERE id=9001");$pending=$f->facts();assertSameValue(false,$before===$pending,'real caller pending control');
 $changes=$method==='appendFailure'?['status'=>O\AssignmentOrderOriginalStatus::FAILED,'reason'=>O\AssignmentOrderOriginalReason::STREAM_FAILURE]:[];
 assertSameValue('rolled_back',$writer->$method(A::dto($changes))->value,'active borrowed connection refused');assertSameValue([],$observer->events,'no observer before owned transaction');assertSameValue(1,(int)$f->db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],'caller still active');assertSameValue($pending,$f->facts(),'pending facts preserved');$f->db->rollback();assertSameValue($before,$f->facts(),'caller alone rolls back');
});
foreach(['recordDenied','appendFailure'] as $method)foreach(['before_write_begin','before_native_commit','after_native_commit','rollback_observer'] as $fault)A::case('native-'.$method.'-'.$fault,function($f)use($method,$fault){
 $observer=new class($fault) implements O\AssignmentOrderOriginalPersistenceObserver {
  public array $events=[];public function __construct(private string $fault){}public function observe(O\AssignmentOrderOriginalPersistenceEvent $event):void{$this->events[]=$event->value;if($event->value===$this->fault||($this->fault==='rollback_observer'&&in_array($event->value,['before_native_commit','before_write_rollback'],true)))throw new RuntimeException('synthetic observer failure');}
 };
 $writer=A::writer($f,$observer);A::migrate($f);$changes=$method==='appendFailure'?['status'=>O\AssignmentOrderOriginalStatus::FAILED,'reason'=>O\AssignmentOrderOriginalReason::STREAM_FAILURE]:[];
 $r=$writer->$method(A::dto($changes));assertSameValue(in_array($fault,['after_native_commit','rollback_observer'],true)?'outcome_unknown':'rolled_back',$r->value,'native commit/rollback acknowledgement exact');
 $durable=$fault==='after_native_commit';assertSameValue($durable?1:0,count(A::auditRows($f)),'actual committed-or-rolled-back audit');assertSameValue($durable&&$method==='recordDenied'?1:0,count($f->rows('fm2_assignment_order_original_requests')),'terminal exists only for committed denial');assertSameValue(0,(int)$f->db->query('SELECT @@in_transaction active')->fetch_assoc()['active'],'owned transaction ended even when rollback observer throws');
});
A::case('race-harness-existing-repository-control',function($f){
 $race=new S\OriginalAttemptAuditRace();try{$a=$race->start($f,'accepted','hold');assertSameValue([],$f->rows('fm2_assignment_order_original_requests'),'existing repository holds uncommitted fixture');$race->release($a);assertSameValue("RESULT committed\n",$race->result($a),'working native child/observer/pipe control');assertSameValue('found',auditLookup($f,'00000000-0000-4000-8000-000000000701')->status()->value,'child created coherent existing accepted backing');}finally{$race->close();}
});
foreach(['denied','accepted'] as $first)A::case('real-concurrent-'.$first.'-versus-denied',function($f)use($first){
 A::writer($f);A::migrate($f);$race=new S\OriginalAttemptAuditRace();
 try{
  $a=$race->start($f,$first,'hold');$b=$race->start($f,'denied','signal');
  assertSameValue([],$f->rows('fm2_assignment_order_original_requests'),'A still holds uncommitted request while B has entered its writer');assertSameValue([],A::auditRows($f),'no partial audit publication');
  $race->release($a);assertSameValue("RESULT committed\n",$race->result($a),'A confirmed commit');assertSameValue("RESULT committed\n",$race->result($b),'B confirms separate denial audit');
  $requests=$f->rows('fm2_assignment_order_original_requests');$audits=A::auditRows($f);assertSameValue([1,2],[count($requests),count($audits)],'one terminal and two attempts in both overlaps');
  assertSameValue($first==='accepted'?'accepted':'rejected',$requests[0]['status'],'first terminal preserved');assertSameValue(['18','19'],array_column($audits,'actor_identity'),'audits remain independently attributable');
  assertSameValue('found',auditLookup($f,'00000000-0000-4000-8000-000000000701')->status()->value,'complete terminal backing after concurrency');
 }finally{$race->close();}
});
A::case('production-factory-records-denial',function($f){
 A::migrate($f);$fresh=new class implements O\AssignmentOrderOriginalFreshTerminalReaderFactory{public int $opens=0;public function open():O\AssignmentOrderOriginalFreshTerminalReaderOpenResult{++$this->opens;return O\AssignmentOrderOriginalFreshTerminalReaderOpenResult::unavailable();}};
 $app=O\ProductionAssignmentOrderOriginalFactory::createRecoveryReady($f->db,new O\AssignmentOrderOriginalProductionConfig($f->privateRoot,$f->prefix,$f->safeLog),$fresh);
 $trace=new S\OriginalIntegrityTrace();$stream=new S\OriginalIntegrityStream(S\OriginalIntegrityFixture::pdf(),$trace);$id='00000000-0000-4000-8000-000000000710';
 $command=new O\SubmitAssignmentOrderOriginalCommand($id,O\AssignmentOrderOriginalMode::INITIAL,4512,81,999,'2026-09-01',true,null,null,null,null,new O\AssignmentOrderOriginalUpload($stream,'original.pdf','application/pdf'));
 $before=gmdate('Y-m-d H:i:s');$result=$app->submitAssignmentOrderOriginal($command);$after=gmdate('Y-m-d H:i:s');assertSameValue(['rejected','authorization_denied',false,$id,null,null,null,null,null,null,null],integrityTuple($result),'actual production composition persists denial');
 $rows=A::auditRows($f);assertSameValue(1,count($rows),'real factory bound real writer');assertSameValue(['999',$id,'rejected','authorization_denied'],[$rows[0]['actor_identity'],$rows[0]['request_id'],$rows[0]['status'],$rows[0]['reason_code']],'actual caller recorded');$at=substr($rows[0]['attempted_at_utc'],0,19);assertSameValue(true,$at>=$before&&$at<=$after,'production clock independently bounded by test UTC instants');assertSameValue([0,1,0],[$stream->readCalls,$stream->closeCalls,$fresh->opens],'denial unread cleanup and no confidential recovery');
});
A::case('real-worker-records-denial',function($f){
 A::migrate($f);$out=S\OriginalAttemptAuditWorker::denied($f);$expected='{"status":"rejected","reasonCode":"authorization_denied","retryable":false,"requestId":"00000000-0000-4000-8000-000000000711","rootOriginalId":null,"currentRevisionId":null,"revisionNumber":null,"documentDate":null,"sha256":null,"byteSize":null,"uploadedAt":null}'."\n";
 assertSameValue([0,'','','',$expected],[$out['exit'],$out['barrier'],$out['stdout'],$out['stderr'],$out['result']],'public worker exact denial channels');$rows=A::auditRows($f);assertSameValue(1,count($rows),'worker really persisted audit');assertSameValue(['999','2026-09-06 09:00:00.000000'],[$rows[0]['actor_identity'],$rows[0]['attempted_at_utc']],'worker actor and injected application clock exact');
});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_MARIADB_OK');
