<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionReader;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionSnapshot;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAcceptedCommit;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAttemptCommit;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizer;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalByteStream;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalClock;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalCommitStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDigestLock;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultInjector;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultPoint;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalIdResult;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalIdSource;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalIdStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLifecycleEvent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLifecycleObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLineageLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOrphanPage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfInspection;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfInspector;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateContent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateContentLease;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateStage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateStorage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReferenceLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalRepository;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResultDeliveryObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResultLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSafeLogObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageEvent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageOutcome;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamRead;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamReadStatus;

final class AssignmentOrderOriginalInitialStream implements AssignmentOrderOriginalByteStream
{
    private int $offset = 0;
    public int $closeCalls = 0;

    public function __construct(private readonly string $bytes) {}

    public function read(int $maximumBytes): AssignmentOrderOriginalStreamRead
    {
        if ($this->offset === strlen($this->bytes)) {
            return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF, '');
        }
        $chunk = substr($this->bytes, $this->offset, $maximumBytes);
        $this->offset += strlen($chunk);
        return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES, $chunk);
    }

    public function close(): void
    {
        ++$this->closeCalls;
    }
}

final class AssignmentOrderOriginalInitialAuthorizer implements AssignmentOrderOriginalAuthorizer
{
    /** @var list<array{int,string}> */
    public array $calls = [];

    public function authorize(int $actorUserId, string $exactCapability): AssignmentOrderOriginalAuthorizationStatus
    {
        $this->calls[] = [$actorUserId, $exactCapability];
        return $actorUserId === 18 && $exactCapability === 'assignment_order.original.upload'
            ? AssignmentOrderOriginalAuthorizationStatus::ALLOWED
            : AssignmentOrderOriginalAuthorizationStatus::DENIED;
    }
}

final class AssignmentOrderOriginalInitialCompositionReader implements AssignmentOrderCompositionReader
{
    public function find(int $caseId, int $orderId): AssignmentOrderCompositionSnapshot
    {
        return new AssignmentOrderCompositionSnapshot(
            AssignmentOrderCompositionLookupStatus::FOUND,
            $caseId,
            $orderId,
            'composition-81-v1',
            str_repeat('1', 64),
            [7001, 7002],
            901,
        );
    }
}

final class AssignmentOrderOriginalInitialClock implements AssignmentOrderOriginalClock
{
    public function nowUtc(): string { return '2026-09-02T09:15:30Z'; }
}

final class AssignmentOrderOriginalInitialIds implements AssignmentOrderOriginalIdSource
{
    public function nextRootId(): AssignmentOrderOriginalIdResult
    {
        return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED, 'original-0001');
    }

    public function nextRevisionId(): AssignmentOrderOriginalIdResult
    {
        return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED, 'revision-0001');
    }
}

final class AssignmentOrderOriginalInitialInspector implements AssignmentOrderOriginalPdfInspector
{
    public ?string $inspected = null;
    public function inspect(string $completedBytes): AssignmentOrderOriginalPdfInspection
    {
        $this->inspected = $completedBytes;
        return AssignmentOrderOriginalPdfInspection::passive();
    }
    public function algorithmId(): string { return 'fmonitor-passive-pdf-v1'; }
}

final readonly class AssignmentOrderOriginalInitialContent implements AssignmentOrderOriginalPrivateContent
{
    public function __construct(private string $digest, private int $size) {}
    public function opaqueIdentity(): string { return 'private-content-0001'; }
    public function sha256(): string { return $this->digest; }
    public function byteSize(): int { return $this->size; }
}

final class AssignmentOrderOriginalInitialLease implements AssignmentOrderOriginalPrivateContentLease
{
    public int $releaseCalls = 0;
    public function __construct(private readonly AssignmentOrderOriginalPrivateContent $content) {}
    public function status(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
    public function content(): ?AssignmentOrderOriginalPrivateContent { return $this->content; }
    public function release(): AssignmentOrderOriginalStorageStatus
    {
        ++$this->releaseCalls;
        return AssignmentOrderOriginalStorageStatus::OK;
    }
}

final readonly class AssignmentOrderOriginalInitialStorageOutcome implements AssignmentOrderOriginalStorageOutcome
{
    public function __construct(private AssignmentOrderOriginalPrivateContentLease $lease) {}
    public function status(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
    public function lease(): ?AssignmentOrderOriginalPrivateContentLease { return $this->lease; }
}

final class AssignmentOrderOriginalInitialStage implements AssignmentOrderOriginalPrivateStage
{
    private string $bytes = '';
    public int $abortCalls = 0;
    public int $closeCalls = 0;
    public ?AssignmentOrderOriginalInitialLease $lease = null;

    public function write(string $chunk): AssignmentOrderOriginalStorageStatus
    {
        $this->bytes .= $chunk;
        return AssignmentOrderOriginalStorageStatus::OK;
    }
    public function completedBytesForInspection(): string { return $this->bytes; }
    public function finalize(string $sha256, int $byteSize): AssignmentOrderOriginalStorageOutcome
    {
        $this->lease = new AssignmentOrderOriginalInitialLease(
            new AssignmentOrderOriginalInitialContent($sha256, $byteSize),
        );
        return new AssignmentOrderOriginalInitialStorageOutcome($this->lease);
    }
    public function abort(): AssignmentOrderOriginalStorageStatus
    {
        ++$this->abortCalls;
        return AssignmentOrderOriginalStorageStatus::OK;
    }
    public function close(): void { ++$this->closeCalls; }
}

final class AssignmentOrderOriginalInitialStorage implements AssignmentOrderOriginalPrivateStorage
{
    public ?AssignmentOrderOriginalInitialStage $stage = null;
    public function beginStage(): AssignmentOrderOriginalPrivateStage
    {
        return $this->stage = new AssignmentOrderOriginalInitialStage();
    }
    public function listOrphans(string $cutoffUtc, int $limit, ?string $cursor): AssignmentOrderOriginalOrphanPage
    {
        throw new \LogicException('Initial upload must not enumerate orphans.');
    }
    public function acquireDigestLock(string $opaqueIdentity): AssignmentOrderOriginalDigestLock
    {
        throw new \LogicException('Initial upload must not invoke maintenance locks.');
    }
    public function deleteLocked(AssignmentOrderOriginalDigestLock $lock): AssignmentOrderOriginalStorageStatus
    {
        throw new \LogicException('Initial upload must not delete content.');
    }
    public function inventoryCanonicalJson(): string
    {
        return '{"schema":"aoou-blobs-v1","stages":[],"finalized":[]}';
    }
}

final readonly class AssignmentOrderOriginalInitialResultLookup implements AssignmentOrderOriginalResultLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::NOT_FOUND; }
    public function result(): ?AssignmentOrderOriginalResult { return null; }
}

final readonly class AssignmentOrderOriginalInitialLineageLookup implements AssignmentOrderOriginalLineageLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::NOT_FOUND; }
    public function rootOriginalId(): ?string { return null; }
    public function currentRevisionId(): ?string { return null; }
    public function currentRevisionNumber(): ?int { return null; }
    public function compositionIdentity(): ?string { return null; }
    public function compositionSha256(): ?string { return null; }
    public function containsRevision(string $revisionId): bool { return false; }
}

final readonly class AssignmentOrderOriginalInitialReferenceLookup implements AssignmentOrderOriginalReferenceLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::NOT_FOUND; }
    public function referenced(): ?bool { return false; }
}

final class AssignmentOrderOriginalInitialRepository implements AssignmentOrderOriginalRepository
{
    /** @var list<AssignmentOrderOriginalAcceptedCommit> */
    public array $accepted = [];
    /** @var list<AssignmentOrderOriginalAttemptCommit> */
    public array $attempts = [];

    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup
    {
        return new AssignmentOrderOriginalInitialResultLookup();
    }
    public function findAcceptedFingerprint(string $fingerprint): AssignmentOrderOriginalResultLookup
    {
        return new AssignmentOrderOriginalInitialResultLookup();
    }
    public function findLineage(string $rootOriginalId): AssignmentOrderOriginalLineageLookup
    {
        return new AssignmentOrderOriginalInitialLineageLookup();
    }
    public function commitAccepted(AssignmentOrderOriginalAcceptedCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        $this->accepted[] = $commit;
        return AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    public function commitAttempt(AssignmentOrderOriginalAttemptCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        $this->attempts[] = $commit;
        return AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    public function hasCommittedContent(string $opaqueIdentity): AssignmentOrderOriginalReferenceLookup
    {
        return new AssignmentOrderOriginalInitialReferenceLookup();
    }
    public function evidenceCanonicalJson(int $caseId, int $orderId): string
    {
        $roots = [];
        if (isset($this->accepted[0])) {
            $commit = $this->accepted[0];
            $roots[] = [
                'rootOriginalId' => $commit->rootOriginalId,
                'currentRevisionId' => $commit->newRevisionId,
                'compositionIdentity' => $commit->compositionIdentity,
                'compositionSha256' => $commit->compositionSha256,
                'revisions' => [[
                    'revisionId' => $commit->newRevisionId,
                    'revisionNumber' => $commit->newRevisionNumber,
                    'previousRevisionId' => $commit->previousRevisionId,
                    'documentDate' => $commit->documentDate,
                    'uploadedAt' => $commit->uploadedAt,
                    'actorUserId' => $commit->actorUserId,
                    'pdfSha256' => $commit->pdfSha256,
                    'byteSize' => $commit->byteSize,
                    'privateContentIdentity' => $commit->privateContentIdentity,
                    'correctionReason' => $commit->correctionReason,
                ]],
            ];
        }
        return json_encode(
            ['schema' => 'aoou-evidence-v1', 'caseId' => $caseId, 'orderId' => $orderId, 'roots' => $roots],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
    }
}

final class AssignmentOrderOriginalInitialObservers implements
    AssignmentOrderOriginalLifecycleObserver,
    AssignmentOrderOriginalStorageObserver,
    AssignmentOrderOriginalFaultInjector,
    AssignmentOrderOriginalSafeLogObserver,
    AssignmentOrderOriginalResultDeliveryObserver
{
    /** @var list<AssignmentOrderOriginalLifecycleEvent> */
    public array $lifecycle = [];
    /** @var list<array{AssignmentOrderOriginalStorageEvent,?string}> */
    public array $storage = [];
    public int $deliveryCalls = 0;
    public function observe(AssignmentOrderOriginalLifecycleEvent|AssignmentOrderOriginalStorageEvent $event, ?string $opaqueIdentity = null): void
    {
        if ($event instanceof AssignmentOrderOriginalLifecycleEvent) {
            $this->lifecycle[] = $event;
        } else {
            $this->storage[] = [$event, $opaqueIdentity];
        }
    }
    public function before(AssignmentOrderOriginalFaultPoint $point): void {}
    public function record(string $event, array $safeFields): void {}
    public function afterCommitBeforeReturn(AssignmentOrderOriginalResult $result): void { ++$this->deliveryCalls; }
}
