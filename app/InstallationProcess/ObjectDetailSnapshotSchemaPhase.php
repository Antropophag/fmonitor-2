<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

enum ObjectDetailSnapshotSchemaPhase: string
{
    case LOCK_ACQUIRED = 'lock_acquired';
    case DETAILS_CREATED = 'details_created';
    case QUARANTINE_CREATED = 'quarantine_created';
}
