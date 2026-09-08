<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface AssignmentOrderApplication
{
    public function applyAssignmentOrderOriginal(ApplyAssignmentOrderOriginalCommand $command): AssignmentOrderApplicationResult;
}
