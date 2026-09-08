<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

interface AssignmentOrderRegisteredCompositionReadObserver
{
    public function observe(AssignmentOrderRegisteredCompositionReadPhase $phase): void;
}
