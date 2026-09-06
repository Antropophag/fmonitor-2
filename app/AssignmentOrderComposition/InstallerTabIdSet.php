<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class InstallerTabIdSet
{
    /** @param list<InstallerTabId> $ascendingUniqueIds */
    public function __construct(public array $ascendingUniqueIds) {}
}
