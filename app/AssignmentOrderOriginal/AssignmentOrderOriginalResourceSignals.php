<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Observation never owns the primitive or its cleanup. */
final class AssignmentOrderOriginalResourceSignals
{
    public function __construct(private readonly AssignmentOrderOriginalDependencies $dependencies) {}

    public function storage(AssignmentOrderOriginalStorageEvent $event, ?string $identity = null, bool $cleanup = false): bool
    {
        try {
            $this->dependencies->storageObserver->observe($event, $identity);
            return true;
        } catch (\Throwable) {
            if (!$cleanup) {
                throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE);
            }
            return false;
        }
    }

    public function lifecycle(AssignmentOrderOriginalLifecycleEvent $event): void
    {
        try {
            $this->dependencies->lifecycle->observe($event);
        } catch (\Throwable) {
            throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
        }
    }

    public function diagnostic(string $event, string $phase): void
    {
        $this->dependencies->safeLog->record($event, ['phase' => $phase]);
    }

    public function deliver(AssignmentOrderOriginalResult $result): void
    {
        try {
            $this->dependencies->lifecycle->observe(AssignmentOrderOriginalLifecycleEvent::AFTER_COMMIT_BEFORE_RETURN);
            $this->dependencies->delivery->afterCommitBeforeReturn($result);
        } catch (\Throwable) {
            throw new AssignmentOrderOriginalResponseDeliveryLost();
        }
    }
}
