<?php

declare(strict_types=1);
// Specification: ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-001 v0.2 sections1-5,8-9.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditFixture.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

function auditResult(O\AssignmentOrderOriginalResult $r,string $status,string $reason,string $request="00000000-0000-4000-8000-000000000001"):void
{assertSameValue([$status,$reason,$status==='failed',$request,null,null,null,null,null,null,null],integrityTuple($r),'exact no-evidence audit result');}
function auditLog(S\OriginalAttemptAuditFixture $h,?string $event,?string $phase):void
{assertSameValue($event===null?[]:[[$event,['phase'=>$phase]]],$h->f->observers->logs,'exact safe event and only phase');}
function auditNoDisclosure(S\OriginalAttemptAuditFixture $h):void
{
 $f=$h->f;assertSameValue([0,0,0,0,0,0],[ $f->repository->terminalCalls,$f->repository->fingerprintCalls,$f->repository->lineageCalls,$f->compositionCalls,$f->fresh->opens,$h->stream->readCalls],'denial no confidential/file reads');
 assertSameValue([1,0,0,0],[$h->stream->closeCalls,$f->storage->beginCalls,count($f->repository->acceptedCalls),count($f->repository->attemptCalls)],'denial cleanup and dedicated writer only');
}
integrityCase('allowed-initial-control',function(){
 $h=new S\OriginalAttemptAuditFixture(['authorization'=>O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED]);$r=$h->run();assertSameValue(['accepted','00000000-0000-4000-8000-000000000001','original-0001','revision-0001',1],[$r->status()->value,$r->requestId(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber()],'public initial control');assertSameValue([], $h->writer->calls,'accepted commit does not use attempt writer');
});
integrityCase('allowed-terminal-replay-control',function(){
 $h=new S\OriginalAttemptAuditFixture(['authorization'=>O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED]);$a=$h->run();$h->next();$before=$h->f->trace->calls;$r=$h->run();$expected=integrityTuple($a);$expected[0]='replayed';assertSameValue($expected,integrityTuple($r),'public created terminal replays');assertSameValue(['authorize','request','stream.close'],array_slice($h->f->trace->calls,count($before)),'replay remains before time/audit/file');assertSameValue([], $h->writer->calls,'replay audit zero');
});
integrityCase('audit-public-declarations',function(){
 assertSameValue(true,interface_exists(O\AssignmentOrderOriginalAttemptAuditWriter::class),'named writer interface required');
 $params=(new ReflectionMethod(O\AssignmentOrderOriginalDependencies::class,'__construct'))->getParameters();
 assertSameValue('attemptAudits',end($params)->getName(),'named trailing dependency required');
 assertSameValue(['committed','rolled_back','outcome_unknown'],array_map(fn($v)=>$v->value,O\AssignmentOrderOriginalAuditWriteStatus::cases()),'closed outcomes');
});
foreach(['committed','rolled_back','outcome_unknown','throw'] as $outcome)foreach([false,true] as $throws){
 integrityCase('denial-'.$outcome.'-logger-'.(int)$throws,function()use($outcome,$throws){
  $h=new S\OriginalAttemptAuditFixture(['auditOutcome'=>$outcome,'logThrows'=>$throws]);$r=$h->run();
  $ok=$outcome==='committed';auditResult($r,$ok?'rejected':'failed',$ok?'authorization_denied':($outcome==='rolled_back'?'persistence_failure':'persistence_outcome_unknown'));
  auditNoDisclosure($h);assertSameValue(1,count($h->writer->calls),'one denied writer attempt');assertSameValue(1,$h->clock->calls,'one lazy clock');
  $a=$h->writer->calls[0][1];assertSameValue([true,$h->command->requestId,18,'initial',4512,81,'rejected','authorization_denied','2026-09-06T09:00:00Z'],[$h->writer->calls[0][0],$a->requestId,$a->actorUserId,$a->mode->value,$a->installationCaseId,$a->assignmentOrderId,$a->status->value,$a->reason->value,$a->attemptedAtUtc],'literal safe audit data');
  assertSameValue($ok?1:0,count($h->writer->rows),'only committed fake persists');
  auditLog($h,$ok?null:'ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_FAILED',$ok?null:'denial');
  assertSameValue($ok?['authorize','stream.close','clock','audit.denied']:['authorize','stream.close','clock','audit.denied','log:denial'],$h->f->trace->calls,'exact denial ordering, logger never repeats');
 });
}
integrityCase('correction-denial',function(){ $h=new S\OriginalAttemptAuditFixture(['correction'=>true]);auditResult($h->run(),'rejected','authorization_denied');auditNoDisclosure($h);assertSameValue(1,count($h->writer->rows),'one correction audit');assertSameValue('correction',$h->writer->rows[0]->mode->value,'correction audit mode'); });
integrityCase('denial-epoch',function(){ $h=new S\OriginalAttemptAuditFixture(['clock'=>'1970-01-01T00:00:00Z']);auditResult($h->run(),'rejected','authorization_denied');assertSameValue(1,count($h->writer->rows),'one epoch audit');assertSameValue('1970-01-01T00:00:00Z',$h->writer->rows[0]->attemptedAtUtc,'epoch audit persisted'); });
integrityCase('denial-clock-unavailable',function(){ $h=new S\OriginalAttemptAuditFixture(['clock'=>new RuntimeException('synthetic clock')]);auditResult($h->run(),'failed','persistence_failure');auditNoDisclosure($h);assertSameValue([],$h->writer->calls,'no audit without time');auditLog($h,'ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_FAILED','submission'); });
integrityCase('two-denials-then-grant',function(){
 $h=new S\OriginalAttemptAuditFixture();auditResult($h->run(),'rejected','authorization_denied');$terminal=$h->f->repository->terminal;
 $h->next();$h->clock->value='2026-09-06T09:01:00Z';auditResult($h->run(),'rejected','authorization_denied');
 assertSameValue($terminal,$h->f->repository->terminal,'first terminal unchanged');assertSameValue(['2026-09-06T09:00:00Z','2026-09-06T09:01:00Z'],array_map(fn($a)=>$a->attemptedAtUtc,$h->writer->rows),'two distinct attempt times');
 $h->next();$h->authorization=O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED;$before=$h->f->trace->calls;auditResult($h->run(),'rejected','authorization_denied');
 assertSameValue(['authorize','request','stream.close'],array_slice($h->f->trace->calls,count($before)),'granted retry replays old denial before audit/clock/file');
 assertSameValue(2,count($h->writer->calls),'no third audit');
});
integrityCase('accepted-revoke-restore',function(){
 $h=new S\OriginalAttemptAuditFixture(['authorization'=>O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED]);$accepted=$h->run();assertSameValue('accepted',$accepted->status()->value,'real public accepted control');$terminal=$h->f->repository->terminal;$facts=$h->f->repository->accepted;
 $h->next();$h->authorization=O\AssignmentOrderOriginalAuthorizationStatus::DENIED;$before=$h->f->trace->calls;auditResult($h->run(),'rejected','authorization_denied');
 assertSameValue(['authorize','stream.close','clock','audit.denied'],array_slice($h->f->trace->calls,count($before)),'revoke has no confidential lookup');assertSameValue(1,count($h->writer->rows),'one revoke audit');
 assertSameValue([$terminal,$facts],[$h->f->repository->terminal,$h->f->repository->accepted],'accepted terminal/facts preserved');
 $h->next();$h->authorization=O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED;$before=$h->f->trace->calls;$replay=$h->run();$expected=integrityTuple($accepted);$expected[0]='replayed';assertSameValue($expected,integrityTuple($replay),'restore exact accepted evidence replay');
 assertSameValue(['authorize','request','stream.close'],array_slice($h->f->trace->calls,count($before)),'restored replay no new audit');
});
foreach(['stream','storage'] as $family)foreach(['committed','rolled_back','outcome_unknown','throw'] as $outcome)foreach([false,true] as $throws){
 integrityCase($family.'-audit-'.$outcome.'-logger-'.(int)$throws,function()use($family,$outcome,$throws){
  $h=new S\OriginalAttemptAuditFixture(['authorization'=>O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED,'fileFailure'=>$family,'auditOutcome'=>$outcome,'logThrows'=>$throws]);auditResult($h->run(),'failed',$family.'_failure');
  assertSameValue(1,count($h->writer->calls),'one failure audit');assertSameValue(false,$h->writer->calls[0][0],'audit-only operation');assertSameValue($family.'_failure',$h->writer->calls[0][1]->reason->value,'selected reason retained');
  assertSameValue([[],[],[],1,1,1,0],[$h->f->repository->terminal,$h->f->repository->acceptedCalls,$h->f->repository->attemptCalls,$h->stream->closeCalls,$h->f->storage->stage->abortCalls,$h->f->storage->stage->closeCalls,$h->f->storage->stage->finalizeCalls],'once cleanup, no terminal/finalize');
  $calls=$h->f->trace->calls;assertSameValue(true,array_search('stream.close',$calls,true)<array_search('audit.failure',$calls,true),'cleanup before audit');
  auditLog($h,$outcome==='committed'?null:'ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_FAILED',$outcome==='committed'?null:'file_failure');
 });
}
foreach([false,true] as $throws){
 foreach(['authorization','lookup','terminal','unknown','accepted_unknown','accepted_rollback'] as $failure)integrityCase('diagnostic-'.$failure.'-logger-'.(int)$throws,function()use($failure,$throws){
  $options=['authorization'=>O\AssignmentOrderOriginalAuthorizationStatus::ALLOWED,'logThrows'=>$throws];
  if($failure==='authorization')$options['authorization']=O\AssignmentOrderOriginalAuthorizationStatus::UNAVAILABLE;
  if($failure==='lookup')$options['terminal']=new S\OriginalIntegrityLookup(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE);
  if(in_array($failure,['terminal','unknown'],true)){$options['confirmed']=false;$options['attemptOutcome']=$failure==='terminal'?O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK:O\AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;$options['freshOpen']='unavailable';}
  if(str_starts_with($failure,'accepted_')){$options['acceptedOutcome']=$failure==='accepted_unknown'?O\AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN:O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK;$options['freshOpen']='unavailable';}
  $h=new S\OriginalAttemptAuditFixture($options);auditResult($h->run(),'failed',in_array($failure,['unknown','accepted_unknown'],true)?'persistence_outcome_unknown':'persistence_failure');
  auditLog($h,in_array($failure,['terminal','unknown'],true)?'ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_FAILED':($failure==='accepted_unknown'?'ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_UNKNOWN':'ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_FAILED'),in_array($failure,['terminal','unknown'],true)?'terminal':'submission');
  assertSameValue([],$h->writer->calls,'no audit-only call for persistence failure');assertSameValue(1,$h->stream->closeCalls,'cleanup once');
 });
}
integrityCase('invalid-shape-control',function(){ $h=new S\OriginalAttemptAuditFixture(['request'=>'bad']);auditResult($h->run(),'rejected','invalid_command','bad');assertSameValue(['stream.close'],$h->f->trace->calls,'shape has no business/audit/clock/log'); });
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_OK');
