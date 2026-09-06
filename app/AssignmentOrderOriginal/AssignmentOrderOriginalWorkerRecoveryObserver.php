<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Applies the existing worker's closed recovery-failure script to its fresh reader. */
final class AssignmentOrderOriginalWorkerRecoveryObserver implements AssignmentOrderOriginalPersistenceObserver
{
    private bool $fired = false;
    public function __construct(private readonly ?AssignmentOrderOriginalFaultPoint $target) {}

    public function observe(AssignmentOrderOriginalPersistenceEvent $event): void
    {
        if (!$this->fired && $event === AssignmentOrderOriginalPersistenceEvent::BEFORE_READ_RELEASE
            && in_array($this->target, [AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_UNAVAILABLE,
                AssignmentOrderOriginalFaultPoint::COMMIT_UNKNOWN_UNAVAILABLE_RELEASE_FAILURE], true)) {
            $this->fired = true;
            throw new \RuntimeException('AssignmentOrderOriginalWorkerRecoveryUnavailable');
        }
    }
}
