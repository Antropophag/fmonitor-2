<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
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

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK\n");
