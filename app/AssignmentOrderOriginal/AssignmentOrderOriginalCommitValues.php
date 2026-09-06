<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalDataScalar.php';

/** @internal Validates passive DTOs before any database or fault-observer call. */
final class AssignmentOrderOriginalCommitValues
{
    public static function accepted(AssignmentOrderOriginalAcceptedCommit $c): bool
    {
        if (!AssignmentOrderOriginalDataScalar::uuid($c->requestId) || $c->installationCaseId < 1 || $c->assignmentOrderId < 1
            || $c->actorUserId < 1 || !AssignmentOrderOriginalCommandShape::validId($c->rootOriginalId)
            || !AssignmentOrderOriginalCommandShape::validId($c->newRevisionId) || $c->newRevisionNumber < 1 || $c->newRevisionNumber > 4294967295
            || !AssignmentOrderOriginalDataScalar::composition($c->compositionIdentity, $c->assignmentOrderId)
            || !AssignmentOrderOriginalDataScalar::hash($c->compositionSha256) || !AssignmentOrderOriginalDataScalar::date($c->documentDate)
            || !AssignmentOrderOriginalDataScalar::utc($c->uploadedAt) || !AssignmentOrderOriginalDataScalar::hash($c->pdfSha256)
            || $c->byteSize < 1 || $c->byteSize > 20971520 || $c->privateContentIdentity !== 'content-sha256-'.$c->pdfSha256) return false;
        if ($c->mode === AssignmentOrderOriginalMode::INITIAL) {
            if ($c->newRevisionNumber !== 1 || $c->previousRevisionId !== null || $c->expectedCurrentRevisionId !== null
                || $c->correctionReason !== null || $c->domainEventType !== 'assignment_order_original_accepted') return false;
        } else {
            if ($c->newRevisionNumber < 2 || !AssignmentOrderOriginalCommandShape::validId($c->previousRevisionId)
                || $c->previousRevisionId !== $c->expectedCurrentRevisionId || $c->newRevisionId === $c->previousRevisionId
                || $c->correctionReason === null || AssignmentOrderOriginalCommandShape::reason($c->correctionReason) !== $c->correctionReason
                || $c->domainEventType !== 'assignment_order_original_corrected') return false;
        }
        $correction = $c->mode === AssignmentOrderOriginalMode::CORRECTION;
        $bytes = '';
        foreach ([$c->mode->value, (string) $c->installationCaseId, (string) $c->assignmentOrderId,
            $correction ? $c->rootOriginalId : '', $correction ? $c->previousRevisionId : '',
            $correction ? $c->expectedCurrentRevisionId : '', $c->documentDate, $c->compositionIdentity,
            $c->compositionSha256, $c->pdfSha256] as $part) $bytes .= pack('N', strlen($part)).$part;
        return $c->fingerprint === hash('sha256', $bytes);
    }

    public static function attempt(AssignmentOrderOriginalAttemptCommit $c): bool
    {
        return AssignmentOrderOriginalDataScalar::uuid($c->requestId) && $c->actorUserId > 0 && $c->installationCaseId > 0
            && $c->assignmentOrderId > 0 && !$c->retryable && AssignmentOrderOriginalDataScalar::utc($c->attemptedAt)
            && AssignmentOrderOriginalDataScalar::terminalReason($c->status, $c->reason);
    }
}
