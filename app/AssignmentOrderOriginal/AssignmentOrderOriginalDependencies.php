<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalAttemptAuditContracts.php';

final readonly class AssignmentOrderOriginalDependencies
{
    public AssignmentOrderOriginalSafeLogObserver $safeLog;
    public AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders;
    public AssignmentOrderOriginalAttemptAuditWriter $attemptAudits;

    public function __construct(
        public AssignmentOrderOriginalAuthorizer $authorizer,
        public AssignmentOrderCompositionReader $compositions,
        public AssignmentOrderOriginalClock $clock,
        public AssignmentOrderOriginalIdSource $ids,
        public AssignmentOrderOriginalPdfInspector $pdfInspector,
        public AssignmentOrderOriginalPrivateStorage $storage,
        public AssignmentOrderOriginalRepository $repository,
        public AssignmentOrderOriginalLifecycleObserver $lifecycle,
        public AssignmentOrderOriginalStorageObserver $storageObserver,
        public AssignmentOrderOriginalFaultInjector $faults,
        AssignmentOrderOriginalSafeLogObserver $safeLog,
        public AssignmentOrderOriginalResultDeliveryObserver $delivery,
        ?AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders = null,
        ?AssignmentOrderOriginalAttemptAuditWriter $attemptAudits = null,
    ) {
        $this->safeLog = new AssignmentOrderOriginalBestEffortSafeLog($safeLog);
        $this->freshTerminalReaders = $freshTerminalReaders ?? new AssignmentOrderOriginalUnavailableFreshReaders();
        $this->attemptAudits = $attemptAudits ?? new AssignmentOrderOriginalUnavailableAttemptAudits();
    }
}
