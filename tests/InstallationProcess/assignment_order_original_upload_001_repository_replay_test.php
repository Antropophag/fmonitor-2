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

foreach ([AssignmentOrderOriginalApplication::class, AssignmentOrderOriginalVerificationFactory::class] as $type) {
    if (!class_exists($type) && !interface_exists($type)) throw new TestFailure('INTENDED_RED: canonical production application seam is missing: ' . $type);
}
require dirname(__DIR__) . '/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';

$pdf = base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK', true);
$stream = static function (int &$reads, string $bytes = '') use ($pdf): AssignmentOrderOriginalByteStream {
    return new class($bytes === '' ? $pdf : $bytes, $reads) implements AssignmentOrderOriginalByteStream {
        private int $offset = 0;
        public function __construct(private string $bytes, private int &$reads) {}
        public function read(int $maximumBytes): AssignmentOrderOriginalStreamRead { $this->reads++; if ($this->offset === strlen($this->bytes)) return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF, ''); $chunk=substr($this->bytes,$this->offset,$maximumBytes); $this->offset+=strlen($chunk); return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES,$chunk); }
        public function close(): void {}
    };
};
$command = static function (string $request, AssignmentOrderOriginalMode $mode, AssignmentOrderOriginalByteStream $upload, string $date='2026-09-01'): SubmitAssignmentOrderOriginalCommand {
    $correction = $mode === AssignmentOrderOriginalMode::CORRECTION;
    return new SubmitAssignmentOrderOriginalCommand($request,$mode,4512,81,18,$date,true,$correction?'original-0001':null,$correction?'revision-0001':null,$correction?'revision-0001':null,$correction?'Исправлена дата документа':null,new AssignmentOrderOriginalUpload($upload,'signed.pdf','application/pdf'));
};

$environment = new InMemoryAssignmentOrderOriginalInitialEnvironment();
$application = AssignmentOrderOriginalVerificationFactory::create($environment->dependencies());
$reads=0; $accepted=$application->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000051',AssignmentOrderOriginalMode::INITIAL,$stream($reads)));
assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED,$accepted->status(),'Typed accepted commit succeeds.');
assertSameValue(['00000000-0000-4000-8000-000000000051','assignment_order_original_accepted',1,null],[$environment->acceptedCommit?->requestId,$environment->acceptedCommit?->domainEventType,$environment->acceptedCommit?->newRevisionNumber,$environment->acceptedCommit?->previousRevisionId],'Commit DTO fixes request/event/revision invariants.');

$retryReads=0; $retry=$application->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000051',AssignmentOrderOriginalMode::INITIAL,$stream($retryReads,'NOT A PDF')));
assertSameValue([AssignmentOrderOriginalStatus::REPLAYED,null,false],[$retry->status(),$retry->reasonCode(),$retry->retryable()],'Terminal request hit returns stored accepted evidence as replay.');
assertSameValue(0,$retryReads,'Terminal request replay never reads replacement stream.');
assertSameValue([$accepted->rootOriginalId(),$accepted->currentRevisionId(),$accepted->sha256(),$accepted->uploadedAt()],[$retry->rootOriginalId(),$retry->currentRevisionId(),$retry->sha256(),$retry->uploadedAt()],'Request replay preserves exact stored evidence.');

$fingerprintReads=0; $fingerprintReplay=$application->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000052',AssignmentOrderOriginalMode::INITIAL,$stream($fingerprintReads)));
assertSameValue(AssignmentOrderOriginalStatus::REPLAYED,$fingerprintReplay->status(),'Cross-request accepted fingerprint replays immutable result.');

$environment->allowedCapability='assignment_order.original.correct';
$noChangeReads=0; $noChange=$application->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000053',AssignmentOrderOriginalMode::CORRECTION,$stream($noChangeReads)));
assertSameValue([AssignmentOrderOriginalStatus::REJECTED,AssignmentOrderOriginalReason::NO_CHANGES,false],[$noChange->status(),$noChange->reasonCode(),$noChange->retryable()],'Reason-only correction is not a change.');

$dateReads=0; $dateChange=$application->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000054',AssignmentOrderOriginalMode::CORRECTION,$stream($dateReads),'2026-09-02'));
assertSameValue([AssignmentOrderOriginalStatus::ACCEPTED,'revision-0002',2,'2026-09-02'],[$dateChange->status(),$dateChange->currentRevisionId(),$dateChange->revisionNumber(),$dateChange->documentDate()],'Same bytes plus changed date appends revision 2.');

$collision = new InMemoryAssignmentOrderOriginalInitialEnvironment(); $collisionApp=AssignmentOrderOriginalVerificationFactory::create($collision->dependencies()); $r=0; $collisionApp->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000055',AssignmentOrderOriginalMode::INITIAL,$stream($r))); $collision->allowedCapability='assignment_order.original.correct'; $collision->compositionSha256=str_repeat('2',64); $collisionReads=0; $conflict=$collisionApp->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000056',AssignmentOrderOriginalMode::CORRECTION,$stream($collisionReads),'2026-09-02'));
assertSameValue([AssignmentOrderOriginalStatus::CONFLICT,AssignmentOrderOriginalReason::SEMANTIC_COLLISION,false,0],[$conflict->status(),$conflict->reasonCode(),$conflict->retryable(),$collisionReads],'Composition drift conflicts before stream.');

$denied = new InMemoryAssignmentOrderOriginalInitialEnvironment(); $denied->allowedCapability='assignment_order.prepare'; $deniedReads=0; $denial=AssignmentOrderOriginalVerificationFactory::create($denied->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000057',AssignmentOrderOriginalMode::INITIAL,$stream($deniedReads)));
assertSameValue([AssignmentOrderOriginalStatus::REJECTED,AssignmentOrderOriginalReason::AUTHORIZATION_DENIED,false],[$denial->status(),$denial->reasonCode(),$denial->retryable()],'Denial has exact terminal result.');
assertSameValue(['requestId','actorUserId','mode','installationCaseId','assignmentOrderId','status','reason','retryable','attemptedAt'],array_keys(get_object_vars($denied->attemptCommits[0])),'Safe attempt DTO contains no filename/path/bytes/composition/reason text/exception.');

fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_REPLAY_OK\n");
