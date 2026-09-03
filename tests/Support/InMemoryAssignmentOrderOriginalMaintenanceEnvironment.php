<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalClock;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalCommitStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDigestLock;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultInjector;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceAuthorizer;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceCommit;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceDependencies;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceReason;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceRepository;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceResult;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceResultLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOrphanCandidate;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalOrphanPage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateStage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPrivateStorage;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReferenceLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalRepository;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResultLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSafeLogObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageEvent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStorageStatus;

final class InMemoryAssignmentOrderOriginalMaintenanceEnvironment
{
    public string $allowedPrincipal = 'system:original-orphan-reconciler';
    public bool $authorizationAvailable = true;
    public AssignmentOrderOriginalLookupStatus $requestLookupStatus = AssignmentOrderOriginalLookupStatus::NOT_FOUND;
    public AssignmentOrderOriginalCommitStatus $commitStatus = AssignmentOrderOriginalCommitStatus::COMMITTED;
    public AssignmentOrderOriginalStorageStatus $pageStatus = AssignmentOrderOriginalStorageStatus::OK;
    public ?string $nextCursor = null;
    /** @var list<AssignmentOrderOriginalOrphanCandidate> */
    public array $candidates = [];
    /** @var array<string, AssignmentOrderOriginalStorageStatus> */
    public array $locks = [];
    /** @var array<string, AssignmentOrderOriginalLookupStatus> */
    public array $referenceStatuses = [];
    /** @var array<string, bool> */
    public array $references = [];
    /** @var array<string, AssignmentOrderOriginalStorageStatus> */
    public array $deletes = [];
    /** @var array<string, AssignmentOrderOriginalMaintenanceResult> */
    public array $terminal = [];
    /** @var list<AssignmentOrderOriginalMaintenanceCommit> */
    public array $commits = [];
    /** @var list<string> */
    public array $calls = [];
    /** @var array<string, int> */
    public array $physicalDeletes = [];

    public function dependencies(): AssignmentOrderOriginalMaintenanceDependencies
    {
        $owner = $this;
        return new AssignmentOrderOriginalMaintenanceDependencies(
            new class($owner) implements AssignmentOrderOriginalMaintenanceAuthorizer {
                public function __construct(private InMemoryAssignmentOrderOriginalMaintenanceEnvironment $owner) {}
                public function authorize(string $systemPrincipalId, string $exactCapability): AssignmentOrderOriginalAuthorizationStatus
                {
                    $this->owner->calls[] = "authorize:$systemPrincipalId:$exactCapability";
                    if (!$this->owner->authorizationAvailable) return AssignmentOrderOriginalAuthorizationStatus::UNAVAILABLE;
                    return $systemPrincipalId === $this->owner->allowedPrincipal
                        && $exactCapability === 'assignment_order.original.storage.reconcile'
                        ? AssignmentOrderOriginalAuthorizationStatus::ALLOWED
                        : AssignmentOrderOriginalAuthorizationStatus::DENIED;
                }
            },
            new class($owner) implements AssignmentOrderOriginalClock {
                public function __construct(private InMemoryAssignmentOrderOriginalMaintenanceEnvironment $owner) {}
                public function nowUtc(): string { $this->owner->calls[] = 'clock'; return '2026-09-02T09:15:30Z'; }
            },
            new InMemoryAssignmentOrderOriginalMaintenanceStorage($owner),
            new InMemoryAssignmentOrderOriginalMaintenanceReferences($owner),
            new InMemoryAssignmentOrderOriginalMaintenanceRequests($owner),
            new class implements AssignmentOrderOriginalStorageObserver {
                public function observe(AssignmentOrderOriginalStorageEvent $event, ?string $opaqueIdentity): void {}
            },
            new class implements AssignmentOrderOriginalFaultInjector {
                public function before(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalFaultPoint $point): void {}
            },
            new class implements AssignmentOrderOriginalSafeLogObserver {
                public function record(string $event, array $safeFields): void {}
            },
        );
    }
}

final class InMemoryAssignmentOrderOriginalMaintenanceStorage implements AssignmentOrderOriginalPrivateStorage
{
    public function __construct(private InMemoryAssignmentOrderOriginalMaintenanceEnvironment $owner) {}
    public function beginStage(): AssignmentOrderOriginalPrivateStage { throw new \LogicException('Maintenance must not stage.'); }
    public function listOrphans(string $cutoffUtc, int $limit, ?string $cursor): AssignmentOrderOriginalOrphanPage
    {
        $this->owner->calls[] = 'list:' . $cutoffUtc . ':' . $limit . ':' . ($cursor ?? 'null');
        return new InMemoryAssignmentOrderOriginalOrphanPage($this->owner->pageStatus, $this->owner->candidates, $this->owner->nextCursor);
    }
    public function acquireDigestLock(string $opaqueIdentity): AssignmentOrderOriginalDigestLock
    {
        $this->owner->calls[] = 'lock:' . $opaqueIdentity;
        return new InMemoryAssignmentOrderOriginalDigestLock($opaqueIdentity, $this->owner->locks[$opaqueIdentity] ?? AssignmentOrderOriginalStorageStatus::OK, $this->owner->calls);
    }
    public function deleteLocked(AssignmentOrderOriginalDigestLock $lock): AssignmentOrderOriginalStorageStatus
    {
        $identity = $lock->opaqueIdentity();
        $this->owner->calls[] = 'delete:' . $identity;
        $status = $this->owner->deletes[$identity] ?? AssignmentOrderOriginalStorageStatus::OK;
        if ($status === AssignmentOrderOriginalStorageStatus::OK) {
            $this->owner->physicalDeletes[$identity] = ($this->owner->physicalDeletes[$identity] ?? 0) + 1;
            $this->owner->deletes[$identity] = AssignmentOrderOriginalStorageStatus::ALREADY_PRESENT_VERIFIED;
        }
        return $status;
    }
    public function inventoryCanonicalJson(): string { return '{}'; }
}

final readonly class InMemoryAssignmentOrderOriginalOrphanPage implements AssignmentOrderOriginalOrphanPage
{
    /** @param list<AssignmentOrderOriginalOrphanCandidate> $items */
    public function __construct(private AssignmentOrderOriginalStorageStatus $pageStatus, private array $items, private ?string $cursor) {}
    public function status(): AssignmentOrderOriginalStorageStatus { return $this->pageStatus; }
    public function candidates(): array { return $this->items; }
    public function nextCursor(): ?string { return $this->cursor; }
}

final class InMemoryAssignmentOrderOriginalDigestLock implements AssignmentOrderOriginalDigestLock
{
    /** @param list<string> $calls */
    public function __construct(private string $identity, private AssignmentOrderOriginalStorageStatus $lockStatus, private array &$calls) {}
    public function status(): AssignmentOrderOriginalStorageStatus { return $this->lockStatus; }
    public function opaqueIdentity(): string { return $this->identity; }
    public function release(): void { $this->calls[] = 'unlock:' . $this->identity; }
}

final class InMemoryAssignmentOrderOriginalMaintenanceReferences implements AssignmentOrderOriginalRepository
{
    public function __construct(private InMemoryAssignmentOrderOriginalMaintenanceEnvironment $owner) {}
    public function hasCommittedContent(string $opaqueIdentity): AssignmentOrderOriginalReferenceLookup
    {
        $this->owner->calls[] = 'reference:' . $opaqueIdentity;
        return new InMemoryAssignmentOrderOriginalReferenceLookup(
            $this->owner->referenceStatuses[$opaqueIdentity] ?? AssignmentOrderOriginalLookupStatus::FOUND,
            $this->owner->references[$opaqueIdentity] ?? false,
        );
    }
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup { throw new \LogicException('Wrong repository seam.'); }
    public function findAcceptedFingerprint(string $fingerprint): AssignmentOrderOriginalResultLookup { throw new \LogicException('Wrong repository seam.'); }
    public function findLineage(string $rootOriginalId): \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLineageLookup { throw new \LogicException('Wrong repository seam.'); }
    public function commitAccepted(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAcceptedCommit $commit): AssignmentOrderOriginalCommitStatus { throw new \LogicException('Maintenance must not commit originals.'); }
    public function commitAttempt(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAttemptCommit $commit): AssignmentOrderOriginalCommitStatus { throw new \LogicException('Maintenance owns a separate audit transaction.'); }
    public function evidenceCanonicalJson(int $caseId, int $orderId): string { return '{}'; }
}

final readonly class InMemoryAssignmentOrderOriginalReferenceLookup implements AssignmentOrderOriginalReferenceLookup
{
    public function __construct(private AssignmentOrderOriginalLookupStatus $lookupStatus, private ?bool $isReferenced) {}
    public function status(): AssignmentOrderOriginalLookupStatus { return $this->lookupStatus; }
    public function referenced(): ?bool { return $this->lookupStatus === AssignmentOrderOriginalLookupStatus::FOUND ? $this->isReferenced : null; }
}

final class InMemoryAssignmentOrderOriginalMaintenanceRequests implements AssignmentOrderOriginalMaintenanceRepository
{
    public function __construct(private InMemoryAssignmentOrderOriginalMaintenanceEnvironment $owner) {}
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalMaintenanceResultLookup
    {
        $this->owner->calls[] = 'request:' . $requestId;
        return new InMemoryAssignmentOrderOriginalMaintenanceResultLookup($this->owner->requestLookupStatus, $this->owner->terminal[$requestId] ?? null);
    }
    public function commitResultAndAudit(AssignmentOrderOriginalMaintenanceCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        $this->owner->calls[] = 'commit:' . $commit->requestId;
        $this->owner->commits[] = $commit;
        if ($this->owner->commitStatus === AssignmentOrderOriginalCommitStatus::COMMITTED) {
            $this->owner->terminal[$commit->requestId] = new InMemoryAssignmentOrderOriginalMaintenanceResult($commit);
            $this->owner->requestLookupStatus = AssignmentOrderOriginalLookupStatus::FOUND;
        }
        return $this->owner->commitStatus;
    }
}

final readonly class InMemoryAssignmentOrderOriginalMaintenanceResultLookup implements AssignmentOrderOriginalMaintenanceResultLookup
{
    public function __construct(private AssignmentOrderOriginalLookupStatus $lookupStatus, private ?AssignmentOrderOriginalMaintenanceResult $stored) {}
    public function status(): AssignmentOrderOriginalLookupStatus { return $this->lookupStatus; }
    public function result(): ?AssignmentOrderOriginalMaintenanceResult { return $this->stored; }
}

final readonly class InMemoryAssignmentOrderOriginalMaintenanceResult implements AssignmentOrderOriginalMaintenanceResult
{
    public function __construct(private AssignmentOrderOriginalMaintenanceCommit $commit) {}
    public function status(): AssignmentOrderOriginalMaintenanceStatus { return $this->commit->status; }
    public function reason(): ?AssignmentOrderOriginalMaintenanceReason { return $this->commit->reason; }
    public function retryable(): bool { return $this->commit->retryable; }
    public function scanned(): int { return $this->commit->scanned; }
    public function deleted(): int { return $this->commit->deleted; }
    public function retained(): int { return $this->commit->retained; }
    public function failed(): int { return $this->commit->failed; }
    public function nextCursor(): ?string { return $this->commit->nextCursor; }
}
