<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionDependencyReader
{
    public function findCaseByObject(InstallationObjectId $id): SelectionCaseLookup;
    public function findInstallers(InstallerTabIdSet $ids,SelectionInstant $at): SelectionInstallerBatchLookup;
    public function findEngineer(UserId $id,SelectionInstant $at): SelectionEngineerLookup;
}
