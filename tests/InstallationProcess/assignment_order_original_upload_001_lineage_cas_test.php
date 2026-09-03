<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalByteStream;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReason;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamRead;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamReadStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand;
use FMonitor2\Tests\Support\InMemoryAssignmentOrderOriginalInitialEnvironment;

foreach ([AssignmentOrderOriginalApplication::class, AssignmentOrderOriginalVerificationFactory::class] as $type) if (!class_exists($type) && !interface_exists($type)) throw new TestFailure('INTENDED_RED: canonical production application seam is missing: '.$type);
require dirname(__DIR__).'/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';
$pdf=base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);
$stream=static function(int &$reads)use($pdf):AssignmentOrderOriginalByteStream{return new class($pdf,$reads)implements AssignmentOrderOriginalByteStream{private int$o=0;public function __construct(private string$b,private int&$r){}public function read(int$m):AssignmentOrderOriginalStreamRead{$this->r++;if($this->o===strlen($this->b))return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF,'');$c=substr($this->b,$this->o,$m);$this->o+=strlen($c);return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES,$c);}public function close():void{}};};
$cmd=static function(string$request,AssignmentOrderOriginalByteStream$stream,?string$root=null,?string$target=null,?string$expected=null,string$date='2026-09-01'):SubmitAssignmentOrderOriginalCommand{return new SubmitAssignmentOrderOriginalCommand($request,$root===null?AssignmentOrderOriginalMode::INITIAL:AssignmentOrderOriginalMode::CORRECTION,4512,81,18,$date,true,$root,$target,$expected,$root===null?null:'Исправление',new AssignmentOrderOriginalUpload($stream,'signed.pdf','application/pdf'));};
$snapshot=static fn(InMemoryAssignmentOrderOriginalInitialEnvironment$e):array=>array_map(static fn($c):array=>[$c->rootOriginalId,$c->newRevisionId,$c->newRevisionNumber,$c->previousRevisionId,$c->documentDate,$c->pdfSha256],$e->acceptedCommits);

$env=new InMemoryAssignmentOrderOriginalInitialEnvironment();$app=AssignmentOrderOriginalVerificationFactory::create($env->dependencies());$r=0;$app->submitAssignmentOrderOriginal($cmd('00000000-0000-4000-8000-000000000061',$stream($r)));$env->allowedCapability='assignment_order.original.correct';$r=0;$app->submitAssignmentOrderOriginal($cmd('00000000-0000-4000-8000-000000000062',$stream($r),'original-0001','revision-0001','revision-0001','2026-09-02'));$history=$snapshot($env);
$cases=[
 ['00000000-0000-4000-8000-000000000063','revision-0001','revision-0001',AssignmentOrderOriginalReason::STALE_REVISION],
 ['00000000-0000-4000-8000-000000000064','missing-revision','revision-0002',AssignmentOrderOriginalReason::TARGET_NOT_FOUND],
 ['00000000-0000-4000-8000-000000000065','revision-0001','revision-0002',AssignmentOrderOriginalReason::TARGET_NOT_CURRENT],
];
foreach($cases as[$request,$target,$expected,$reason]){$reads=0;$result=$app->submitAssignmentOrderOriginal($cmd($request,$stream($reads),'original-0001',$target,$expected,'2026-09-01'));assertSameValue([AssignmentOrderOriginalStatus::CONFLICT,$reason,false,0],[$result->status(),$result->reasonCode(),$result->retryable(),$reads],'Exact lineage conflict precedes stream: '.$reason->value);assertSameValue($history,$snapshot($env),'Lineage conflict preserves every prior revision.');}

$identical=new InMemoryAssignmentOrderOriginalInitialEnvironment();$identicalApp=AssignmentOrderOriginalVerificationFactory::create($identical->dependencies());$r=0;$identicalApp->submitAssignmentOrderOriginal($cmd('00000000-0000-4000-8000-000000000066',$stream($r)));$initial=$snapshot($identical);$identical->allowedCapability='assignment_order.original.correct';$identical->commitRace='identical';$r=0;$loser=$identicalApp->submitAssignmentOrderOriginal($cmd('00000000-0000-4000-8000-000000000067',$stream($r),'original-0001','revision-0001','revision-0001','2026-09-02'));assertSameValue(AssignmentOrderOriginalStatus::REPLAYED,$loser->status(),'Identical CAS loser replays winner after fingerprint reread.');assertSameValue($initial[0],$snapshot($identical)[0],'Identical race never rewrites prior revision.');

$different=new InMemoryAssignmentOrderOriginalInitialEnvironment();$differentApp=AssignmentOrderOriginalVerificationFactory::create($different->dependencies());$r=0;$differentApp->submitAssignmentOrderOriginal($cmd('00000000-0000-4000-8000-000000000068',$stream($r)));$initial=$snapshot($different);$different->allowedCapability='assignment_order.original.correct';$different->commitRace='different';$r=0;$loser=$differentApp->submitAssignmentOrderOriginal($cmd('00000000-0000-4000-8000-000000000069',$stream($r),'original-0001','revision-0001','revision-0001','2026-09-02'));assertSameValue([AssignmentOrderOriginalStatus::CONFLICT,AssignmentOrderOriginalReason::STALE_REVISION,false],[$loser->status(),$loser->reasonCode(),$loser->retryable()],'Different CAS loser observes winner current lineage and returns stale.');assertSameValue($initial[0],$snapshot($different)[0],'Different race never rewrites prior revision.');
fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_CONCURRENCY_OK\n");
