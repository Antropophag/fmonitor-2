<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Owns only a lease actually returned by the storage port. */
final class AssignmentOrderOriginalLeaseResources
{
    private ?AssignmentOrderOriginalPrivateContentLease $lease = null;
    private bool $releaseAttempted = false;

    public function __construct(private readonly AssignmentOrderOriginalResourceSignals $signals) {}

    public function capture(AssignmentOrderOriginalStorageOutcome $outcome, string $sha256, int $byteSize): string
    {
        try {
            $status = $outcome->status();
            $this->lease = $outcome->lease();
            if (!in_array($status, [AssignmentOrderOriginalStorageStatus::OK, AssignmentOrderOriginalStorageStatus::ALREADY_PRESENT_VERIFIED], true)
                || $this->lease === null || $this->lease->status() !== AssignmentOrderOriginalStorageStatus::OK) {
                throw new \RuntimeException();
            }
            $content = $this->lease->content();
            if ($content === null) {
                throw new \RuntimeException();
            }
            $identity = $content->opaqueIdentity();
            $digest = $content->sha256();
            $size = $content->byteSize();
            if (!AssignmentOrderOriginalCommandShape::validId($identity)
                || preg_match('/^[0-9a-f]{64}$/D', $digest) !== 1 || !hash_equals($sha256, $digest)
                || $size < 1 || $size !== $byteSize) {
                throw new \RuntimeException();
            }
            return $identity;
        } catch (\Throwable) {
            throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE);
        }
    }

    public function release(string $phase): void
    {
        if ($this->lease === null || $this->releaseAttempted) {
            return;
        }
        $this->releaseAttempted = true;
        try {
            $released = $this->lease->release() === AssignmentOrderOriginalStorageStatus::OK;
        } catch (\Throwable) {
            $released = false;
        }
        if (!$released) {
            $this->signals->diagnostic('ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED', $phase);
        }
    }
}
