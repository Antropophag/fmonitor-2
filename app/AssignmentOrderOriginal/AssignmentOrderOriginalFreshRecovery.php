<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Owns one recovery reader until its result has been frozen. */
final class AssignmentOrderOriginalFreshRecovery
{
    /** @return array{AssignmentOrderOriginalResult,string} */
    public static function resolve(AssignmentOrderOriginalDependencies $d, string $requestId, bool $attempt = false): array
    {
        $reader = null;
        try {
            $opened = $d->freshTerminalReaders->open();
            $reader = $opened->reader;
            if ($opened->status !== AssignmentOrderOriginalFreshReaderOpenStatus::OPENED || $reader === null)
                return self::failure($requestId, true);
            $lookup = $reader->findTerminalRequest($requestId);
            $status = $lookup->status();
            $source = $lookup->result();
            if (($status === AssignmentOrderOriginalLookupStatus::FOUND) !== ($source !== null)) return self::failure($requestId, true);
            if ($status === AssignmentOrderOriginalLookupStatus::FOUND)
                return [AssignmentOrderOriginalResultSnapshot::copy($source, $requestId, replayAccepted: $attempt), 'unknown_found'];
            return self::failure($requestId, $status !== AssignmentOrderOriginalLookupStatus::NOT_FOUND);
        } catch (\Throwable) {
            return self::failure($requestId, true);
        } finally {
            if ($reader !== null) {
                try { $closed = $reader->close() === AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED; }
                catch (\Throwable) { $closed = false; }
                if (!$closed) {
                    try { $d->safeLog->record('ASSIGNMENT_ORDER_ORIGINAL_FRESH_READER_CLOSE_FAILED',
                        ['phase' => $attempt ? 'authorized_attempt_recovery' : 'accepted_commit_recovery']); }
                    catch (\Throwable) {}
                }
            }
        }
    }

    private static function failure(string $requestId, bool $unknown): array
    {
        return [new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::FAILED,
            $unknown ? AssignmentOrderOriginalReason::PERSISTENCE_OUTCOME_UNKNOWN : AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,
            true, $requestId), $unknown ? 'unknown_unavailable' : 'unknown_not_found'];
    }
}
