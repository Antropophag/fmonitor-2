<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
interface AssignmentOrderIdentityRegistryObserver
{
    public function observe(AssignmentOrderIdentityRegistryPhase $phase): void;
}
