<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Copies selected port values into an immutable invocation result. */
final class AssignmentOrderOriginalResultSnapshot
{
    public static function copy(AssignmentOrderOriginalResult $source, string $requestId, bool $forceReplay = false, bool $replayAccepted = false): AssignmentOrderOriginalResult
    {
        $status = $source->status();
        $reason = $source->reasonCode();
        $retryable = $source->retryable();
        if ($forceReplay || ($replayAccepted && $status === AssignmentOrderOriginalStatus::ACCEPTED)) {
            $status = AssignmentOrderOriginalStatus::REPLAYED;
            $reason = null;
            $retryable = false;
        }
        return new AssignmentOrderOriginalResultValue(
            $status, $reason, $retryable, $requestId, $source->rootOriginalId(), $source->currentRevisionId(),
            $source->revisionNumber(), $source->documentDate(), $source->sha256(), $source->byteSize(), $source->uploadedAt(),
        );
    }
}
