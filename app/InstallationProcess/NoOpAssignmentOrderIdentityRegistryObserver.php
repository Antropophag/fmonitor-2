<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class NoOpAssignmentOrderIdentityRegistryObserver implements AssignmentOrderIdentityRegistryObserver
{
    public function observe(AssignmentOrderIdentityRegistryPhase $phase): void {}
}
