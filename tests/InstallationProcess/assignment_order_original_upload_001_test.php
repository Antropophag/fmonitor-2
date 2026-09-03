<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalByteStream;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamRead;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamReadStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand;
use FMonitor2\Tests\Support\InMemoryAssignmentOrderOriginalInitialEnvironment;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, Example A.
$requiredTypes = [
    AssignmentOrderOriginalApplication::class,
    AssignmentOrderOriginalVerificationFactory::class,
    SubmitAssignmentOrderOriginalCommand::class,
];
foreach ($requiredTypes as $requiredType) {
    if (!class_exists($requiredType) && !interface_exists($requiredType)) {
        throw new TestFailure(
            'INTENDED_RED: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 canonical production application seam is missing: '
            . $requiredType,
        );
    }
}

require dirname(__DIR__) . '/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';

$pdf = base64_decode(
    'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',
    true,
);
assertSameValue(327, strlen($pdf), 'Approved positive fixture byte size is an independent literal oracle.');
assertSameValue('4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784', hash('sha256', $pdf), 'Approved positive fixture digest is an independent literal oracle.');

$stream = new class($pdf) implements AssignmentOrderOriginalByteStream {
    private int $offset = 0;
    public function __construct(private string $bytes) {}
    public function read(int $maximumBytes): AssignmentOrderOriginalStreamRead
    {
        if ($this->offset === strlen($this->bytes)) {
            return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF, '');
        }
        $chunk = substr($this->bytes, $this->offset, $maximumBytes);
        $this->offset += strlen($chunk);
        return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES, $chunk);
    }
    public function close(): void {}
};

$environment = new InMemoryAssignmentOrderOriginalInitialEnvironment();
$beforeProcess = $environment->processCanonicalJson();
$application = AssignmentOrderOriginalVerificationFactory::create($environment->dependencies());
$result = $application->submitAssignmentOrderOriginal(new SubmitAssignmentOrderOriginalCommand(
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
));

assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $result->status(), 'Valid direct initial upload is accepted.');
assertSameValue([
    null, false, '00000000-0000-4000-8000-000000000001', 'original-0001', 'revision-0001', 1,
    '2026-09-01', '4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',
    327, '2026-09-02T09:15:30Z',
], [
    $result->reasonCode(), $result->retryable(), $result->requestId(), $result->rootOriginalId(),
    $result->currentRevisionId(), $result->revisionNumber(), $result->documentDate(),
    $result->sha256(), $result->byteSize(), $result->uploadedAt(),
], 'Accepted result exposes the independently specified immutable evidence.');
assertSameValue('composition-81-v1', $environment->acceptedCommit?->compositionIdentity, 'Accepted fact freezes composition identity.');
assertSameValue(InMemoryAssignmentOrderOriginalInitialEnvironment::COMPOSITION_SHA256, $environment->acceptedCommit?->compositionSha256, 'Accepted fact freezes composition hash.');
assertSameValue($pdf, $environment->storedBytes, 'Private original bytes remain byte-identical.');
assertSameValue($beforeProcess, $environment->processCanonicalJson(), 'Upload mutates neither composition nor opening/process state.');

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK\n");
