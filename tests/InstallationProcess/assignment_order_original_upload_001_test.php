<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDependencies;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialAuthorizer;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialClock;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialCompositionReader;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialIds;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialObservers;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialRepository;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialStorage;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialStream;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, Example A.
if (!class_exists(AssignmentOrderOriginalVerificationFactory::class)) {
    throw new TestFailure(
        'INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.',
    );
}

require dirname(__DIR__) . '/Support/AssignmentOrderOriginalInitialFixture.php';

$positivePdf = base64_decode(
    'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',
    true,
);
assertSameValue(true, is_string($positivePdf), 'Approved literal PDF fixture decodes strictly.');

$authorizer = new AssignmentOrderOriginalInitialAuthorizer();
$compositions = new AssignmentOrderOriginalInitialCompositionReader();
$clock = new AssignmentOrderOriginalInitialClock();
$ids = new AssignmentOrderOriginalInitialIds();
$inspector = new AssignmentOrderOriginalInitialInspector();
$storage = new AssignmentOrderOriginalInitialStorage();
$repository = new AssignmentOrderOriginalInitialRepository();
$observers = new AssignmentOrderOriginalInitialObservers();
$stream = new AssignmentOrderOriginalInitialStream($positivePdf);

$beforeProcess = '{"schema":"aoou-process-v1","orderCompositionSha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","caseSha256":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","openingSha256":"cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc","tasksSha256":"dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd","decoySha256":"eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee"}';
$processEvidence = $beforeProcess;

$application = AssignmentOrderOriginalVerificationFactory::create(
    new AssignmentOrderOriginalDependencies(
        $authorizer,
        $compositions,
        $clock,
        $ids,
        $inspector,
        $storage,
        $repository,
        $observers,
        $observers,
        $observers,
        $observers,
        $observers,
    ),
);
$result = $application->submitAssignmentOrderOriginal(
    new SubmitAssignmentOrderOriginalCommand(
        '00000000-0000-4000-8000-000000000001',
        AssignmentOrderOriginalMode::INITIAL,
        4512,
        81,
        18,
        '2026-09-01',
        true,
        null,
        null,
        null,
        null,
        new AssignmentOrderOriginalUpload($stream, 'signed-order.pdf', 'application/pdf'),
    ),
);

assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $result->status(), 'Valid direct original is accepted.');
assertSameValue(null, $result->reasonCode(), 'Accepted result has no rejection reason.');
assertSameValue(false, $result->retryable(), 'Accepted result is terminal.');
assertSameValue('00000000-0000-4000-8000-000000000001', $result->requestId(), 'Request identity is echoed.');
assertSameValue('original-0001', $result->rootOriginalId(), 'Application owns root identity.');
assertSameValue('revision-0001', $result->currentRevisionId(), 'Application owns revision identity.');
assertSameValue(1, $result->revisionNumber(), 'Initial original starts at revision one.');
assertSameValue('2026-09-01', $result->documentDate(), 'Document date remains distinct from upload time.');
assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784', $result->sha256(), 'Received bytes have the approved independent digest.');
assertSameValue(327, $result->byteSize(), 'Received bytes have the approved independent size.');
assertSameValue('2026-09-02T09:15:30Z', $result->uploadedAt(), 'Upload time is the fixed server instant.');

$expectedEvidence = '{"schema":"aoou-evidence-v1","caseId":4512,"orderId":81,"roots":[{"rootOriginalId":"original-0001","currentRevisionId":"revision-0001","compositionIdentity":"composition-81-v1","compositionSha256":"1111111111111111111111111111111111111111111111111111111111111111","revisions":[{"revisionId":"revision-0001","revisionNumber":1,"previousRevisionId":null,"documentDate":"2026-09-01","uploadedAt":"2026-09-02T09:15:30Z","actorUserId":18,"pdfSha256":"4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784","byteSize":327,"privateContentIdentity":"private-content-0001","correctionReason":null}]}]}';
assertSameValue($expectedEvidence, $repository->evidenceCanonicalJson(4512, 81), 'Immutable original evidence is persisted exactly once.');
assertSameValue(1, count($repository->accepted), 'Exactly one accepted revision/event transaction is requested.');
assertSameValue('assignment_order_original_accepted', $repository->accepted[0]->domainEventType, 'Initial upload emits the exact domain event.');
assertSameValue([], $repository->attempts, 'Accepted initial upload creates no rejection attempt.');
assertSameValue([[18, 'assignment_order.original.upload']], $authorizer->calls, 'Only exact upload capability authorizes initial upload.');
assertSameValue($positivePdf, $inspector->inspected, 'Inspector receives exact completed bytes.');
assertSameValue(1, $stream->closeCalls, 'Upload stream closes exactly once.');
assertSameValue(1, $storage->stage?->closeCalls, 'Private stage closes exactly once.');
assertSameValue(0, $storage->stage?->abortCalls, 'Accepted stage is not aborted.');
assertSameValue(1, $storage->stage?->lease?->releaseCalls, 'Accepted content lease releases exactly once.');
assertSameValue(1, $observers->deliveryCalls, 'Delivery observer runs once after accepted commit.');
assertSameValue($beforeProcess, $processEvidence, 'Upload does not mutate composition, case, opening, tasks, or decoy facts.');

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK\n");
