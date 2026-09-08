<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class NoOpAssignmentOrderSelectionSchemaObserver implements AssignmentOrderSelectionSchemaObserver
{
    public function observe(AssignmentOrderSelectionSchemaPhase $phase): void {}
}
