<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionReader;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionSnapshot;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDependencies;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReason;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialClock;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialCompositionReader;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialIds;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialObservers;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialProcessState;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialRepository;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialStorage;
use FMonitor2\Tests\Support\AssignmentOrderOriginalMatrixAuthorizer;
use FMonitor2\Tests\Support\AssignmentOrderOriginalMatrixStream;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, parity and exact authorization.
if (!class_exists(AssignmentOrderOriginalVerificationFactory::class)) {
    throw new TestFailure(
        'INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.',
    );
}

require dirname(__DIR__) . '/Support/AssignmentOrderOriginalInitialProcessState.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalInitialFixture.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalMatrixInputs.php';

$pdf = base64_decode(
    'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',
    true,
);
assertSameValue(true, is_string($pdf), 'Approved positive PDF decodes strictly.');

/** @return array{object,AssignmentOrderOriginalMatrixAuthorizer,AssignmentOrderOriginalMatrixStream} */
$build = static function (
    AssignmentOrderOriginalAuthorizationStatus $authorization,
    string $bytes,
): array {
    $state = new AssignmentOrderOriginalInitialProcessState();
    $authorizer = new AssignmentOrderOriginalMatrixAuthorizer($authorization);
    $stream = new AssignmentOrderOriginalMatrixStream($bytes);
    $repository = new AssignmentOrderOriginalInitialRepository($state);
    $storage = new AssignmentOrderOriginalInitialStorage();
    $observers = new AssignmentOrderOriginalInitialObservers();
    $application = AssignmentOrderOriginalVerificationFactory::create(
        new AssignmentOrderOriginalDependencies(
            $authorizer,
            new AssignmentOrderOriginalInitialCompositionReader($state),
            new AssignmentOrderOriginalInitialClock(),
            new AssignmentOrderOriginalInitialIds(),
            new AssignmentOrderOriginalInitialInspector(),
            $storage,
            $repository,
            $observers,
            $observers,
            $observers,
            $observers,
            $observers,
        ),
    );
    return [$application, $authorizer, $stream];
};

$resultVector = static fn ($result): array => [
    $result->status(), $result->reasonCode(), $result->retryable(),
    $result->rootOriginalId(), $result->currentRevisionId(), $result->revisionNumber(),
    $result->documentDate(), $result->sha256(), $result->byteSize(), $result->uploadedAt(),
];

[$direct, , $directStream] = $build(AssignmentOrderOriginalAuthorizationStatus::ALLOWED, $pdf);
$directResult = $direct->submitAssignmentOrderOriginal(new SubmitAssignmentOrderOriginalCommand(
    '00000000-0000-4000-8000-000000000011', AssignmentOrderOriginalMode::INITIAL,
    4512, 81, 18, '2026-09-01', true, null, null, null, null,
    new AssignmentOrderOriginalUpload($directStream, 'direct.pdf', 'application/pdf'),
));
[$postTemplate, , $postTemplateStream] = $build(AssignmentOrderOriginalAuthorizationStatus::ALLOWED, $pdf);
$postTemplateResult = $postTemplate->submitAssignmentOrderOriginal(new SubmitAssignmentOrderOriginalCommand(
    '00000000-0000-4000-8000-000000000012', AssignmentOrderOriginalMode::INITIAL,
    4512, 81, 18, '2026-09-01', true, null, null, null, null,
    new AssignmentOrderOriginalUpload($postTemplateStream, 'after-template.pdf', 'application/pdf'),
));
assertSameValue($resultVector($directResult), $resultVector($postTemplateResult), 'Direct and post-template inputs have identical command semantics.');
assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $postTemplateResult->status(), 'Post-template parity accepts the same original evidence.');

[$deniedInitial, $initialAuth, $deniedInitialStream] = $build(AssignmentOrderOriginalAuthorizationStatus::DENIED, $pdf);
$deniedInitialResult = $deniedInitial->submitAssignmentOrderOriginal(new SubmitAssignmentOrderOriginalCommand(
    '00000000-0000-4000-8000-000000000013', AssignmentOrderOriginalMode::INITIAL,
    4512, 81, 18, '2026-09-01', true, null, null, null, null,
    new AssignmentOrderOriginalUpload($deniedInitialStream, 'denied.pdf', 'application/pdf'),
));
assertSameValue([AssignmentOrderOriginalStatus::REJECTED, AssignmentOrderOriginalReason::AUTHORIZATION_DENIED, false], [$deniedInitialResult->status(), $deniedInitialResult->reasonCode(), $deniedInitialResult->retryable()], 'Denied initial upload has exact terminal result.');
assertSameValue([[18, 'assignment_order.original.upload']], $initialAuth->calls, 'Initial mode asks only for exact upload capability.');
assertSameValue(0, $deniedInitialStream->readCalls, 'Unauthorized initial upload is denied before stream read.');

[$deniedCorrection, $correctionAuth, $deniedCorrectionStream] = $build(AssignmentOrderOriginalAuthorizationStatus::DENIED, $pdf);
$deniedCorrectionResult = $deniedCorrection->submitAssignmentOrderOriginal(new SubmitAssignmentOrderOriginalCommand(
    '00000000-0000-4000-8000-000000000014', AssignmentOrderOriginalMode::CORRECTION,
    4512, 81, 18, '2026-09-01', true, 'original-0001', 'revision-0001', 'revision-0001', 'Исправлен файл',
    new AssignmentOrderOriginalUpload($deniedCorrectionStream, 'correction.pdf', 'application/pdf'),
));
assertSameValue([AssignmentOrderOriginalStatus::REJECTED, AssignmentOrderOriginalReason::AUTHORIZATION_DENIED, false], [$deniedCorrectionResult->status(), $deniedCorrectionResult->reasonCode(), $deniedCorrectionResult->retryable()], 'Denied correction has exact terminal result.');
assertSameValue([[18, 'assignment_order.original.correct']], $correctionAuth->calls, 'Correction mode asks only for exact correction capability.');
assertSameValue(0, $deniedCorrectionStream->readCalls, 'Unauthorized correction is denied before stream read.');

$submitInitial = static function (object $application, AssignmentOrderOriginalMatrixStream $stream, string $requestId, string $date, bool $confirmed, string $filename, string $mediaType) {
    return $application->submitAssignmentOrderOriginal(new SubmitAssignmentOrderOriginalCommand(
        $requestId, AssignmentOrderOriginalMode::INITIAL, 4512, 81, 18, $date, $confirmed,
        null, null, null, null, new AssignmentOrderOriginalUpload($stream, $filename, $mediaType),
    ));
};
$nullEvidence = static fn ($result): array => [
    $result->rootOriginalId(), $result->currentRevisionId(), $result->revisionNumber(),
    $result->documentDate(), $result->sha256(), $result->byteSize(), $result->uploadedAt(),
];
$matrix = [
    ['00000000-0000-4000-8000-000000000020','2026-09-01',false,'signed.pdf','application/pdf',$pdf,AssignmentOrderOriginalReason::COMPOSITION_NOT_CONFIRMED,0],
    ['00000000-0000-4000-8000-000000000021','2026-09-03',true,'signed.pdf','application/pdf',$pdf,AssignmentOrderOriginalReason::FUTURE_DOCUMENT_DATE,0],
    ['00000000-0000-4000-8000-000000000022','2026-09-01',true,'signed.pdf','text/plain',$pdf,AssignmentOrderOriginalReason::NOT_PDF,2],
    ['00000000-0000-4000-8000-000000000023','2026-09-01',true,'signed.pdf','application/pdf','',AssignmentOrderOriginalReason::NOT_PDF,1],
    ['00000000-0000-4000-8000-000000000024','2026-09-01',true,'signed.pdf','application/pdf','plain text',AssignmentOrderOriginalReason::NOT_PDF,2],
    ['00000000-0000-4000-8000-000000000025','2026-09-01',true,'signed.pdf','application/pdf',str_repeat('x',20_971_521),AssignmentOrderOriginalReason::FILE_TOO_LARGE,321],
];
foreach($matrix as[$requestId,$date,$confirmed,$filename,$media,$bytes,$reason,$maximumReads]){[$application,$authorizer,$stream]=$build(AssignmentOrderOriginalAuthorizationStatus::ALLOWED,$bytes);$result=$submitInitial($application,$stream,$requestId,$date,$confirmed,$filename,$media);assertSameValue([AssignmentOrderOriginalStatus::REJECTED,$reason,false,[$requestId],[null,null,null,null,null,null,null]],[$result->status(),$result->reasonCode(),$result->retryable(),[$result->requestId()],$nullEvidence($result)],"{$reason->value} exact public result and null evidence.");assertSameValue(true,$stream->readCalls<=$maximumReads,"{$reason->value} stops at its bounded precedence point.");assertSameValue(1,$stream->closeCalls,"{$reason->value} closes the supplied stream exactly once.");assertSameValue([[18,'assignment_order.original.upload']],$authorizer->calls,"{$reason->value} uses only exact upload authorization.");}

$compositionCases=[
    [AssignmentOrderCompositionLookupStatus::NOT_FOUND,AssignmentOrderOriginalStatus::REJECTED,AssignmentOrderOriginalReason::ORDER_NOT_FOUND,false],
    [AssignmentOrderCompositionLookupStatus::FOUND,AssignmentOrderOriginalStatus::REJECTED,AssignmentOrderOriginalReason::INVALID_COMPOSITION,false],
    [AssignmentOrderCompositionLookupStatus::UNAVAILABLE,AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,true],
];
foreach($compositionCases as$index=>[$lookup,$status,$reason,$retryable]){$state=new AssignmentOrderOriginalInitialProcessState();$authorizer=new AssignmentOrderOriginalMatrixAuthorizer(AssignmentOrderOriginalAuthorizationStatus::ALLOWED);$stream=new AssignmentOrderOriginalMatrixStream($pdf);$composition=new class($lookup)implements AssignmentOrderCompositionReader{public function __construct(private AssignmentOrderCompositionLookupStatus$status){}public function find(int$caseId,int$orderId):AssignmentOrderCompositionSnapshot{return new AssignmentOrderCompositionSnapshot($this->status,$caseId,$orderId,$this->status===AssignmentOrderCompositionLookupStatus::FOUND?'composition-81-v1':null,$this->status===AssignmentOrderCompositionLookupStatus::FOUND?str_repeat('0',64):null,[],null);}};$observers=new AssignmentOrderOriginalInitialObservers();$application=AssignmentOrderOriginalVerificationFactory::create(new AssignmentOrderOriginalDependencies($authorizer,$composition,new AssignmentOrderOriginalInitialClock(),new AssignmentOrderOriginalInitialIds(),new AssignmentOrderOriginalInitialInspector(),new AssignmentOrderOriginalInitialStorage(),new AssignmentOrderOriginalInitialRepository($state),$observers,$observers,$observers,$observers,$observers));$request=sprintf('00000000-0000-4000-8000-%012d',30+$index);$result=$submitInitial($application,$stream,$request,'2026-09-01',true,'signed.pdf','application/pdf');assertSameValue([$status,$reason,$retryable,[null,null,null,null,null,null,null],0],[$result->status(),$result->reasonCode(),$result->retryable(),$nullEvidence($result),$stream->readCalls],"{$lookup->value} composition/order lookup maps exactly before stream.");}

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK\n");
