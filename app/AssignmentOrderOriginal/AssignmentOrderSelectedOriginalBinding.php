<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Shared production/verification construction; original service remains mutation owner. */
final class AssignmentOrderSelectedOriginalBinding
{
    public static function create(\mysqli $db,AssignmentOrderOriginalProductionConfig $c,
        AssignmentOrderOriginalFreshTerminalReaderFactory $fresh,AssignmentOrderOriginalClock $clock,
        ?AssignmentOrderOriginalPersistenceObserver $observer=null): AssignmentOrderOriginalApplication
    {
        try{$safeLog=AssignmentOrderOriginalOpenedSafeLog::open($c->safeLogFile);}
        catch(\Throwable){throw new AssignmentOrderOriginalProductionConfigurationUnavailable();}
        AssignmentOrderOriginalFileStorage::validateRoot($c->privateStorageRoot);
        if(preg_match('/^[A-Za-z0-9_]{0,25}$/D',$c->tablePrefix)!==1)throw new AssignmentOrderOriginalProductionConfigurationUnavailable();
        require_once __DIR__.'/MariaDbRuntimeRepository.php';
        $faults=new AssignmentOrderOriginalNoFaults();
        return new AssignmentOrderOriginalService(new AssignmentOrderOriginalDependencies(
            new AssignmentOrderOriginalMariaDbAuthorizer($db,$c->tablePrefix),new MariaDbSelectedOriginalCompositionReader($db,$c->tablePrefix),
            $clock,new AssignmentOrderOriginalRandomIds(),new FMonitorPassivePdfInspector(),
            new AssignmentOrderOriginalFileStorage($c->privateStorageRoot,$clock,$faults),
            new AssignmentOrderOriginalMariaDbRepository($db,$c->tablePrefix,null,$observer,true),new AssignmentOrderOriginalNoOpLifecycle(),
            new AssignmentOrderOriginalNoOpStorageObserver(),$faults,$safeLog,new AssignmentOrderOriginalNoOpDelivery(),$fresh,
            new AssignmentOrderOriginalMariaDbAttemptAuditWriter($db,$c->tablePrefix)));
    }
}
