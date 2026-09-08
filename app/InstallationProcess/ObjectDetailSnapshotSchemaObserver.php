<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
require_once __DIR__ . '/ObjectDetailSnapshotSchemaPhase.php';

interface ObjectDetailSnapshotSchemaObserver
{
    public function observe(ObjectDetailSnapshotSchemaPhase $phase): void;
}
