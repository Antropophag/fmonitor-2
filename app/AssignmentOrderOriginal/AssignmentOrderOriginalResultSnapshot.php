<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalDataScalar.php';

/** @internal Copies selected port values into an immutable invocation result. */
final class AssignmentOrderOriginalResultSnapshot
{
    public static function copy(AssignmentOrderOriginalResult $source, string $requestId, bool $forceReplay = false, bool $replayAccepted = false): AssignmentOrderOriginalResult
    {
        $status = $source->status();
        $reason = $source->reasonCode();
        $retryable = $source->retryable();
        $storedRequest = $source->requestId();
        $root = $source->rootOriginalId();
        $revision = $source->currentRevisionId();
        $number = $source->revisionNumber();
        $date = $source->documentDate();
        $sha = $source->sha256();
        $size = $source->byteSize();
        $at = $source->uploadedAt();
        $valid = AssignmentOrderOriginalDataScalar::uuid($storedRequest) && !$retryable
            && ($forceReplay || $storedRequest === $requestId);
        if ($status === AssignmentOrderOriginalStatus::ACCEPTED) {
            $valid = $valid && $reason === null && AssignmentOrderOriginalCommandShape::validId($root)
                && AssignmentOrderOriginalCommandShape::validId($revision) && $number !== null && $number >= 1 && $number <= 4294967295
                && AssignmentOrderOriginalDataScalar::date($date) && AssignmentOrderOriginalDataScalar::hash($sha)
                && $size !== null && $size >= 1 && $size <= 20971520 && AssignmentOrderOriginalDataScalar::utc($at);
        } else {
            $valid = $valid && !$forceReplay && AssignmentOrderOriginalDataScalar::terminalReason($status, $reason)
                && [$root, $revision, $number, $date, $sha, $size, $at] === array_fill(0, 7, null);
        }
        if (!$valid) throw new \RuntimeException('AssignmentOrderOriginalStoredResultUnavailable');
        if ($forceReplay || ($replayAccepted && $status === AssignmentOrderOriginalStatus::ACCEPTED)) {
            $status = AssignmentOrderOriginalStatus::REPLAYED;
            $reason = null;
            $retryable = false;
        }
        return new AssignmentOrderOriginalResultValue(
            $status, $reason, $retryable, $requestId, $root, $revision, $number, $date, $sha, $size, $at,
        );
    }
}
