<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalLineageSnapshot.php';

/** @internal Resolves normal step11 before ID allocation or private finalization. */
final class AssignmentOrderOriginalCandidate
{
    public static function root(AssignmentOrderOriginalRepository $repository, SubmitAssignmentOrderOriginalCommand $c,
        AssignmentOrderCompositionSnapshot $composition): AssignmentOrderOriginalLineageSnapshot
    {
        $line = AssignmentOrderOriginalLineageSnapshot::read($repository->findLineage($c->rootOriginalId),
            $c->targetRevisionId, queryRoot: $c->rootOriginalId);
        if ($line->status === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) self::fail();
        if (!$line->matches($c, $composition)) self::conflict(AssignmentOrderOriginalReason::SEMANTIC_COLLISION);
        return $line;
    }

    public static function assignment(AssignmentOrderOriginalRepository $repository, SubmitAssignmentOrderOriginalCommand $c): AssignmentOrderOriginalLineageSnapshot
    {
        if (!$repository instanceof AssignmentOrderOriginalAssignmentLineageRepository) self::fail();
        return AssignmentOrderOriginalLineageSnapshot::read($repository->findLineageForAssignmentOrder($c->installationCaseId, $c->assignmentOrderId),
            queryCase: $c->installationCaseId, queryOrder: $c->assignmentOrderId);
    }

    public static function number(AssignmentOrderOriginalRepository $repository, SubmitAssignmentOrderOriginalCommand $c,
        AssignmentOrderCompositionSnapshot $composition, string $sha, string $fingerprint): int|AssignmentOrderOriginalResult
    {
        if ($c->mode === AssignmentOrderOriginalMode::INITIAL) {
            $line = self::assignment($repository, $c);
            if ($line->status === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) self::fail();
            if ($line->status === AssignmentOrderOriginalLookupStatus::FOUND) self::conflict(AssignmentOrderOriginalReason::INITIAL_ALREADY_EXISTS);
            return 1;
        }
        $line = self::root($repository, $c, $composition);
        if ($line->current !== $c->expectedCurrentRevisionId) {
            $winner = $repository->findAcceptedFingerprint($fingerprint);
            $status = $winner->status();
            $result = $winner->result();
            if (($status === AssignmentOrderOriginalLookupStatus::FOUND) !== ($result !== null)
                || $status === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) self::fail();
            if ($result !== null) return AssignmentOrderOriginalResultSnapshot::copy($result, $c->requestId, forceReplay: true);
            self::conflict(AssignmentOrderOriginalReason::STALE_REVISION);
        }
        if (!in_array($c->targetRevisionId, $line->ids, true)) {
            if (!$repository instanceof AssignmentOrderOriginalRevisionLineageRepository) self::fail();
            $owner = AssignmentOrderOriginalLineageSnapshot::read($repository->findLineageForRevision($c->targetRevisionId), queryRevision: $c->targetRevisionId);
            if ($owner->status === AssignmentOrderOriginalLookupStatus::NOT_FOUND) self::conflict(AssignmentOrderOriginalReason::TARGET_NOT_FOUND);
            if ($owner->status === AssignmentOrderOriginalLookupStatus::UNAVAILABLE || $owner->root === $line->root) self::fail();
            self::conflict(AssignmentOrderOriginalReason::SEMANTIC_COLLISION);
        }
        if ($line->current !== $c->targetRevisionId) self::conflict(AssignmentOrderOriginalReason::TARGET_NOT_CURRENT);
        if ($line->date === $c->documentDate && $line->sha === $sha)
            throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::NO_CHANGES);
        if ($line->number >= 4294967295) self::fail();
        return $line->number + 1;
    }

    private static function fail(): never
    { throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE); }

    private static function conflict(AssignmentOrderOriginalReason $reason): never
    { throw AssignmentOrderOriginalSubmissionFailure::conflict($reason); }
}
