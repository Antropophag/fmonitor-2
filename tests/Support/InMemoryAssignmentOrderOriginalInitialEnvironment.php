<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionReader;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionSnapshot;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAcceptedCommit;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizer;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalByteStream;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalClock;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalCommitStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDependencies;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultInjector;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalIdResult;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalIdSource;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalIdStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLifecycleEvent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLifecycleObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLineageLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfInspection;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfInspector;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateContent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateContentLease;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateStage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateStorage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReferenceLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalRepository;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResultDeliveryObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResultLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSafeLogObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageEvent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageOutcome;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAttemptCommit;

final class InMemoryAssignmentOrderOriginalInitialEnvironment
{
    public const COMPOSITION_SHA256 = '1111111111111111111111111111111111111111111111111111111111111111';

    public ?AssignmentOrderOriginalAcceptedCommit $acceptedCommit = null;
    public string $storedBytes = '';

    /** @var array<string, mixed> */
    private array $process = [
        'orderCompositionSha256' => self::COMPOSITION_SHA256,
        'caseState' => 'assignment_order_prepared',
        'opening' => null,
        'actualStartDate' => null,
        'checklistAvailable' => false,
    ];

    public function processCanonicalJson(): string
    {
        return json_encode($this->process, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public function dependencies(): AssignmentOrderOriginalDependencies
    {
        $owner = $this;
        $authorizer = new class implements AssignmentOrderOriginalAuthorizer {
            public function authorize(int $actorUserId, string $exactCapability): AssignmentOrderOriginalAuthorizationStatus
            {
                return $actorUserId === 18 && $exactCapability === 'assignment_order.original.upload'
                    ? AssignmentOrderOriginalAuthorizationStatus::ALLOWED
                    : AssignmentOrderOriginalAuthorizationStatus::DENIED;
            }
        };
        $compositions = new class implements AssignmentOrderCompositionReader {
            public function find(int $caseId, int $orderId): AssignmentOrderCompositionSnapshot
            {
                return new AssignmentOrderCompositionSnapshot(
                    AssignmentOrderCompositionLookupStatus::FOUND,
                    $caseId,
                    $orderId,
                    'composition-81-v1',
                    InMemoryAssignmentOrderOriginalInitialEnvironment::COMPOSITION_SHA256,
                    [7001, 7002],
                    901,
                );
            }
        };
        $clock = new class implements AssignmentOrderOriginalClock {
            public function nowUtc(): string { return '2026-09-02T09:15:30Z'; }
        };
        $ids = new class implements AssignmentOrderOriginalIdSource {
            public function nextRootId(): AssignmentOrderOriginalIdResult
            {
                return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED, 'original-0001');
            }
            public function nextRevisionId(): AssignmentOrderOriginalIdResult
            {
                return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED, 'revision-0001');
            }
        };
        $inspector = new class implements AssignmentOrderOriginalPdfInspector {
            public function inspect(string $completedBytes): AssignmentOrderOriginalPdfInspection
            {
                return AssignmentOrderOriginalPdfInspection::passive();
            }
            public function algorithmId(): string { return 'fmonitor-passive-pdf-v1'; }
        };
        $storage = new InMemoryAssignmentOrderOriginalInitialStorage($owner);
        $repository = new InMemoryAssignmentOrderOriginalInitialRepository($owner);
        $lifecycle = new class implements AssignmentOrderOriginalLifecycleObserver {
            public function observe(AssignmentOrderOriginalLifecycleEvent $event): void {}
        };
        $storageObserver = new class implements AssignmentOrderOriginalStorageObserver {
            public function observe(AssignmentOrderOriginalStorageEvent $event, ?string $opaqueIdentity): void {}
        };
        $faults = new class implements AssignmentOrderOriginalFaultInjector {
            public function before(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultPoint $point): void {}
        };
        $safeLog = new class implements AssignmentOrderOriginalSafeLogObserver {
            public function record(string $event, array $safeFields): void {}
        };
        $delivery = new class implements AssignmentOrderOriginalResultDeliveryObserver {
            public function afterCommitBeforeReturn(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult $result): void {}
        };

        return new AssignmentOrderOriginalDependencies(
            $authorizer, $compositions, $clock, $ids, $inspector, $storage,
            $repository, $lifecycle, $storageObserver, $faults, $safeLog, $delivery,
        );
    }
}

final class InMemoryAssignmentOrderOriginalInitialStorage implements AssignmentOrderOriginalPrivateStorage
{
    public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
    public function beginStage(): AssignmentOrderOriginalPrivateStage
    {
        return new InMemoryAssignmentOrderOriginalInitialStage($this->owner);
    }
    public function listOrphans(string $cutoffUtc, int $limit, ?string $cursor): \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOrphanPage
    {
        throw new \LogicException('Not used by initial upload.');
    }
    public function acquireDigestLock(string $opaqueIdentity): \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDigestLock
    {
        throw new \LogicException('Not used by initial upload.');
    }
    public function deleteLocked(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDigestLock $lock): AssignmentOrderOriginalStorageStatus
    {
        throw new \LogicException('Not used by initial upload.');
    }
    public function inventoryCanonicalJson(): string { return '{}'; }
}

final class InMemoryAssignmentOrderOriginalInitialStage implements AssignmentOrderOriginalPrivateStage
{
    private string $bytes = '';
    public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
    public function write(string $chunk): AssignmentOrderOriginalStorageStatus
    {
        $this->bytes .= $chunk;
        return AssignmentOrderOriginalStorageStatus::OK;
    }
    public function completedBytesForInspection(): string { return $this->bytes; }
    public function finalize(string $sha256, int $byteSize): AssignmentOrderOriginalStorageOutcome
    {
        $this->owner->storedBytes = $this->bytes;
        $content = new class($sha256, $byteSize) implements AssignmentOrderOriginalPrivateContent {
            public function __construct(private string $digest, private int $size) {}
            public function opaqueIdentity(): string { return 'private-content-0001'; }
            public function sha256(): string { return $this->digest; }
            public function byteSize(): int { return $this->size; }
        };
        $lease = new class($content) implements AssignmentOrderOriginalPrivateContentLease {
            public function __construct(private AssignmentOrderOriginalPrivateContent $contentValue) {}
            public function status(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
            public function content(): ?AssignmentOrderOriginalPrivateContent { return $this->contentValue; }
            public function release(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
        };
        return new class($lease) implements AssignmentOrderOriginalStorageOutcome {
            public function __construct(private AssignmentOrderOriginalPrivateContentLease $leaseValue) {}
            public function status(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
            public function lease(): ?AssignmentOrderOriginalPrivateContentLease { return $this->leaseValue; }
        };
    }
    public function abort(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
    public function close(): void {}
}

final class InMemoryAssignmentOrderOriginalInitialRepository implements AssignmentOrderOriginalRepository
{
    public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup { return $this->miss(); }
    public function findAcceptedFingerprint(string $fingerprint): AssignmentOrderOriginalResultLookup { return $this->miss(); }
    private function miss(): AssignmentOrderOriginalResultLookup
    {
        return new class implements AssignmentOrderOriginalResultLookup {
            public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::NOT_FOUND; }
            public function result(): ?\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult { return null; }
        };
    }
    public function findLineage(string $rootOriginalId): AssignmentOrderOriginalLineageLookup
    {
        return new class implements AssignmentOrderOriginalLineageLookup {
            public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::NOT_FOUND; }
            public function rootOriginalId(): ?string { return null; }
            public function currentRevisionId(): ?string { return null; }
            public function currentRevisionNumber(): ?int { return null; }
            public function compositionIdentity(): ?string { return null; }
            public function compositionSha256(): ?string { return null; }
            public function containsRevision(string $revisionId): bool { return false; }
        };
    }
    public function commitAccepted(AssignmentOrderOriginalAcceptedCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        $this->owner->acceptedCommit = $commit;
        return AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    public function commitAttempt(AssignmentOrderOriginalAttemptCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        throw new \LogicException('Not used by valid initial upload.');
    }
    public function hasCommittedContent(string $opaqueIdentity): AssignmentOrderOriginalReferenceLookup
    {
        throw new \LogicException('Not used by initial upload.');
    }
    public function evidenceCanonicalJson(int $caseId, int $orderId): string { return '{}'; }
}
