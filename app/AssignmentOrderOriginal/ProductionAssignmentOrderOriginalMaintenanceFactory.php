<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/MariaDbRuntimeRepository.php';
require_once __DIR__.'/MariaDbOriginalMaintenanceRepository.php';
require_once __DIR__.'/AssignmentOrderOriginalOpenedSafeLog.php';

final class ProductionAssignmentOrderOriginalMaintenanceFactory
{
    public static function create(\mysqli $database, AssignmentOrderOriginalProductionConfig $config,
        AssignmentOrderOriginalMaintenanceAuthorization $authorization): AssignmentOrderOriginalMaintenanceApplication
    { return AssignmentOrderOriginalRealMaintenanceVerificationFactory::create($database,$config,$authorization,new AssignmentOrderOriginalSystemClock(),new AssignmentOrderOriginalNoFaults()); }
}

final class AssignmentOrderOriginalRealMaintenanceVerificationFactory
{
    public static function create(\mysqli $database, AssignmentOrderOriginalProductionConfig $config,
        AssignmentOrderOriginalMaintenanceAuthorization $authorization, AssignmentOrderOriginalClock $clock,
        AssignmentOrderOriginalFaultInjector $faults): AssignmentOrderOriginalMaintenanceApplication
    {
        if (!AssignmentOrderOriginalMaintenanceValues::principal($authorization->systemPrincipalId)
            || $authorization->capability !== 'assignment_order.original.storage.reconcile') throw new \InvalidArgumentException('Invalid maintenance authorization.');
        $safe = null;
        try {
            $safe = AssignmentOrderOriginalOpenedSafeLog::open($config->safeLogFile);
            AssignmentOrderOriginalFileStorage::validateRoot($config->privateStorageRoot);
            AssignmentOrderOriginalPhysicalNames::table($config->tablePrefix,'fm2_assignment_order_original_maintenance_requests');
            return new AssignmentOrderOriginalMaintenanceOwner(new AssignmentOrderOriginalMaintenanceDependencies(
                new AssignmentOrderOriginalConfiguredMaintenanceAuthorizer($authorization),$clock,
                new AssignmentOrderOriginalFileStorage($config->privateStorageRoot,$clock,$faults),
                new AssignmentOrderOriginalMariaDbRepository($database,$config->tablePrefix),
                new AssignmentOrderOriginalMariaDbMaintenanceRepository($database,$config->tablePrefix),
                new AssignmentOrderOriginalNoOpStorageObserver(),$faults,$safe));
        } catch (\Throwable) {
            if ($safe !== null) try { $safe->close(); } catch (\Throwable) {}
            throw new AssignmentOrderOriginalProductionConfigurationUnavailable();
        }
    }
}

final readonly class AssignmentOrderOriginalConfiguredMaintenanceAuthorizer implements AssignmentOrderOriginalMaintenanceAuthorizer
{
    public function __construct(private AssignmentOrderOriginalMaintenanceAuthorization $authorization) {}
    public function authorize(string $systemPrincipalId, string $exactCapability): AssignmentOrderOriginalAuthorizationStatus
    { return $systemPrincipalId === $this->authorization->systemPrincipalId && $exactCapability === $this->authorization->capability
        ? AssignmentOrderOriginalAuthorizationStatus::ALLOWED : AssignmentOrderOriginalAuthorizationStatus::DENIED; }
}
