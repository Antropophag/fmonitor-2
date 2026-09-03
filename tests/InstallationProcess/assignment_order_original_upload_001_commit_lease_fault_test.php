<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalByteStream;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalCommitStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReason;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamRead;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamReadStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand;
use FMonitor2\Tests\Support\InMemoryAssignmentOrderOriginalInitialEnvironment;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, sections 10, 11 and 15.
foreach ([AssignmentOrderOriginalApplication::class, AssignmentOrderOriginalVerificationFactory::class] as $type) {
    if (!class_exists($type) && !interface_exists($type)) throw new TestFailure('INTENDED_RED: canonical production application seam is missing: ' . $type);
}
require dirname(__DIR__) . '/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';

$pdf=base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);
$stream=static function(int &$reads,string $bytes='')use($pdf):AssignmentOrderOriginalByteStream{return new class($bytes===''?$pdf:$bytes,$reads)implements AssignmentOrderOriginalByteStream{private int$o=0;public function __construct(private string$b,private int&$r){}public function read(int$m):AssignmentOrderOriginalStreamRead{$this->r++;if($this->o===strlen($this->b))return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF,'');$c=substr($this->b,$this->o,$m);$this->o+=strlen($c);return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES,$c);}public function close():void{}};};
$command=static fn(string$id,AssignmentOrderOriginalByteStream$bytes):SubmitAssignmentOrderOriginalCommand=>new SubmitAssignmentOrderOriginalCommand($id,AssignmentOrderOriginalMode::INITIAL,4512,81,18,'2026-09-01',true,null,null,null,null,new AssignmentOrderOriginalUpload($bytes,'signed.pdf','application/pdf'));
$resultTuple=static fn($r):array=>[$r->status(),$r->reasonCode(),$r->retryable(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize(),$r->uploadedAt()];
$failedPersistence=[AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,true,null,null,null,null,null,null,null];

$rollback=new InMemoryAssignmentOrderOriginalInitialEnvironment();$rollback->commitOutcome=AssignmentOrderOriginalCommitStatus::ROLLED_BACK;$before=$rollback->processCanonicalJson();$reads=0;
$rolled=AssignmentOrderOriginalVerificationFactory::create($rollback->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000121',$stream($reads)));
assertSameValue($failedPersistence,$resultTuple($rolled),'Definite rollback is retryable PERSISTENCE_FAILURE with zero evidence fields.');
assertSameValue(['commit:held','release_attempt:held'],array_values(array_filter($rollback->repositoryTrace,static fn(string$x):bool=>str_starts_with($x,'commit:')||str_starts_with($x,'release_attempt:'))),'Lease is held through rollback and released exactly afterwards.');
assertSameValue([1,false,false,null,$before],[ $rollback->leaseReleaseCalls,$rollback->leaseHeld,$rollback->storageRecoveryOwnsLease,$rollback->acceptedCommit,$rollback->processCanonicalJson() ],'Rollback releases once, persists no public/domain fact and leaves process unchanged.');
assertSameValue([],array_keys($rollback->terminalResults),'Retryable persistence failure is not a terminal request hit.');

foreach ([
    'found'=>[AssignmentOrderOriginalStatus::ACCEPTED,null,false],
    'not_found'=>[AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,true],
    'unavailable'=>[AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_OUTCOME_UNKNOWN,true],
] as $resolution=>$expected) {
    $environment=new InMemoryAssignmentOrderOriginalInitialEnvironment();$environment->commitOutcome=AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;$environment->unknownResolution=$resolution;$reads=0;
    $result=AssignmentOrderOriginalVerificationFactory::create($environment->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-00000000012'.(['found'=>2,'not_found'=>3,'unavailable'=>4][$resolution]),$stream($reads)));
    assertSameValue($expected,[$result->status(),$result->reasonCode(),$result->retryable()],'Unknown commit has exact fresh-lookup resolution: '.$resolution);
    $trace=array_values(array_filter($environment->repositoryTrace,static fn(string$x):bool=>str_starts_with($x,'commit:')||str_starts_with($x,'request_lookup:')||str_starts_with($x,'release_attempt:')));
    assertSameValue(['request_lookup:released','commit:held','request_lookup:held','release_attempt:held'],$trace,'Fresh terminal lookup occurs with lease held, then one release: '.$resolution);
    assertSameValue(1,$environment->leaseReleaseCalls,'Unknown resolution releases exactly once: '.$resolution);
}

$absent=new InMemoryAssignmentOrderOriginalInitialEnvironment();$absent->commitOutcome=AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;$absent->unknownResolution='not_found';$app=AssignmentOrderOriginalVerificationFactory::create($absent->dependencies());$reads=0;
$first=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000125',$stream($reads)));
$absent->commitOutcome=AssignmentOrderOriginalCommitStatus::COMMITTED;$absent->unknownResolution=null;$retryReads=0;$retry=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000125',$stream($retryReads)));
assertSameValue([[AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_FAILURE],[AssignmentOrderOriginalStatus::ACCEPTED,null]],[[$first->status(),$first->reasonCode()],[$retry->status(),$retry->reasonCode()]],'Proven-absent same request may retry verified private content and accept.');
assertSameValue([2,1,1],[$absent->acceptedCommitCalls,count($absent->acceptedCommits),count($absent->terminalResults)],'Retry makes one new commit attempt and at most one accepted fact/terminal result.');

$lost=new InMemoryAssignmentOrderOriginalInitialEnvironment();$lost->deliveryThrows=true;$app=AssignmentOrderOriginalVerificationFactory::create($lost->dependencies());$reads=0;$thrown=false;
try{$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000126',$stream($reads)));}catch(Throwable){$thrown=true;}
assertSameValue(true,$thrown,'Verifier delivery fault simulates response loss after durable commit.');
assertSameValue(['commit:held','release_attempt:held','delivery:released'],array_values(array_filter($lost->repositoryTrace,static fn(string$x):bool=>str_starts_with($x,'commit:')||str_starts_with($x,'release_attempt:')||str_starts_with($x,'delivery:'))),'Delivery runs only after commit and release attempt.');
$lost->deliveryThrows=false;$replacementReads=0;$replayed=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000126',$stream($replacementReads,'NOT PDF')));
assertSameValue([AssignmentOrderOriginalStatus::REPLAYED,0,1,1],[$replayed->status(),$replacementReads,$lost->acceptedCommitCalls,count($lost->acceptedCommits)],'Lost response retry replays before replacement stream/storage/commit.');

foreach (['failed','throw'] as $releaseFault) {
    $committed=new InMemoryAssignmentOrderOriginalInitialEnvironment();$committed->leaseReleaseFault=$releaseFault;$reads=0;
    $accepted=AssignmentOrderOriginalVerificationFactory::create($committed->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-00000000012'.($releaseFault==='failed'?7:8),$stream($reads)));
    assertSameValue([AssignmentOrderOriginalStatus::ACCEPTED,null,false,1,true,true],[$accepted->status(),$accepted->reasonCode(),$accepted->retryable(),$committed->leaseReleaseCalls,$committed->leaseHeld,$committed->storageRecoveryOwnsLease],'Release '.$releaseFault.' preserves committed accepted result and storage recovery ownership.');
    assertSameValue(1,count($committed->safeLogs),'Release '.$releaseFault.' emits exactly one safe log.');
    $log=$committed->safeLogs[0];
    assertSameValue('ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED',$log['event'],'Release failure uses stable safe event.');
    assertSameValue(['correlation_id','phase'],array_keys($log['safeFields']),'Release failure log has exact redacted allowlist.');
    assertSameValue('committed',$log['safeFields']['phase'],'Committed release phase is explicit.');
    assertSameValue(1,preg_match('/^[0-9a-f]{12}$/',(string)$log['safeFields']['correlation_id']),'Correlation is an opaque twelve-hex token.');
    assertSameValue(false,str_contains(json_encode($log,JSON_THROW_ON_ERROR),'private-content')||str_contains(json_encode($log,JSON_THROW_ON_ERROR),'signed.pdf')||str_contains(json_encode($log,JSON_THROW_ON_ERROR),'secret'),'Safe log contains no identity, filename, path or exception detail.');
}

$rollbackRelease=new InMemoryAssignmentOrderOriginalInitialEnvironment();$rollbackRelease->commitOutcome=AssignmentOrderOriginalCommitStatus::ROLLED_BACK;$rollbackRelease->leaseReleaseFault='throw';$reads=0;
$rollbackResult=AssignmentOrderOriginalVerificationFactory::create($rollbackRelease->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000129',$stream($reads)));
assertSameValue($failedPersistence,$resultTuple($rollbackResult),'Release throw cannot replace rollback PERSISTENCE_FAILURE.');
assertSameValue('rolled_back',$rollbackRelease->safeLogs[0]['safeFields']['phase']??null,'Rollback release failure logs exact phase.');

foreach ([
    'found'=>[AssignmentOrderOriginalStatus::ACCEPTED,null,'unknown_found','failed'],
    'not_found'=>[AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,'unknown_not_found','throw'],
    'unavailable'=>[AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_OUTCOME_UNKNOWN,'unknown_unavailable','failed'],
] as $resolution=>[$status,$reason,$phase,$fault]) {
    $environment=new InMemoryAssignmentOrderOriginalInitialEnvironment();$environment->commitOutcome=AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;$environment->unknownResolution=$resolution;$environment->leaseReleaseFault=$fault;$reads=0;
    $selected=AssignmentOrderOriginalVerificationFactory::create($environment->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-00000000014'.(['found'=>0,'not_found'=>1,'unavailable'=>2][$resolution]),$stream($reads)));
    assertSameValue([$status,$reason,true,true],[$selected->status(),$selected->reasonCode(),$environment->leaseHeld,$environment->storageRecoveryOwnsLease],'Unknown '.$resolution.' release '.$fault.' preserves selected outcome and recovery token.');
    assertSameValue([1,$phase],[count($environment->safeLogs),$environment->safeLogs[0]['safeFields']['phase']??null],'Unknown '.$resolution.' release failure logs exact once and exact phase.');
}

foreach (['identical'=>[AssignmentOrderOriginalStatus::REPLAYED,null],'different'=>[AssignmentOrderOriginalStatus::CONFLICT,AssignmentOrderOriginalReason::STALE_REVISION]] as $race=>$expected) {
    $environment=new InMemoryAssignmentOrderOriginalInitialEnvironment();$app=AssignmentOrderOriginalVerificationFactory::create($environment->dependencies());$reads=0;$initial=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-00000000013'.($race==='identical'?0:2),$stream($reads)));
    $environment->allowedCapability='assignment_order.original.correct';$environment->commitRace=$race;$environment->leaseReleaseFault=$race==='identical'?'throw':'failed';$reads=0;
    $correction=new SubmitAssignmentOrderOriginalCommand('00000000-0000-4000-8000-00000000013'.($race==='identical'?1:3),AssignmentOrderOriginalMode::CORRECTION,4512,81,18,'2026-09-02',true,$initial->rootOriginalId(),$initial->currentRevisionId(),$initial->currentRevisionId(),'Исправлена дата',new AssignmentOrderOriginalUpload($stream($reads),'corrected.pdf','application/pdf'));
    $selected=$app->submitAssignmentOrderOriginal($correction);
    assertSameValue($expected,[$selected->status(),$selected->reasonCode()],'CAS '.$race.' release failure preserves selected result.');
    $commitPositions=array_keys($environment->repositoryTrace,'commit:held',true);$tail=array_slice($environment->repositoryTrace,$commitPositions[array_key_last($commitPositions)]);
    assertSameValue(true,in_array('fingerprint_lookup:held',$tail,true)&&in_array('lineage_lookup:held',$tail,true),'CAS '.$race.' performs both mandatory rereads while lease is held.');
    assertSameValue(true,array_search('release_attempt:held',$tail,true)<($race==='different'?array_search('attempt_commit:held',$tail,true):count($tail)),'CAS '.$race.' release is attempted before any required conflict audit.');
    assertSameValue(1,count($environment->safeLogs),'CAS '.$race.' release failure safe-logs exactly once.');
    assertSameValue('commit_conflict',$environment->safeLogs[0]['safeFields']['phase']??null,'CAS release log has exact conflict phase.');
}

fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_FAILURE_MATRIX_OK\n");
