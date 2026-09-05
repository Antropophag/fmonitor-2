<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
enum AssignmentOrderIdentityRegistryPhase: string
{
    case LOCK_ACQUIRED='lock_acquired';
    case REGISTRY_CREATED='registry_created';
    case RECEIPTS_CREATED='receipts_created';
    case FRONTIER_READY='frontier_ready';
    case BEFORE_BACKFILL_COMMIT='before_backfill_commit';
    case BACKFILL_COMMITTED='backfill_committed';
}
