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

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, section 11.
foreach ([AssignmentOrderOriginalApplication::class, AssignmentOrderOriginalVerificationFactory::class] as $type) {
    if (!class_exists($type) && !interface_exists($type)) throw new TestFailure('INTENDED_RED: canonical production application seam is missing: ' . $type);
}
require dirname(__DIR__) . '/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';

$pdf=base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);
$stream=static function(int &$reads)use($pdf):AssignmentOrderOriginalByteStream{return new class($pdf,$reads)implements AssignmentOrderOriginalByteStream{private int$o=0;public function __construct(private string$b,private int&$r){}public function read(int$m):AssignmentOrderOriginalStreamRead{$this->r++;if($this->o===strlen($this->b))return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF,'');$c=substr($this->b,$this->o,$m);$this->o+=strlen($c);return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES,$c);}public function close():void{}};};
$command=static function(string$id,AssignmentOrderOriginalByteStream$bytes,AssignmentOrderOriginalMode$mode=AssignmentOrderOriginalMode::INITIAL,?string$root=null,?string$revision=null):SubmitAssignmentOrderOriginalCommand{return new SubmitAssignmentOrderOriginalCommand($id,$mode,4512,81,18,$mode===AssignmentOrderOriginalMode::INITIAL?'2026-09-01':'2026-09-02',true,$root,$revision,$revision,$mode===AssignmentOrderOriginalMode::INITIAL?null:'Исправлена дата',new AssignmentOrderOriginalUpload($bytes,'signed.pdf','application/pdf'));};
$tuple=static fn($r):array=>[$r->status(),$r->reasonCode(),$r->retryable(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize(),$r->uploadedAt()];
$persistenceFailure=[AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,true,null,null,null,null,null,null,null];

$denied=new InMemoryAssignmentOrderOriginalInitialEnvironment();$denied->allowedCapability='assignment_order.prepare';$denied->attemptCommitStatus=AssignmentOrderOriginalCommitStatus::ROLLED_BACK;$reads=0;
$denial=AssignmentOrderOriginalVerificationFactory::create($denied->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000151',$stream($reads)));
assertSameValue($persistenceFailure,$tuple($denial),'Valid-shape authorization denial whose atomic terminal/audit commit fails becomes PERSISTENCE_FAILURE.');
assertSameValue([0,1,0],[$reads,count($denied->attemptCommits),count($denied->terminalResults)],'Denial audit is attempted once before stream and leaves no terminal result.');

$storage=new InMemoryAssignmentOrderOriginalInitialEnvironment();$storage->storageFault='write';$storage->attemptCommitStatus=AssignmentOrderOriginalCommitStatus::ROLLED_BACK;$reads=0;
$storageFailure=AssignmentOrderOriginalVerificationFactory::create($storage->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000152',$stream($reads)));
assertSameValue([AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::STORAGE_FAILURE,true],[$storageFailure->status(),$storageFailure->reasonCode(),$storageFailure->retryable()],'Best-effort audit failure cannot replace the original retryable storage failure.');
assertSameValue([1,0], [count($storage->attemptCommits),count($storage->terminalResults)], 'Retryable storage failure attempts safe audit once but never becomes terminal.');

$conflict=new InMemoryAssignmentOrderOriginalInitialEnvironment();$app=AssignmentOrderOriginalVerificationFactory::create($conflict->dependencies());$reads=0;$initial=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000153',$stream($reads)));
$conflict->allowedCapability='assignment_order.original.correct';$conflict->commitRace='different';$conflict->attemptCommitStatus=AssignmentOrderOriginalCommitStatus::ROLLED_BACK;$conflict->leaseReleaseFault='throw';$reads=0;
$collision=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000154',$stream($reads),AssignmentOrderOriginalMode::CORRECTION,$initial->rootOriginalId(),$initial->currentRevisionId()));
assertSameValue($persistenceFailure,$tuple($collision),'CAS-selected conflict whose required terminal/audit commit fails becomes PERSISTENCE_FAILURE even after release throw.');
$release=array_search('release_attempt:held',$conflict->repositoryTrace,true);$audit=array_search('attempt_commit:held',$conflict->repositoryTrace,true);
assertSameValue(true,is_int($release)&&is_int($audit)&&$release<$audit,'CAS conflict attempts release before its one atomic terminal/audit commit.');
assertSameValue([2,1],[$conflict->leaseReleaseCalls,count($conflict->safeLogs)],'Initial acceptance and conflict each release exactly once; conflict release throw remains safe-logged despite audit failure.');

fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUDIT_PRECEDENCE_OK\n");
