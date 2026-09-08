<?php

declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once __DIR__.'/AssignmentOrderOriginalIntegrityValues.php';
require_once __DIR__.'/AssignmentOrderOriginalIntegrityFixture.php';
require_once __DIR__.'/AssignmentOrderOriginalIntegrityResources.php';

use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support as S;

$integrityFailed=[];$integrityPassed=0;
function integrityCase(string $name,callable $case):void
{global $integrityFailed,$integrityPassed;try{$case();++$integrityPassed;fwrite(STDOUT,"PASS {$name}\n");}catch(Throwable $e){$integrityFailed[]=$name;fwrite(STDOUT,"FAIL {$name}: ".$e->getMessage()."\n");}}
function integrityTuple(O\AssignmentOrderOriginalResult $r):array
{return [$r->status()->value,$r->reasonCode()?->value,$r->retryable(),$r->requestId(),$r->rootOriginalId(),$r->currentRevisionId(),$r->revisionNumber(),$r->documentDate(),$r->sha256(),$r->byteSize(),$r->uploadedAt()];}
function integrityFailure(S\OriginalIntegrityFixture $f,O\AssignmentOrderOriginalResult $r,string $reason='persistence_failure',bool $streamed=false):void
{
    assertSameValue(['failed',$reason,true,$f->command->requestId,null,null,null,null,null,null,null],integrityTuple($r),'exact retryable failure');
    assertSameValue(1,$f->stream->closeCalls,'one stream close');assertSameValue($streamed?2:0,$f->stream->readCalls,'exact stream reads');
    assertSameValue([], $f->repository->acceptedCalls,'no accepted write call');assertSameValue([], $f->repository->attemptCalls,'no terminal attempt call');
    assertSameValue(0,$f->observers->deliveryCalls,'no delivery');assertSameValue(0,$f->ids->rootCalls+$f->ids->revisionCalls,'no allocation');
    if($streamed){assertSameValue(1,$f->storage->stage->abortCalls,'candidate aborted once');assertSameValue(1,$f->storage->stage->closeCalls,'candidate closed once');assertSameValue(0,$f->storage->stage->finalizeCalls,'no finalize');}
    else assertSameValue(0,$f->storage->beginCalls,'no stage');
}
function integrityDone(string $marker):void
{global $integrityFailed,$integrityPassed;fwrite(STDOUT,"RESULT passed={$integrityPassed} failed=".count($integrityFailed)."\n");assertSameValue([],$integrityFailed,'Cumulative integrity exact cases');fwrite(STDOUT,$marker."\n");}
