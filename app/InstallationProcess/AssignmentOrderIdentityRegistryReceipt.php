<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final readonly class AssignmentOrderIdentityRegistryReceipt
{
    public function __construct(public int $formatVersion, public string $legacyMaxId,
        public string $legacyNextId, public string $preservedNextId, public string $legacyRowCount,
        public string $tupleSha256, public string $preparedSha256) {}
}
