<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectionDependencies
{
    public function __construct(
        public SelectionAuthorizer $authorizer,
        public SelectionDependencyReader $facts,
        public SelectionClock $clock,
        public SelectionTerminalRequestReader $requests,
        public SelectionUnitOfWork $transactions,
        public SelectionFreshTerminalReaderFactory $freshReaders,
        public SelectionAttemptAuditWriter $audits,
        public SelectionTerminalAttemptUnitOfWork $terminalAttempts,
    ) {}
}
