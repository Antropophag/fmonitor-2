<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

interface AssignmentOrderSelectionSchemaObserver
{
    public function observe(AssignmentOrderSelectionSchemaPhase $phase): void;
}
