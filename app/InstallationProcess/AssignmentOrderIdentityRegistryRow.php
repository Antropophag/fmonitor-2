<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final readonly class AssignmentOrderIdentityRegistryRow
{
    public function __construct(public string $id, public string $caseId, public int $version, public string $sourceKind, public string $allocatedAtUtc) {}
}
