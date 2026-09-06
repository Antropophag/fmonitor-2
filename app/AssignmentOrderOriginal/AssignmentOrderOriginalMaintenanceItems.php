<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal One bounded lock/reference/delete attempt per candidate. */
final readonly class AssignmentOrderOriginalMaintenanceItems
{
    public function __construct(private AssignmentOrderOriginalMaintenanceDependencies $d, private AssignmentOrderOriginalBestEffortSafeLog $log) {}

    /** @return array{int,int,int,int} deleted, retained, failed, locked */
    public function process(array $items): array
    {
        $counts = [0,0,0,0];
        foreach ($items as $item) {
            $lock = null;
            try {
                $lock = $this->d->storage->acquireDigestLock($item->opaqueIdentity);
                $status = $lock->status(); $id = $lock->opaqueIdentity();
                if ($id !== $item->opaqueIdentity) throw new \UnexpectedValueException();
                if ($status === AssignmentOrderOriginalStorageStatus::LOCKED) { $counts[1]++; $counts[3]++; continue; }
                if ($status !== AssignmentOrderOriginalStorageStatus::OK) throw new \UnexpectedValueException();
                if ($item->kind === AssignmentOrderOriginalOrphanKind::FINALIZED_CONTENT) {
                    $this->d->faults->before(AssignmentOrderOriginalFaultPoint::ORPHAN_REFERENCE_LOOKUP);
                    $reference = $this->d->references->hasCommittedContent($id);
                    $found = $reference->status(); $referenced = $reference->referenced();
                    if ($found !== AssignmentOrderOriginalLookupStatus::FOUND || !is_bool($referenced)) throw new \UnexpectedValueException();
                    if ($referenced) { $counts[1]++; continue; }
                }
                if ($this->d->storage->deleteLocked($lock) !== AssignmentOrderOriginalStorageStatus::OK) throw new \UnexpectedValueException();
                $counts[0]++;
            } catch (\Throwable) { $counts[2]++; }
            finally {
                if ($lock !== null) try { $lock->release(); }
                catch (\Throwable) { $this->log->record('ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_LOCK_RELEASE_FAILED', ['phase'=>'digest_release']); }
            }
        }
        return $counts;
    }
}
