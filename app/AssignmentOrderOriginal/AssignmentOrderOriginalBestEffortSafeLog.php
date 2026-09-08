<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** Isolates diagnostic failures at the application dependency boundary. */
final class AssignmentOrderOriginalBestEffortSafeLog implements AssignmentOrderOriginalRequestSafeLogObserver
{
    private bool $contextAvailable = true;

    public function __construct(private readonly AssignmentOrderOriginalSafeLogObserver $observer) {}

    public function useRequest(string $requestId): void
    {
        $this->contextAvailable = true;
        if (!$this->observer instanceof AssignmentOrderOriginalRequestSafeLogObserver) {
            return;
        }
        try {
            $this->observer->useRequest($requestId);
        } catch (\Throwable) {
            // A partially bound observer must not write under a previous request.
            $this->contextAvailable = false;
        }
    }

    public function record(string $event, array $safeFields): void
    {
        if (!$this->contextAvailable) {
            return;
        }
        try {
            $this->observer->record($event, $safeFields);
        } catch (\Throwable) {
            // No retry: subsequent independent diagnostics still get one attempt.
        }
    }
}
