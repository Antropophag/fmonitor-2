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

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, sections 1, 3, 4 and 12.
foreach ([AssignmentOrderOriginalApplication::class, AssignmentOrderOriginalVerificationFactory::class, SubmitAssignmentOrderOriginalCommand::class] as $type) {
    if (!class_exists($type) && !interface_exists($type)) {
        throw new TestFailure('INTENDED_RED: canonical production application seam is missing: ' . $type);
    }
}
require dirname(__DIR__) . '/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';

$pdf = base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK', true);

$run = static function (InMemoryAssignmentOrderOriginalInitialEnvironment $environment, string $requestId, int &$reads) use ($pdf) {
    $stream = new class($pdf, $reads) implements AssignmentOrderOriginalByteStream {
        private int $offset = 0;
        public function __construct(private string $bytes, private int &$reads) {}
        public function read(int $maximumBytes): AssignmentOrderOriginalStreamRead
        {
            $this->reads++;
            if ($this->offset === strlen($this->bytes)) return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF, '');
            $chunk = substr($this->bytes, $this->offset, $maximumBytes);
            $this->offset += strlen($chunk);
            return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES, $chunk);
        }
        public function close(): void {}
    };
    return AssignmentOrderOriginalVerificationFactory::create($environment->dependencies())->submitAssignmentOrderOriginal(
        new SubmitAssignmentOrderOriginalCommand($requestId, AssignmentOrderOriginalMode::INITIAL, 4512, 81, 18, '2026-09-01', true, null, null, null, null, new AssignmentOrderOriginalUpload($stream, 'signed.pdf', 'application/pdf')),
    );
};

$direct = new InMemoryAssignmentOrderOriginalInitialEnvironment();
$directReads = 0;
$directResult = $run($direct, '00000000-0000-4000-8000-000000000011', $directReads);
$postTemplate = new InMemoryAssignmentOrderOriginalInitialEnvironment();
$templateReads = 0;
$templateResult = $run($postTemplate, '00000000-0000-4000-8000-000000000012', $templateReads);
assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $directResult->status(), 'Direct initial upload accepted.');
assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $templateResult->status(), 'Post-template initial upload has identical command behavior.');
assertSameValue([$directResult->documentDate(), $directResult->sha256(), $directResult->byteSize(), $directResult->uploadedAt()], [$templateResult->documentDate(), $templateResult->sha256(), $templateResult->byteSize(), $templateResult->uploadedAt()], 'Optional template provenance cannot alter original evidence.');

foreach (['manager-role-only', 'assignment_order.prepare', 'assignment_order.confirm_registration', 'installation.open'] as $fallback) {
    $denied = new InMemoryAssignmentOrderOriginalInitialEnvironment();
    $denied->allowedCapability = $fallback;
    $reads = 0;
    $result = $run($denied, '00000000-0000-4000-8000-' . str_pad((string) (20 + $reads), 12, '0', STR_PAD_LEFT), $reads);
    assertSameValue([AssignmentOrderOriginalStatus::REJECTED, AssignmentOrderOriginalReason::AUTHORIZATION_DENIED, false], [$result->status(), $result->reasonCode(), $result->retryable()], 'No role/adjacent-capability fallback: ' . $fallback);
    assertSameValue(0, $reads, 'Authorization denial precedes stream read: ' . $fallback);
}

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK\n");
