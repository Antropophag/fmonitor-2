<?php

declare(strict_types=1);
// FMONITOR_TEST_DB: WORKER-BOUNDARY-001 native IPC and pure result closure.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityWorker.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;
use FMonitor2\Tests\Support\OriginalAttemptAuditDatabase as A;

function workerEncodingResult(string $request):O\AssignmentOrderOriginalResult{return new O\AssignmentOrderOriginalResultValue(O\AssignmentOrderOriginalStatus::FAILED,O\AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,true,$request);}
function workerEncodingFailure(callable $operation):void{$caught=null;try{$operation();}catch(Throwable $e){$caught=$e;}assertSameValue(true,$caught instanceof O\AssignmentOrderOriginalWorkerEncodingUnavailable,'fixed encoder class');assertSameValue(['Assignment-order original worker encoding unavailable.',0,null],[$caught->getMessage(),$caught->getCode(),$caught->getPrevious()],'fixed encoder error');}
$prefix='{"status":"failed","reasonCode":"persistence_failure","retryable":true,"requestId":"';
$suffix='","rootOriginalId":null,"currentRevisionId":null,"revisionNumber":null,"documentDate":null,"sha256":null,"byteSize":null,"uploadedAt":null}'."\n";
integrityCase('worker-encoder-literal',function()use($prefix,$suffix){$id='00000000-0000-4000-8000-000000000001';assertSameValue($prefix.$id.$suffix,O\AssignmentOrderOriginalWorkerResultEncoder::encode(result:workerEncodingResult($id)),'independent exact line and named port');});
integrityCase('worker-encoder-16384',function()use($prefix,$suffix){$id=str_repeat('a',16384-strlen($prefix)-strlen($suffix));$line=O\AssignmentOrderOriginalWorkerResultEncoder::encode(workerEncodingResult($id));assertSameValue($prefix.$id.$suffix,$line,'exact inclusive native line');assertSameValue(16384,strlen($line),'inclusive encoded byte limit');});
integrityCase('worker-encoder-16385',function()use($prefix,$suffix){$id=str_repeat('a',16385-strlen($prefix)-strlen($suffix));workerEncodingFailure(fn()=>O\AssignmentOrderOriginalWorkerResultEncoder::encode(workerEncodingResult($id)));});
integrityCase('worker-encoder-invalid-utf8',fn()=>workerEncodingFailure(fn()=>O\AssignmentOrderOriginalWorkerResultEncoder::encode(workerEncodingResult("\xff"))));
function workerControlled(array $out,string $ready=''):void{assertSameValue([70,'',"ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n",$ready,''],[$out['exit'],$out['stdout'],$out['stderr'],$out['ready'],$out['result']],'exact controlled worker channels');}
function workerNoFacts($f):void{$r=O\AssignmentOrderOriginalEvidenceReaderFactory::create(new O\AssignmentOrderOriginalEvidenceReaderConfig($f->host,$f->port,$f->database,$f->user,$f->passwordFile,$f->prefix,$f->privateRoot,$f->safeLog));try{assertSameValue('{"schema":"aoou-evidence-v1","caseId":4512,"orderId":81,"roots":[]}',$r->domainCanonicalJson(4512,81),'worker failure commits no original');}finally{$r->close();}}
A::case('worker-boundary-native-control',function($f){$w=new S\OriginalIntegrityWorker($f,S\OriginalIntegrityWorker::command());$out=$w->finish();assertSameValue([0,'',''],[$out['exit'],$out['stdout'],$out['stderr']],'normal native worker');assertSameValue('accepted',json_decode($out['result'],true,512,JSON_THROW_ON_ERROR)['status'],'ordinary result preserved');});
A::case('worker-command-eof-deadline',function($f){$start=hrtime(true);$w=new S\OriginalIntegrityWorker($f,S\OriginalIntegrityWorker::command(),[],true,true);$out=$w->finish(false);$elapsed=(hrtime(true)-$start)/1e9;workerControlled($out);assertSameValue(true,$elapsed>=4.5&&$elapsed<12,'child enforces five-second EOF wait; parent watchdog is not success');workerNoFacts($f);});
A::case('worker-barrier-deadline',function($f){$w=new S\OriginalIntegrityWorker($f,S\OriginalIntegrityWorker::command());assertSameValue("READY 00000000-0000-4000-8000-000000000001\n",$w->awaitReady(),'actual barrier reached');$start=hrtime(true);$out=$w->finish(false);$elapsed=(hrtime(true)-$start)/1e9;workerControlled($out,"READY 00000000-0000-4000-8000-000000000001\n");assertSameValue(true,$elapsed>=4.5&&$elapsed<12,'child barrier deadline independent of parent reap');workerNoFacts($f);});
foreach(['missing-log','log-mode','password-mode','root-alias','prefix-bound','clock'] as $bad)A::case('worker-pre-command-'.$bad,function($f)use($bad){
 $owned=null;$changes=[];
 switch($bad){case 'missing-log':$changes['safeLogFile']=$f->control.'/missing-log';break;case 'root-alias':$changes['privateStorageRoot']=$f->privateRoot.'/.';break;case 'prefix-bound':$changes['tablePrefix']=str_repeat('a',26);break;case 'clock':$changes['clockUtc']='not-a-clock';break;default:$owned=$f->control.'/wrong-mode';file_put_contents($owned,$bad==='password-mode'?'synthetic':'');chmod($owned,0640);$changes[$bad==='password-mode'?'databasePasswordFile':'safeLogFile']=$owned;}
 try{$w=new S\OriginalIntegrityWorker($f,S\OriginalIntegrityWorker::command(),$changes,true,false);workerControlled($w->finish(false));workerNoFacts($f);}finally{if($owned!==null)unlink($owned);}
});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_WORKER_BOUNDARY_OK');
