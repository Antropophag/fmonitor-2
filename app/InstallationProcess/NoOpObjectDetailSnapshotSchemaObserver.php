<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
require_once __DIR__ . '/ObjectDetailSnapshotSchemaObserver.php';

final class NoOpObjectDetailSnapshotSchemaObserver implements ObjectDetailSnapshotSchemaObserver
{
    public function observe(ObjectDetailSnapshotSchemaPhase $phase): void {}
}
