<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface AssignmentOrderCompositionApplication
{
    public function selectAssignmentOrderComposition(
        SelectAssignmentOrderCompositionCommand $command,
    ): AssignmentOrderCompositionResult;
}
