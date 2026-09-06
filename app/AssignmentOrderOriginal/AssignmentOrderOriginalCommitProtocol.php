<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Selects a commit outcome before the scope releases its lease. */
final class AssignmentOrderOriginalCommitProtocol
{
    private readonly AssignmentOrderOriginalRepository $repository;
    public function __construct(private readonly AssignmentOrderOriginalDependencies $dependencies)
    { $this->repository = $dependencies->repository; }

    public function execute(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition, string $root, string $revision, int $number, string $at, string $sha, int $size, string $content, string $fingerprint, AssignmentOrderOriginalResourceScope $resources): array
    {
        $phase = 'commit_conflict';
        $needsAttempt = false;
        try {
            $result = null;
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
                    $needsAttempt = true;
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
        return [$result, $needsAttempt];
    }

    /** @return array{AssignmentOrderOriginalResult,string} */
    private function recover(SubmitAssignmentOrderOriginalCommand $c): array
    {
        return AssignmentOrderOriginalFreshRecovery::resolve($this->dependencies, $c->requestId);
    }

    private function conflict(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition, string $fingerprint, string $sha): AssignmentOrderOriginalResult
    {
        $winner = $this->repository->findAcceptedFingerprint($fingerprint);
        $status = $winner->status();
        $value = $winner->result();
        if (($status === AssignmentOrderOriginalLookupStatus::FOUND) !== ($value !== null)
            || $status === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) return $this->failure($c);
        if ($value !== null) return AssignmentOrderOriginalResultSnapshot::copy($value, $c->requestId, forceReplay: true);
        if ($c->mode === AssignmentOrderOriginalMode::INITIAL) {
            $line = AssignmentOrderOriginalCandidate::assignment($this->repository, $c);
            return $line->status === AssignmentOrderOriginalLookupStatus::FOUND
                ? $this->terminal($c, AssignmentOrderOriginalReason::INITIAL_ALREADY_EXISTS) : $this->failure($c);
        }
        $line = AssignmentOrderOriginalLineageSnapshot::read($this->repository->findLineage($c->rootOriginalId),
            $c->targetRevisionId, queryRoot: $c->rootOriginalId);
        if ($line->matches($c, $composition) && in_array($c->targetRevisionId, $line->ids, true)
            && $line->current !== $c->expectedCurrentRevisionId)
            return $this->terminal($c, AssignmentOrderOriginalReason::STALE_REVISION);
        return $this->failure($c);
    }

    private function failure(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderOriginalReason $reason = AssignmentOrderOriginalReason::PERSISTENCE_FAILURE): AssignmentOrderOriginalResult
    { return new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::FAILED, $reason, true, $c->requestId); }

    private function terminal(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderOriginalReason $reason): AssignmentOrderOriginalResult
    { return new AssignmentOrderOriginalResultValue($reason === AssignmentOrderOriginalReason::NO_CHANGES ? AssignmentOrderOriginalStatus::REJECTED : AssignmentOrderOriginalStatus::CONFLICT, $reason, false, $c->requestId); }
}
