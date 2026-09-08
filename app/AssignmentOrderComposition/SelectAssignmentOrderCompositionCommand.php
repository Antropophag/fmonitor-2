<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class SelectAssignmentOrderCompositionCommand
{
    public function __construct(
        public SelectionRequestId $requestId,
        public AssignmentOrderCompositionMode $mode,
        public InstallationObjectId $installationObjectId,
        public UserId $actorUserId,
        public InstallerTabIdList $installerTabIds,
        public ?UserId $controlEngineerUserId,
        public SelectionRevision $expectedSelectionRevision,
    ) {}
}
