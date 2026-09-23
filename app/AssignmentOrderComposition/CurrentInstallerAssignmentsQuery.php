<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface CurrentInstallerAssignmentsQuery
{
    public function find(array $installerTabIds): array;
}
