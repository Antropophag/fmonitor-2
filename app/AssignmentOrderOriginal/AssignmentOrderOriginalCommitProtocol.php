<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Selects a commit outcome before the scope releases its lease. */
final class AssignmentOrderOriginalCommitProtocol
{
    public function __construct(private readonly AssignmentOrderOriginalRepository $repository) {}

    public function execute(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition, string $root, string $revision, int $number, string $at, string $sha, int $size, string $content, string $fingerprint, AssignmentOrderOriginalResourceScope $resources): AssignmentOrderOriginalResult
    {
        $phase = 'commit_conflict';
        try {
            $result = null;
            if ($c->mode === AssignmentOrderOriginalMode::CORRECTION) {
                [$result, $number] = $this->correction($c, $composition, $fingerprint, $sha);
            }
            if ($result === null) {
                $commit = new AssignmentOrderOriginalAcceptedCommit($c->requestId, $fingerprint, $c->mode, $c->installationCaseId, $c->assignmentOrderId, $c->actorUserId, $root, $revision, $number, $c->mode === AssignmentOrderOriginalMode::CORRECTION ? $c->targetRevisionId : null, $c->expectedCurrentRevisionId, (string) $composition->identity, (string) $composition->sha256, $c->documentDate, $at, $sha, $size, $content, AssignmentOrderOriginalCommandShape::reason($c->correctionReason), $c->mode === AssignmentOrderOriginalMode::INITIAL ? 'assignment_order_original_accepted' : 'assignment_order_original_corrected');
                try { $status = $this->repository->commitAccepted($commit); }
                catch (\Throwable) { $status = AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN; }
                if ($status === AssignmentOrderOriginalCommitStatus::COMMITTED) {
                    $result = new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::ACCEPTED, null, false, $c->requestId, $root, $revision, $number, $c->documentDate, $sha, $size, $at);
                    $phase = 'committed';
                } elseif ($status === AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN) {
                    [$result, $phase] = $this->recover($c);
                } elseif ($status === AssignmentOrderOriginalCommitStatus::CONFLICT) {
                    $result = $this->conflict($c, $composition, $fingerprint, $sha);
                } else {
                    $result = $this->failure($c);
                    $phase = 'rolled_back';
                }
            }
        } catch (\Throwable) {
            $result = $this->failure($c);
        }
        $resources->release($phase);
        if ($result->status() === AssignmentOrderOriginalStatus::ACCEPTED) $resources->deliver($result);
        return $result;
    }

    /** @return array{AssignmentOrderOriginalResult,string} */
    private function recover(SubmitAssignmentOrderOriginalCommand $c): array
    {
        try {
            $fresh = $this->repository->findTerminalRequest($c->requestId);
            $status = $fresh->status();
            if ($status === AssignmentOrderOriginalLookupStatus::FOUND) {
                $value = $fresh->result();
                if ($value === null) throw new \RuntimeException();
                return [AssignmentOrderOriginalResultSnapshot::copy($value, $c->requestId), 'unknown_found'];
            }
            if ($status === AssignmentOrderOriginalLookupStatus::NOT_FOUND) return [$this->failure($c), 'unknown_not_found'];
        } catch (\Throwable) {
        }
        return [$this->failure($c, AssignmentOrderOriginalReason::PERSISTENCE_OUTCOME_UNKNOWN), 'unknown_unavailable'];
    }

    /** @return array{?AssignmentOrderOriginalResult,int} */
    private function correction(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition, string $fingerprint, string $sha): array
    {
        $line = $this->repository->findLineage((string) $c->rootOriginalId);
        if ($line->status() === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) return [$this->failure($c), 0];
        if ($line->status() !== AssignmentOrderOriginalLookupStatus::FOUND || $line->rootOriginalId() !== $c->rootOriginalId || $line->compositionIdentity() !== $composition->identity || $line->compositionSha256() !== $composition->sha256)
            return [$this->terminal($c, AssignmentOrderOriginalReason::SEMANTIC_COLLISION), 0];
        if ($line->currentRevisionId() !== $c->expectedCurrentRevisionId) {
            $winner = $this->repository->findAcceptedFingerprint($fingerprint);
            if ($winner->status() === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) return [$this->failure($c), 0];
            if ($winner->status() === AssignmentOrderOriginalLookupStatus::FOUND && ($value = $winner->result()) !== null)
                return [AssignmentOrderOriginalResultSnapshot::copy($value, $c->requestId, forceReplay: true), 0];
            return [$this->terminal($c, AssignmentOrderOriginalReason::STALE_REVISION), 0];
        }
        if (!$line->containsRevision((string) $c->targetRevisionId)) return [$this->terminal($c, AssignmentOrderOriginalReason::TARGET_NOT_FOUND), 0];
        if ($line->currentRevisionId() !== $c->targetRevisionId) return [$this->terminal($c, AssignmentOrderOriginalReason::TARGET_NOT_CURRENT), 0];
        if ($line instanceof AssignmentOrderOriginalCurrentEvidenceLookup && $line->currentDocumentDate() === $c->documentDate && $line->currentPdfSha256() === $sha)
            return [$this->terminal($c, AssignmentOrderOriginalReason::NO_CHANGES), 0];
        return [null, (int) $line->currentRevisionNumber() + 1];
    }

    private function conflict(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition, string $fingerprint, string $sha): AssignmentOrderOriginalResult
    {
        $winner = $this->repository->findAcceptedFingerprint($fingerprint);
        if ($winner->status() === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) return $this->failure($c);
        if ($winner->status() === AssignmentOrderOriginalLookupStatus::FOUND && ($value = $winner->result()) !== null)
            return AssignmentOrderOriginalResultSnapshot::copy($value, $c->requestId, forceReplay: true);
        $line = $c->mode === AssignmentOrderOriginalMode::INITIAL && $this->repository instanceof AssignmentOrderOriginalAssignmentLineageRepository
            ? $this->repository->findLineageForAssignmentOrder($c->installationCaseId, $c->assignmentOrderId)
            : $this->repository->findLineage((string) $c->rootOriginalId);
        if ($line->status() === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) return $this->failure($c);
        if ($c->mode === AssignmentOrderOriginalMode::INITIAL) return $this->terminal($c, AssignmentOrderOriginalReason::INITIAL_ALREADY_EXISTS);
        $reason = AssignmentOrderOriginalReason::SEMANTIC_COLLISION;
        if ($line->status() === AssignmentOrderOriginalLookupStatus::FOUND && $line->rootOriginalId() === $c->rootOriginalId && $line->compositionIdentity() === $composition->identity && $line->compositionSha256() === $composition->sha256) {
            if ($line->currentRevisionId() !== $c->expectedCurrentRevisionId) $reason = AssignmentOrderOriginalReason::STALE_REVISION;
            elseif (!$line->containsRevision((string) $c->targetRevisionId)) $reason = AssignmentOrderOriginalReason::TARGET_NOT_FOUND;
            elseif ($line->currentRevisionId() !== $c->targetRevisionId) $reason = AssignmentOrderOriginalReason::TARGET_NOT_CURRENT;
            elseif ($line instanceof AssignmentOrderOriginalCurrentEvidenceLookup && $line->currentDocumentDate() === $c->documentDate && $line->currentPdfSha256() === $sha) $reason = AssignmentOrderOriginalReason::NO_CHANGES;
        }
        return $this->terminal($c, $reason);
    }

    private function failure(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderOriginalReason $reason = AssignmentOrderOriginalReason::PERSISTENCE_FAILURE): AssignmentOrderOriginalResult
    { return new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::FAILED, $reason, true, $c->requestId); }

    private function terminal(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderOriginalReason $reason): AssignmentOrderOriginalResult
    { return new AssignmentOrderOriginalResultValue($reason === AssignmentOrderOriginalReason::NO_CHANGES ? AssignmentOrderOriginalStatus::REJECTED : AssignmentOrderOriginalStatus::CONFLICT, $reason, false, $c->requestId); }
}
