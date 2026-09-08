<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

final readonly class AssignmentOrderOriginalOrphanCandidate
{
    public function __construct(
        public AssignmentOrderOriginalOrphanKind $kind,
        public string $opaqueIdentity,
        public ?string $sha256,
        public int $byteSize,
        public string $createdOrFinalizedAtUtc,
    ) {}
}

enum AssignmentOrderOriginalOrphanKind: string
{
    case ABANDONED_STAGE = 'abandoned_stage';
    case FINALIZED_CONTENT = 'finalized_content';
}

interface AssignmentOrderOriginalOrphanPage
{
    public function status(): AssignmentOrderOriginalStorageStatus;
    /** @return list<AssignmentOrderOriginalOrphanCandidate> */
    public function candidates(): array;
    public function nextCursor(): ?string;
}

interface AssignmentOrderOriginalDigestLock
{
    public function status(): AssignmentOrderOriginalStorageStatus;
    public function opaqueIdentity(): string;
    public function release(): void;
}

interface AssignmentOrderOriginalMaintenanceResult
{
    public function status(): AssignmentOrderOriginalMaintenanceStatus;
    public function reason(): ?AssignmentOrderOriginalMaintenanceReason;
    public function retryable(): bool;
    public function scanned(): int;
    public function deleted(): int;
    public function retained(): int;
    public function failed(): int;
    public function nextCursor(): ?string;
}

interface AssignmentOrderOriginalMaintenanceApplication
{
    public function reconcileAssignmentOrderOriginalPrivateOrphans(
        ReconcileAssignmentOrderOriginalPrivateOrphansCommand $command,
    ): AssignmentOrderOriginalMaintenanceResult;
}

interface AssignmentOrderOriginalMaintenanceAuthorizer
{
    public function authorize(
        string $systemPrincipalId,
        string $exactCapability,
    ): AssignmentOrderOriginalAuthorizationStatus;
}

final readonly class AssignmentOrderOriginalMaintenanceCommit
{
    public function __construct(
        public string $requestId,
        public string $systemPrincipalId,
        public AssignmentOrderOriginalMaintenanceStatus $status,
        public ?AssignmentOrderOriginalMaintenanceReason $reason,
        public bool $retryable,
        public int $scanned,
        public int $deleted,
        public int $retained,
        public int $failed,
        public ?string $nextCursor,
        public string $attemptedAtUtc,
    ) {}
}

interface AssignmentOrderOriginalMaintenanceResultLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus;
    public function result(): ?AssignmentOrderOriginalMaintenanceResult;
}

interface AssignmentOrderOriginalMaintenanceRepository
{
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalMaintenanceResultLookup;
    public function commitResultAndAudit(
        AssignmentOrderOriginalMaintenanceCommit $commit,
    ): AssignmentOrderOriginalCommitStatus;
}

final readonly class AssignmentOrderOriginalMaintenanceDependencies
{
    public function __construct(
        public AssignmentOrderOriginalMaintenanceAuthorizer $authorizer,
        public AssignmentOrderOriginalClock $clock,
        public AssignmentOrderOriginalPrivateStorage $storage,
        public AssignmentOrderOriginalRepository $references,
        public AssignmentOrderOriginalMaintenanceRepository $requests,
        public AssignmentOrderOriginalStorageObserver $storageObserver,
        public AssignmentOrderOriginalFaultInjector $faults,
        public AssignmentOrderOriginalSafeLogObserver $safeLog,
    ) {}
}

