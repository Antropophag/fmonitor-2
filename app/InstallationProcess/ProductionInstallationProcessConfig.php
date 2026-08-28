<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProductionInstallationProcessConfig
{
    public function __construct(
        public readonly string $processTablePrefix,
        public readonly string $legacyTablePrefix,
        public readonly string $artifactStorageRoot,
    ) {
    }
}
