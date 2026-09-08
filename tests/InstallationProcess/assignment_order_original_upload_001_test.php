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
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialProcessEvidenceReader;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialProcessState;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialRepository;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialStorage;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialStream;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v42, Example A.
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalInitialProcessState.php';

$compositionValues=['caseId'=>4512,'compositionIdentity'=>'composition-81-v1','engineerUserId'=>31,'installers'=>[7001,7002],'orderId'=>81];
$compositionJson=json_encode($compositionValues,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
assertSameValue('{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001,7002],"orderId":81}',$compositionJson,'Composition canonical JSON is an independent literal.');
assertSameValue('388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',hash('sha256',$compositionJson),'Composition digest is independently derived.');
foreach([
    'case'=>array_replace($compositionValues,['caseId'=>4513]),
    'identity'=>array_replace($compositionValues,['compositionIdentity'=>'composition-81-v2']),
    'engineer'=>array_replace($compositionValues,['engineerUserId'=>32]),
    'installer-member'=>array_replace($compositionValues,['installers'=>[7001,7003]]),
    'installer-order'=>array_replace($compositionValues,['installers'=>[7002,7001]]),
    'order'=>array_replace($compositionValues,['orderId'=>82]),
]as$axis=>$mutation)assertSameValue(false,hash_equals(hash('sha256',$compositionJson),hash('sha256',json_encode($mutation,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))),"Composition {$axis} mutation changes digest.");
foreach(['empty'=>[],'duplicate'=>[7001,7001],'nonpositive'=>[0,7002]]as$axis=>$members)assertSameValue(false,$members!==[]&&count($members)===count(array_unique($members))&&min($members)>0,"Composition {$axis} validation rejects invalid members.");

$downstreamFamilies = [
    'orderCompositionSha256',
    'caseSha256',
    'openingSha256',
    'tasksSha256',
    'checklistSha256',
    'decoySha256',
];
foreach ($downstreamFamilies as $family) {
    $sensitivityState = new AssignmentOrderOriginalInitialProcessState();
    $sensitivityReader = new AssignmentOrderOriginalInitialProcessEvidenceReader($sensitivityState);
    $sensitivityBefore = $sensitivityReader->canonicalJson();
    $sensitivityState->perturb($family);
    assertSameValue(
        false,
        hash_equals($sensitivityBefore, $sensitivityReader->canonicalJson()),
        "Downstream no-mutation oracle detects a {$family} perturbation.",
    );
}

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
$processState = new AssignmentOrderOriginalInitialProcessState();
$processEvidence = new AssignmentOrderOriginalInitialProcessEvidenceReader($processState);
$compositions = new AssignmentOrderOriginalInitialCompositionReader($processState);
$clock = new AssignmentOrderOriginalInitialClock();
$ids = new AssignmentOrderOriginalInitialIds();
$inspector = new AssignmentOrderOriginalInitialInspector();
$storage = new AssignmentOrderOriginalInitialStorage();
$repository = new AssignmentOrderOriginalInitialRepository($processState);
$observers = new AssignmentOrderOriginalInitialObservers();
$stream = new AssignmentOrderOriginalInitialStream($positivePdf);

$beforeProcess = $processEvidence->canonicalJson();
assertSameValue(
    '{"schema":"aoou-process-v1","orderCompositionSha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","caseSha256":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","openingSha256":"cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc","tasksSha256":"dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd","checklistSha256":"ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff","decoySha256":"eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee"}',
    $beforeProcess,
    'Independent observer reads every exactly seeded downstream family.',
);

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

$expectedEvidence = '{"schema":"aoou-evidence-v1","caseId":4512,"orderId":81,"roots":[{"rootOriginalId":"original-0001","currentRevisionId":"revision-0001","compositionIdentity":"composition-81-v1","compositionSha256":"388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5","revisions":[{"revisionId":"revision-0001","revisionNumber":1,"previousRevisionId":null,"documentDate":"2026-09-01","uploadedAt":"2026-09-02T09:15:30Z","actorUserId":18,"pdfSha256":"4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784","byteSize":327,"privateContentIdentity":"private-content-0001","correctionReason":null}]}]}';
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
assertSameValue(
    $beforeProcess,
    $processEvidence->canonicalJson(),
    'Upload does not mutate composition, case, opening, tasks, checklist availability, or decoy facts.',
);

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK\n");
