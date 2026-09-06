<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

final class ProductionAssignmentOrderOriginalFactory
{
    public static function create(\mysqli $db, AssignmentOrderOriginalProductionConfig $c,
        ?AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders = null): AssignmentOrderOriginalApplication
    {
        try { $safeLog = AssignmentOrderOriginalOpenedSafeLog::open($c->safeLogFile); }
        catch (\Throwable) { throw new AssignmentOrderOriginalProductionConfigurationUnavailable(); }
        AssignmentOrderOriginalFileStorage::validateRoot($c->privateStorageRoot);
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $c->tablePrefix) !== 1) throw new AssignmentOrderOriginalProductionConfigurationUnavailable();
        require_once __DIR__.'/MariaDbRuntimeRepository.php';
        $clock = new AssignmentOrderOriginalSystemClock();
        $faults = new AssignmentOrderOriginalNoFaults();
        return new AssignmentOrderOriginalService(new AssignmentOrderOriginalDependencies(
            new AssignmentOrderOriginalMariaDbAuthorizer($db, $c->tablePrefix), new AssignmentOrderOriginalMariaDbCompositionReader($db, $c->tablePrefix),
            $clock, new AssignmentOrderOriginalRandomIds(), new FMonitorPassivePdfInspector(),
            new AssignmentOrderOriginalFileStorage($c->privateStorageRoot, new AssignmentOrderOriginalSystemClock(), $faults),
            new AssignmentOrderOriginalMariaDbRepository($db, $c->tablePrefix), new AssignmentOrderOriginalNoOpLifecycle(),
            new AssignmentOrderOriginalNoOpStorageObserver(), $faults, $safeLog, new AssignmentOrderOriginalNoOpDelivery(), $freshTerminalReaders));
    }

    public static function createRecoveryReady(\mysqli $db, AssignmentOrderOriginalProductionConfig $c,
        AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders): AssignmentOrderOriginalApplication
    { return self::create($db, $c, $freshTerminalReaders); }
}
