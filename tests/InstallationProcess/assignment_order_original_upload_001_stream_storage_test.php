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

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, sections 5, 10 and 15.
foreach ([AssignmentOrderOriginalApplication::class, AssignmentOrderOriginalVerificationFactory::class] as $type) {
    if (!class_exists($type) && !interface_exists($type)) throw new TestFailure('INTENDED_RED: canonical production application seam is missing: ' . $type);
}
require dirname(__DIR__) . '/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';

$run = static function (string $bytes, ?string $streamFault, ?string $storageFault, string $requestId): array {
    $environment = new InMemoryAssignmentOrderOriginalInitialEnvironment();
    $environment->storageFault = $storageFault;
    $reads = [];
    $closed = 0;
    $stream = new class($bytes, $streamFault, $reads, $closed) implements AssignmentOrderOriginalByteStream {
        private int $offset = 0;
        public function __construct(private string $bytes, private ?string $fault, private array &$reads, private int &$closed) {}
        public function read(int $maximumBytes): AssignmentOrderOriginalStreamRead
        {
            $this->reads[] = $maximumBytes;
            if ($this->fault === 'first_read') return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::FAILED, '');
            if ($this->offset === strlen($this->bytes)) return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF, '');
            $chunk = substr($this->bytes, $this->offset, $maximumBytes);
            $this->offset += strlen($chunk);
            if ($this->fault === 'incomplete' && $this->offset >= 65536) return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::FAILED, '');
            return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES, $chunk);
        }
        public function close(): void { $this->closed++; }
    };
    $before = $environment->processCanonicalJson();
    $result = AssignmentOrderOriginalVerificationFactory::create($environment->dependencies())->submitAssignmentOrderOriginal(new SubmitAssignmentOrderOriginalCommand(
        $requestId, AssignmentOrderOriginalMode::INITIAL, 4512, 81, 18, '2026-09-01', true, null, null, null, null,
        new AssignmentOrderOriginalUpload($stream, 'boundary.pdf', 'application/pdf'),
    ));
    return [$result, $environment, $reads, $closed, $before];
};

$maximum = "%PDF-" . str_repeat('A', 20_971_520 - 5);
[$accepted, $ok, $reads, $closed, $before] = $run($maximum, null, null, '00000000-0000-4000-8000-000000000031');
assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $accepted->status(), 'Inclusive 20 MiB boundary accepted.');
assertSameValue([20_971_520, 'b87cd7354478d953ea1856cd7b220f3962c53b33b5a53ea01521f0da3ac4104a'], [$accepted->byteSize(), $accepted->sha256()], 'Received-byte boundary has independent size/digest oracle.');
assertSameValue([], array_values(array_filter($reads, static fn(int $n): bool => $n !== 65_536)), 'Every stream request is bounded to 65536 bytes.');
assertSameValue(1, $closed, 'Accepted stream closes exactly once.');
assertSameValue($maximum, $ok->storedBytes, 'Accepted stage stores byte-identical content.');
assertSameValue($before, $ok->processCanonicalJson(), 'Accepted upload does not mutate composition/opening.');
$okWrites = array_values(array_filter($ok->storageEvents, static fn(string $event): bool => str_starts_with($event, 'STAGE_WRITE:')));
assertSameValue(320, count($okWrites), 'Exactly 320 bounded chunks write 20 MiB.');
assertSameValue([], array_values(array_filter($okWrites, static fn(string $event): bool => $event !== 'STAGE_WRITE:65536')), 'Every accepted write is exactly 65536 bytes.');
assertSameValue(['STAGE_BEGIN', 'STAGE_DONE', 'FINALIZE_BEGIN', 'FINALIZE_DONE', 'STAGE_CLOSE'], array_values(array_filter($ok->storageEvents, static fn(string $event): bool => !str_starts_with($event, 'STAGE_WRITE:'))), 'Success event ordering is exact.');

[$tooLarge, $large, $largeReads, $largeClosed, $largeBefore] = $run($maximum . 'B', null, null, '00000000-0000-4000-8000-000000000032');
assertSameValue([AssignmentOrderOriginalStatus::REJECTED, AssignmentOrderOriginalReason::FILE_TOO_LARGE], [$tooLarge->status(), $tooLarge->reasonCode()], 'Byte 20,971,521 is rejected.');
assertSameValue('', $large->storedBytes, 'Over-limit byte is never finalized/publicized.');
assertSameValue(true, in_array('ABORT_BEGIN', $large->storageEvents, true), 'Over-limit stage aborts.');
assertSameValue(false, in_array('FINALIZE_BEGIN', $large->storageEvents, true), 'Over-limit stage never finalizes.');
assertSameValue(1, $largeClosed, 'Over-limit stream closes exactly once.');
assertSameValue($largeBefore, $large->processCanonicalJson(), 'Over-limit rejection has no process mutation.');

foreach ([
    ['first_read', null, AssignmentOrderOriginalReason::STREAM_FAILURE],
    ['incomplete', null, AssignmentOrderOriginalReason::STREAM_FAILURE],
    [null, 'write', AssignmentOrderOriginalReason::STORAGE_FAILURE],
    [null, 'finalize', AssignmentOrderOriginalReason::STORAGE_FAILURE],
] as $index => [$streamFault, $storageFault, $reason]) {
    [$failed, $environment, $faultReads, $faultClosed, $faultBefore] = $run("%PDF-" . str_repeat('x', 70_000), $streamFault, $storageFault, '00000000-0000-4000-8000-' . str_pad((string) (40 + $index), 12, '0', STR_PAD_LEFT));
    assertSameValue([AssignmentOrderOriginalStatus::FAILED, $reason, true], [$failed->status(), $failed->reasonCode(), $failed->retryable()], 'Exact typed stream/storage fault result.');
    assertSameValue(true, in_array('ABORT_BEGIN', $environment->storageEvents, true), 'Fault aborts owned stage.');
    assertSameValue(true, array_search('ABORT_BEGIN', $environment->storageEvents, true) < array_search('ABORT_DONE', $environment->storageEvents, true), 'Abort begin precedes durable abort done.');
    assertSameValue('STAGE_CLOSE', $environment->storageEvents[array_key_last($environment->storageEvents)], 'Fault closes stage after abort.');
    assertSameValue('', $environment->storedBytes, 'Fault leaves zero public/finalized orphan evidence.');
    assertSameValue(1, $faultClosed, 'Fault closes stream exactly once.');
    assertSameValue($faultBefore, $environment->processCanonicalJson(), 'Fault has no composition/opening mutation.');
}

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_STREAM_STORAGE_OK\n");
