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
    public int $actorUserId = 18;
    public string $allowedCapability = 'assignment_order.original.upload';
    public bool $authorizationAvailable = true;
    public int $authorizationCalls = 0;
    public ?string $throwAt = null;
    public string $throwSecret = 'SECRET-PORT-DETAIL';
    /** @var array<string,int> */ public array $throwOnCall = [];
    /** @var array<string,int> */ public array $phaseCalls = [];
    public ?string $storageFault = null;
    /** @var list<string> */
    public array $storageEvents = [];
    public string $compositionIdentity = 'composition-81-v1';
    public string $compositionSha256 = self::COMPOSITION_SHA256;
    /** @var list<AssignmentOrderOriginalAttemptCommit> */
    public array $attemptCommits = [];
    /** @var array<string, \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult> */
    public array $terminalResults = [];
    /** @var array<string, \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult> */
    public array $fingerprintResults = [];
    public int $rootIdCalls = 0;
    public int $revisionIdCalls = 0;
    public ?string $commitRace = null;
    public ?AssignmentOrderOriginalCommitStatus $commitOutcome = null;
    public ?string $unknownResolution = null;
    public int $acceptedCommitCalls = 0;
    public AssignmentOrderOriginalCommitStatus $attemptCommitStatus = AssignmentOrderOriginalCommitStatus::COMMITTED;
    public int $requestLookupCalls = 0;
    public bool $leaseHeld = false;
    public bool $storageRecoveryOwnsLease = false;
    public int $leaseReleaseCalls = 0;
    public ?string $leaseReleaseFault = null;
    /** @var list<string> */
    public array $repositoryTrace = [];
    /** @var list<array{event:string,safeFields:array<string, scalar|null>}> */
    public array $safeLogs = [];
    public bool $deliveryThrows = false;
    public int $deliveryCalls = 0;
    /** @var list<AssignmentOrderOriginalAcceptedCommit> */
    public array $acceptedCommits = [];

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
    public function trip(string $phase): void { $this->phaseCalls[$phase]=($this->phaseCalls[$phase]??0)+1;if ($this->throwAt === $phase||($this->throwOnCall[$phase]??0)===$this->phaseCalls[$phase]) throw new \RuntimeException($this->throwSecret . ':' . $phase); }

    public function dependencies(): AssignmentOrderOriginalDependencies
    {
        $owner = $this;
        $authorizer = new class($owner) implements AssignmentOrderOriginalAuthorizer {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function authorize(int $actorUserId, string $exactCapability): AssignmentOrderOriginalAuthorizationStatus
            {
                $this->owner->authorizationCalls++;
                $this->owner->trip('authorizer');
                if (!$this->owner->authorizationAvailable) {
                    return AssignmentOrderOriginalAuthorizationStatus::UNAVAILABLE;
                }
                return $actorUserId === $this->owner->actorUserId && $exactCapability === $this->owner->allowedCapability
                    ? AssignmentOrderOriginalAuthorizationStatus::ALLOWED
                    : AssignmentOrderOriginalAuthorizationStatus::DENIED;
            }
        };
        $compositions = new class($owner) implements AssignmentOrderCompositionReader {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function find(int $caseId, int $orderId): AssignmentOrderCompositionSnapshot
            {
                $this->owner->trip('composition');
                return new AssignmentOrderCompositionSnapshot(
                    AssignmentOrderCompositionLookupStatus::FOUND,
                    $caseId,
                    $orderId,
                    $this->owner->compositionIdentity,
                    $this->owner->compositionSha256,
                    [7001, 7002],
                    901,
                );
            }
        };
        $clock = new class($owner) implements AssignmentOrderOriginalClock {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function nowUtc(): string { $this->owner->trip('clock'); return '2026-09-02T09:15:30Z'; }
        };
        $ids = new class($owner) implements AssignmentOrderOriginalIdSource {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function nextRootId(): AssignmentOrderOriginalIdResult
            {
                $this->owner->rootIdCalls++;
                $this->owner->trip('root_id');
                return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED, 'original-' . str_pad((string) $this->owner->rootIdCalls, 4, '0', STR_PAD_LEFT));
            }
            public function nextRevisionId(): AssignmentOrderOriginalIdResult
            {
                $this->owner->revisionIdCalls++;
                $this->owner->trip('revision_id');
                return new AssignmentOrderOriginalIdResult(AssignmentOrderOriginalIdStatus::GENERATED, 'revision-' . str_pad((string) $this->owner->revisionIdCalls, 4, '0', STR_PAD_LEFT));
            }
        };
        $inspector = new class($owner) implements AssignmentOrderOriginalPdfInspector {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function inspect(string $completedBytes): AssignmentOrderOriginalPdfInspection
            {
                $this->owner->trip('inspector'); return AssignmentOrderOriginalPdfInspection::passive();
            }
            public function algorithmId(): string { return 'fmonitor-passive-pdf-v1'; }
        };
        $storage = new InMemoryAssignmentOrderOriginalInitialStorage($owner);
        $repository = new InMemoryAssignmentOrderOriginalInitialRepository($owner);
        $lifecycle = new class($owner) implements AssignmentOrderOriginalLifecycleObserver {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function observe(AssignmentOrderOriginalLifecycleEvent $event): void { $this->owner->trip('lifecycle'); }
        };
        $storageObserver = new class($owner) implements AssignmentOrderOriginalStorageObserver {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function observe(AssignmentOrderOriginalStorageEvent $event, ?string $opaqueIdentity): void { $this->owner->trip('storage_observer'); }
        };
        $faults = new class($owner) implements AssignmentOrderOriginalFaultInjector {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function before(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultPoint $point): void { $this->owner->trip('fault_injector'); }
        };
        $safeLog = new class($owner) implements AssignmentOrderOriginalSafeLogObserver {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function record(string $event, array $safeFields): void { $this->owner->trip('safe_log'); $this->owner->safeLogs[]=['event'=>$event,'safeFields'=>$safeFields]; }
        };
        $delivery = new class($owner) implements AssignmentOrderOriginalResultDeliveryObserver {
            public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function afterCommitBeforeReturn(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult $result): void
            {
                $this->owner->deliveryCalls++;
                $this->owner->trip('delivery');
                $this->owner->repositoryTrace[]='delivery:'.($this->owner->leaseHeld?'held':'released');
                if ($this->owner->deliveryThrows) throw new \RuntimeException('verifier response loss detail must not escape through domain result');
            }
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
        $this->owner->trip('fault_injector');
        $this->owner->trip('storage_observer');
        $this->owner->trip('stage_begin');
        $this->owner->storageEvents[] = 'STAGE_BEGIN';
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
        $this->owner->trip('fault_injector'); $this->owner->trip('storage_observer');
        $this->owner->trip('stage_write');
        $this->owner->storageEvents[] = 'STAGE_WRITE:' . strlen($chunk);
        if ($this->owner->storageFault === 'write') return AssignmentOrderOriginalStorageStatus::FAILED;
        $this->bytes .= $chunk;
        return AssignmentOrderOriginalStorageStatus::OK;
    }
    public function completedBytesForInspection(): string
    {
        $this->owner->trip('fault_injector'); $this->owner->trip('storage_observer');
        $this->owner->trip('stage_completed');
        $this->owner->storageEvents[] = 'STAGE_DONE';
        return $this->bytes;
    }
    public function finalize(string $sha256, int $byteSize): AssignmentOrderOriginalStorageOutcome
    {
        $this->owner->trip('fault_injector'); $this->owner->trip('storage_observer');
        $this->owner->trip('stage_finalize');
        $this->owner->storageEvents[] = 'FINALIZE_BEGIN';
        if ($this->owner->storageFault === 'finalize') {
            return new class implements AssignmentOrderOriginalStorageOutcome {
                public function status(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::FAILED; }
                public function lease(): ?AssignmentOrderOriginalPrivateContentLease { return null; }
            };
        }
        $this->owner->storedBytes = $this->bytes;
        $this->owner->leaseHeld = true;
        $this->owner->storageRecoveryOwnsLease = true;
        $this->owner->storageEvents[] = 'FINALIZE_DONE';
        $content = new class($sha256, $byteSize) implements AssignmentOrderOriginalPrivateContent {
            public function __construct(private string $digest, private int $size) {}
            public function opaqueIdentity(): string { return 'private-content-0001'; }
            public function sha256(): string { return $this->digest; }
            public function byteSize(): int { return $this->size; }
        };
        $lease = new class($content, $this->owner) implements AssignmentOrderOriginalPrivateContentLease {
            public function __construct(private AssignmentOrderOriginalPrivateContent $contentValue, private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
            public function status(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
            public function content(): ?AssignmentOrderOriginalPrivateContent { return $this->contentValue; }
            public function release(): AssignmentOrderOriginalStorageStatus
            {
                $this->owner->trip('lease_release');
                $this->owner->leaseReleaseCalls++;
                $this->owner->repositoryTrace[]='release_attempt:'.($this->owner->leaseHeld?'held':'released');
                if ($this->owner->leaseReleaseFault === 'throw') throw new \RuntimeException('secret lease/path detail');
                if ($this->owner->leaseReleaseFault === 'failed') return AssignmentOrderOriginalStorageStatus::FAILED;
                $this->owner->leaseHeld = false;
                $this->owner->storageRecoveryOwnsLease = false;
                return AssignmentOrderOriginalStorageStatus::OK;
            }
        };
        return new class($lease) implements AssignmentOrderOriginalStorageOutcome {
            public function __construct(private AssignmentOrderOriginalPrivateContentLease $leaseValue) {}
            public function status(): AssignmentOrderOriginalStorageStatus { return AssignmentOrderOriginalStorageStatus::OK; }
            public function lease(): ?AssignmentOrderOriginalPrivateContentLease { return $this->leaseValue; }
        };
    }
    public function abort(): AssignmentOrderOriginalStorageStatus
    {
        $this->owner->trip('fault_injector'); $this->owner->trip('storage_observer');
        $this->owner->trip('stage_abort');
        $this->owner->storageEvents[] = 'ABORT_BEGIN';
        $this->bytes = '';
        $this->owner->storageEvents[] = 'ABORT_DONE';
        return AssignmentOrderOriginalStorageStatus::OK;
    }
    public function close(): void { $this->owner->trip('fault_injector'); $this->owner->trip('storage_observer'); $this->owner->trip('stage_close'); $this->owner->storageEvents[] = 'STAGE_CLOSE'; }
}

final class InMemoryAssignmentOrderOriginalInitialRepository implements AssignmentOrderOriginalRepository
{
    public function __construct(private InMemoryAssignmentOrderOriginalInitialEnvironment $owner) {}
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup
    {
        $this->owner->trip('request_lookup');
        $this->owner->requestLookupCalls++;
        $this->owner->repositoryTrace[]='request_lookup:'.($this->owner->leaseHeld?'held':'released');
        if ($this->owner->acceptedCommitCalls > 0 && $this->owner->unknownResolution !== null) {
            if ($this->owner->unknownResolution === 'unavailable') return $this->unavailable();
            if ($this->owner->unknownResolution === 'not_found') return $this->miss();
        }
        return $this->lookup($this->owner->terminalResults[$requestId] ?? null);
    }
    public function findAcceptedFingerprint(string $fingerprint): AssignmentOrderOriginalResultLookup
    {
        $this->owner->trip('fingerprint_lookup');
        $this->owner->repositoryTrace[]='fingerprint_lookup:'.($this->owner->leaseHeld?'held':'released');
        return $this->lookup($this->owner->fingerprintResults[$fingerprint] ?? null);
    }
    private function lookup(?\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult $result): AssignmentOrderOriginalResultLookup
    {
        if ($result === null) return $this->miss();
        return new class($result) implements AssignmentOrderOriginalResultLookup {
            public function __construct(private \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult $stored) {}
            public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::FOUND; }
            public function result(): ?\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult { return $this->stored; }
        };
    }
    private function miss(): AssignmentOrderOriginalResultLookup
    {
        return new class implements AssignmentOrderOriginalResultLookup {
            public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::NOT_FOUND; }
            public function result(): ?\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult { return null; }
        };
    }
    private function unavailable(): AssignmentOrderOriginalResultLookup
    {
        return new class implements AssignmentOrderOriginalResultLookup {
            public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::UNAVAILABLE; }
            public function result(): ?\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult { return null; }
        };
    }
    public function findLineage(string $rootOriginalId): AssignmentOrderOriginalLineageLookup
    {
        $this->owner->trip('lineage_lookup');
        $this->owner->repositoryTrace[]='lineage_lookup:'.($this->owner->leaseHeld?'held':'released');
        $commit = $this->owner->acceptedCommit;
        if ($commit !== null && $commit->rootOriginalId === $rootOriginalId) {
            return new class($commit, $this->owner->acceptedCommits) implements AssignmentOrderOriginalLineageLookup {
                /** @param list<AssignmentOrderOriginalAcceptedCommit> $history */
                public function __construct(private AssignmentOrderOriginalAcceptedCommit $commit, private array $history) {}
                public function status(): AssignmentOrderOriginalLookupStatus { return AssignmentOrderOriginalLookupStatus::FOUND; }
                public function rootOriginalId(): ?string { return $this->commit->rootOriginalId; }
                public function currentRevisionId(): ?string { return $this->commit->newRevisionId; }
                public function currentRevisionNumber(): ?int { return $this->commit->newRevisionNumber; }
                public function compositionIdentity(): ?string { return $this->commit->compositionIdentity; }
                public function compositionSha256(): ?string { return $this->commit->compositionSha256; }
                public function containsRevision(string $revisionId): bool { foreach ($this->history as $item) if ($item->newRevisionId === $revisionId) return true; return false; }
            };
        }
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
        $this->owner->trip('commit_accepted');
        $this->owner->acceptedCommitCalls++;
        $this->owner->repositoryTrace[]='commit:'.($this->owner->leaseHeld?'held':'released');
        if ($this->owner->commitRace === 'identical') {
            $this->store($commit);
            return AssignmentOrderOriginalCommitStatus::CONFLICT;
        }
        if ($this->owner->commitRace === 'different') {
            $winner = new AssignmentOrderOriginalAcceptedCommit(
                '00000000-0000-4000-8000-999999999999', 'different-winner-fingerprint', $commit->mode,
                $commit->installationCaseId, $commit->assignmentOrderId, 19, $commit->rootOriginalId,
                'revision-winner', $commit->newRevisionNumber, $commit->previousRevisionId,
                $commit->expectedCurrentRevisionId, $commit->compositionIdentity, $commit->compositionSha256,
                $commit->documentDate, $commit->uploadedAt, str_repeat('9', 64), $commit->byteSize,
                'private-winner', $commit->correctionReason, $commit->domainEventType,
            );
            $this->store($winner);
            return AssignmentOrderOriginalCommitStatus::CONFLICT;
        }
        if ($this->owner->commitOutcome === AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN) {
            if ($this->owner->unknownResolution === 'found') $this->store($commit);
            return AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;
        }
        if ($this->owner->commitOutcome === AssignmentOrderOriginalCommitStatus::ROLLED_BACK) return AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
        $this->store($commit);
        return AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    private function store(AssignmentOrderOriginalAcceptedCommit $commit): void
    {
        $this->owner->acceptedCommit = $commit;
        $this->owner->acceptedCommits[] = $commit;
        $stored = new InMemoryAssignmentOrderOriginalStoredResult($commit);
        $this->owner->terminalResults[$commit->requestId] = $stored;
        $this->owner->fingerprintResults[$commit->fingerprint] = $stored;
    }
    public function commitAttempt(AssignmentOrderOriginalAttemptCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        $this->owner->trip('commit_attempt');
        $this->owner->repositoryTrace[]='attempt_commit:'.($this->owner->leaseHeld?'held':'released');
        $this->owner->attemptCommits[] = $commit;
        return $this->owner->attemptCommitStatus;
    }
    public function hasCommittedContent(string $opaqueIdentity): AssignmentOrderOriginalReferenceLookup
    {
        $this->owner->trip('reference_lookup');
        throw new \LogicException('Not used by initial upload.');
    }
    public function evidenceCanonicalJson(int $caseId, int $orderId): string { return '{}'; }
}

final class InMemoryAssignmentOrderOriginalStoredResult implements \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult
{
    public function __construct(private AssignmentOrderOriginalAcceptedCommit $commit) {}
    public function status(): \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus { return \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus::ACCEPTED; }
    public function reasonCode(): ?\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReason { return null; }
    public function retryable(): bool { return false; }
    public function requestId(): string { return $this->commit->requestId; }
    public function rootOriginalId(): ?string { return $this->commit->rootOriginalId; }
    public function currentRevisionId(): ?string { return $this->commit->newRevisionId; }
    public function revisionNumber(): ?int { return $this->commit->newRevisionNumber; }
    public function documentDate(): ?string { return $this->commit->documentDate; }
    public function sha256(): ?string { return $this->commit->pdfSha256; }
    public function byteSize(): ?int { return $this->commit->byteSize; }
    public function uploadedAt(): ?string { return $this->commit->uploadedAt; }
}
