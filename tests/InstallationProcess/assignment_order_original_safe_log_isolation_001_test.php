<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__, 2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalInitialProcessState.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalInitialFixture.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalLogIsolationFixture.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

// SAFE-LOG-ISOLATION-001 v0.1: literal Example A, not a reference production run.
$pdf = base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK', true);
assertSameValue(327, strlen($pdf), 'Literal byte size');
assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784', hash('sha256',$pdf), 'Literal digest');
function isolationTuple(O\AssignmentOrderOriginalResult $r): array
{ return [$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->requestId(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize(),$r->uploadedAt()]; }
function isolationExpected(string $id, string $status, ?string $reason, bool $retry=false): array
{ return [$status,$reason,$retry,$id,...($status==='accepted' ? ['original-0001','revision-0001',1,'2026-09-01','4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',327,'2026-09-02T09:15:30Z'] : array_fill(0,7,null))]; }
function isolationCommand(string $id, O\AssignmentOrderOriginalByteStream $stream): O\SubmitAssignmentOrderOriginalCommand
{ return new O\SubmitAssignmentOrderOriginalCommand($id,O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,'2026-09-01',true,null,null,null,null,new O\AssignmentOrderOriginalUpload($stream,'original.pdf','application/pdf')); }
function isolationGraph(string $fault, bool $invalid, bool $conflict=false, bool $auditFails=false, bool $generic=false): array
{
    $trace=new S\OriginalLogIsolationTrace(); $logger=new S\OriginalLogIsolationLogger($trace);
    $repo=new S\OriginalLogIsolationRepository($trace,$conflict,$auditFails);
    $storage=new S\OriginalLogIsolationStorage($trace,$fault);
    $process=new S\AssignmentOrderOriginalInitialProcessState(); $observers=new S\AssignmentOrderOriginalInitialObservers();
    $inspector=new class($invalid) implements O\AssignmentOrderOriginalPdfInspector {
        public function __construct(private bool $invalid) {}
        public function inspect(string $completedBytes): O\AssignmentOrderOriginalPdfInspection { return $this->invalid ? O\AssignmentOrderOriginalPdfInspection::invalid() : O\AssignmentOrderOriginalPdfInspection::passive(); }
        public function algorithmId(): string { return 'fmonitor-passive-pdf-v1'; }
    };
    $delivery=new class($trace) implements O\AssignmentOrderOriginalResultDeliveryObserver {
        public array $results=[];
        public function __construct(private S\OriginalLogIsolationTrace $trace) {}
        public function afterCommitBeforeReturn(O\AssignmentOrderOriginalResult $result): void { $this->trace->add('delivery'); $this->results[]=isolationTuple($result); }
    };
    $supplied=$generic ? new class($logger) implements O\AssignmentOrderOriginalSafeLogObserver {
        public function __construct(private S\OriginalLogIsolationLogger $logger) {}
        public function record(string $event,array $safeFields):void { $this->logger->record($event,$safeFields); }
    } : $logger;
    $dependencies=new O\AssignmentOrderOriginalDependencies(new S\AssignmentOrderOriginalInitialAuthorizer(),new S\AssignmentOrderOriginalInitialCompositionReader($process),new S\AssignmentOrderOriginalInitialClock(),new S\AssignmentOrderOriginalInitialIds(),$inspector,$storage,$repo,$observers,$observers,$observers,$supplied,$delivery);
    $app=O\AssignmentOrderOriginalVerificationFactory::create($dependencies);
    return compact('app','dependencies','trace','logger','repo','storage','delivery');
}
$failures=[]; $passed=0;
function isolationCase(string $name, callable $run): void
{
    global $failures,$passed;
    try { $run(); ++$passed; echo "PASS $name\n"; }
    catch (Throwable $error) { $failures[]=$name; echo "FAIL $name: ".$error->getMessage()."\n"; }
}
foreach (['abort_failed'=>'stage_abort','abort_throw'=>'stage_abort','stage_close'=>'stage_close','stream_close'=>'stream_close','all_cleanup'=>null] as $fault=>$phase) {
    isolationCase('invalid-'.$fault,function()use($fault,$phase,$pdf){
        $g=isolationGraph($fault,true); extract($g); $id='00000000-0000-4000-8000-000000000301';
        $stream=new S\OriginalLogIsolationStream($pdf,$trace,in_array($fault,['stream_close','all_cleanup'],true));
        $result=$app->submitAssignmentOrderOriginal(isolationCommand($id,$stream));
        assertSameValue(isolationExpected($id,'rejected','invalid_pdf'),isolationTuple($result),'full rejected result');
        $expected=['stage.abort']; if(in_array($fault,['abort_failed','abort_throw','all_cleanup'],true))$expected[]='log:stage_abort';
        $expected[]='stage.close'; if(in_array($fault,['stage_close','all_cleanup'],true))$expected[]='log:stage_close';
        $expected[]='stream.close'; if(in_array($fault,['stream_close','all_cleanup'],true))$expected[]='log:stream_close'; $expected[]='attempt.commit';
        assertSameValue($expected,$trace->calls,'cleanup/log/audit exact order, each once');
        $phases=$phase===null?['stage_abort','stage_close','stream_close']:[$phase];
        $events=['stage_abort'=>'STAGE_ABORT','stage_close'=>'STAGE_CLOSE','stream_close'=>'STREAM_CLOSE'];
        assertSameValue(array_map(fn($p)=>['ASSIGNMENT_ORDER_ORIGINAL_'.$events[$p].'_FAILED',['phase'=>$p],$id],$phases),$logger->records,'exact diagnostics including subsequent independent failure');
        assertSameValue('', $logger->bytes,'no diagnostic bytes'); assertSameValue([$id],$logger->bindings,'one exact binding');
        assertSameValue([], $repo->accepted,'no acceptance'); assertSameValue(1,count($repo->attempts),'one terminal audit');
        $audit=$repo->attempts[0]; assertSameValue([$id,18,'initial',4512,81,'rejected','invalid_pdf',false,'2026-09-02T09:15:30Z'],[$audit->requestId,$audit->actorUserId,$audit->mode->value,$audit->installationCaseId,$audit->assignmentOrderId,$audit->status->value,$audit->reason->value,$audit->retryable,$audit->attemptedAt],'exact audit');
        assertSameValue([], $delivery->results,'no delivery');
    });
}
foreach(['release_failed','release_throw'] as $fault)foreach([false,true] as $generic){
    isolationCase('accepted-'.$fault.($generic?'-generic':''),function()use($pdf,$fault,$generic){
        extract(isolationGraph($fault,false,generic:$generic)); $id='00000000-0000-4000-8000-000000000001';
        // Also exercise direct Service construction, which must have the same boundary.
        if($generic)$app=new O\AssignmentOrderOriginalService($dependencies);
        $result=$app->submitAssignmentOrderOriginal(isolationCommand($id,new S\OriginalLogIsolationStream($pdf,$trace,false)));
        $expected=isolationExpected($id,'accepted',null);
        assertSameValue($expected,isolationTuple($result),'full accepted result survives failed release diagnostic');
        assertSameValue(['fingerprint.read','lineage.read','stage.close','stream.close','accepted.commit','lease.release','log:committed','delivery'],$trace->calls,'acceptance order and exact-once cleanup');
        assertSameValue([['ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED',['phase'=>'committed'],$generic?'old-request':$id]],$logger->records,'one unchanged release diagnostic');
        assertSameValue($generic?[]:[$id],$logger->bindings,'generic gets no binding');
        assertSameValue('', $logger->bytes,'no log bytes'); assertSameValue(1,count($repo->accepted),'one accepted commit');
        assertSameValue([], $repo->attempts,'no extra attempt audit'); assertSameValue([$expected],$delivery->results,'delivery exact accepted result');
    });
}
isolationCase('cas-conflict',function()use($pdf){
    extract(isolationGraph('release_failed',false,true)); $id='00000000-0000-4000-8000-000000000001';
    $before=$repo->evidenceCanonicalJson(4512,81);
    $result=$app->submitAssignmentOrderOriginal(isolationCommand($id,new S\OriginalLogIsolationStream($pdf,$trace,false)));
    assertSameValue(isolationExpected($id,'conflict','initial_already_exists'),isolationTuple($result),'full CAS conflict');
    assertSameValue(['fingerprint.read','lineage.read','stage.close','stream.close','accepted.commit','fingerprint.read','lineage.read','lease.release','log:commit_conflict','attempt.commit'],$trace->calls,'rereads before release and required audit after diagnostic');
    assertSameValue([['ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED',['phase'=>'commit_conflict'],$id]],$logger->records,'one conflict diagnostic');
    assertSameValue('{"preExistingRoot":null,"newAccepted":[]}',$before,'rival is not committed before initial precheck');
    assertSameValue('{"preExistingRoot":["original-0099","revision-0099",1,"composition-81-v1"],"newAccepted":[]}',$repo->evidenceCanonicalJson(4512,81),'concurrent winner published exactly and current command has no acceptance');
    assertSameValue(1,count($repo->attempts),'required conflict audit'); assertSameValue('initial_already_exists',$repo->attempts[0]->reason->value,'conflict audit reason');
    assertSameValue([], $delivery->results,'no conflict delivery'); assertSameValue('', $logger->bytes,'no log bytes');
});
isolationCase('binding-failure-accepted',function()use($pdf){
    extract(isolationGraph('release_failed',false)); $logger->bindingFails=true; $id='00000000-0000-4000-8000-000000000001';
    $result=$app->submitAssignmentOrderOriginal(isolationCommand($id,new S\OriginalLogIsolationStream($pdf,$trace,false)));
    $expected=isolationExpected($id,'accepted',null);
    assertSameValue($expected,isolationTuple($result),'binding cannot escape or replace acceptance');
    assertSameValue(['fingerprint.read','lineage.read','stage.close','stream.close','accepted.commit','lease.release','delivery'],$trace->calls,'normal lifecycle despite binding failure');
    assertSameValue([$id],$logger->bindings,'binding attempted once'); assertSameValue([], $logger->records,'no stale log callback');
    assertSameValue('', $logger->bytes,'no stale bytes'); assertSameValue(1,count($repo->accepted),'one acceptance');
    assertSameValue([], $repo->attempts,'no attempts'); assertSameValue([$expected],$delivery->results,'delivery preserved');
});
isolationCase('binding-reset-same-application',function()use($pdf){
    extract(isolationGraph('abort_failed',true)); $logger->bindingFails=true;
    $ids=['00000000-0000-4000-8000-000000000301','00000000-0000-4000-8000-000000000302'];
    foreach($ids as $i=>$id){
        $result=$app->submitAssignmentOrderOriginal(isolationCommand($id,new S\OriginalLogIsolationStream($pdf,$trace,false)));
        assertSameValue(isolationExpected($id,'rejected','invalid_pdf'),isolationTuple($result),'rejection preserved for invocation '.$i);
        if($i===0){assertSameValue([],$logger->records,'failed binding suppresses needed diagnostic'); assertSameValue('old-request',$logger->context,'throw happened before old context update');}
        $logger->bindingFails=false;
    }
    assertSameValue($ids,$logger->bindings,'one binding per fresh invocation');
    assertSameValue([['ASSIGNMENT_ORDER_ORIGINAL_STAGE_ABORT_FAILED',['phase'=>'stage_abort'],$ids[1]]],$logger->records,'recovered next invocation uses new context exactly once');
    assertSameValue(['stage.abort','stage.close','stream.close','attempt.commit','stage.abort','log:stage_abort','stage.close','stream.close','attempt.commit'],$trace->calls,'no sticky suppression or repeated cleanup');
    assertSameValue(2,count($repo->attempts),'both required audits'); assertSameValue([], $repo->accepted,'no accidental accepted fact'); assertSameValue('', $logger->bytes,'throwing logger emits nothing');
});
isolationCase('real-audit-failure-remains',function()use($pdf){
    extract(isolationGraph('abort_failed',true,auditFails:true)); $id='00000000-0000-4000-8000-000000000301';
    $result=$app->submitAssignmentOrderOriginal(isolationCommand($id,new S\OriginalLogIsolationStream($pdf,$trace,false)));
    assertSameValue(isolationExpected($id,'failed','persistence_failure',true),isolationTuple($result),'real audit failure must not become selected rejection');
    assertSameValue(['stage.abort','log:stage_abort','stage.close','stream.close','attempt.commit','log:terminal'],$trace->calls,'audit attempted after cleanup logger failure and failed audit diagnostic attempted once');
    assertSameValue([], $repo->attempts,'failed audit not persisted');
});
if($failures!==[])throw new TestFailure('Diagnostic isolation failed '.count($failures).' cases: '.implode(',',$failures));
echo "PASS SAFE-LOG-ISOLATION-001 ($passed cases)\n";
