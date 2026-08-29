<?php
declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class ProductionInstallationProcessFacts
{
    public function __construct(
        private readonly MariaDbLegacyInstallationObject $legacyObjects,
        private readonly MariaDbWorkforceCatalog $workforce,
        private readonly MariaDbProcessUserDirectory $users,
        private readonly object $renderer,
        private readonly Clock $clock,
    ) {
    }

    public function actorCanPrepareAssignmentOrder(int $id): bool { return $this->users->actorCanPrepareAssignmentOrder($id); }
    public function actorCanConfirmOrderRegistration(int $id): bool { return $this->users->actorCanConfirmOrderRegistration($id); }
    public function actorCanOpenInstallation(int $id): bool { return $this->users->actorCanOpenInstallation($id); }
    public function getInstallationObjectSnapshot(int $id): array { return $this->legacyObjects->getInstallationObjectSnapshot($id); }
    public function findInstallerSnapshot(int|string $id): ?array { return $this->workforce->findInstallerSnapshot($id); }
    public function findCurrentInstallerSnapshot(int|string $id): ?array { return $this->workforce->findInstallerSnapshot($id); }
    public function findEngineerSnapshot(int $id): ?array { return $this->users->findEngineerSnapshot($id); }
    public function renderAssignmentOrder(array $input): array { return $this->renderer->renderAssignmentOrder($input); }
    public function now(): string { return $this->clock->now(); }
}
