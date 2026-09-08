<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

final class NoOpAssignmentOrderRegisteredCompositionReadObserver implements AssignmentOrderRegisteredCompositionReadObserver
{
    public function observe(AssignmentOrderRegisteredCompositionReadPhase $phase): void {}
}
