<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class InspectionItemCommandPolicy
{
    public function valid(CompleteInspectionItem $command): bool
    {
        if (
            !$this->canonicalUuid($command->clientOperationId)
            || !$this->canonicalUuid($command->deviceInstallationId)
            || !$this->timestampHasExplicitOffset($command->deviceTime)
            || $command->installationCaseId <= 0
            || $command->expectedRevision < 0
            || $command->sectionId <= 0
            || $command->itemId <= 0
            || $command->installerTabIds === []
        ) {
            return false;
        }

        $unique = [];
        foreach ($command->installerTabIds as $tabId) {
            if (!is_int($tabId) || $tabId <= 0 || isset($unique[$tabId])) {
                return false;
            }
            $unique[$tabId] = true;
        }

        return true;
    }

    /** @return list<int> */
    public function normalizedInstallerIds(CompleteInspectionItem $command): array
    {
        $installerIds = $command->installerTabIds;
        sort($installerIds, SORT_NUMERIC);
        return $installerIds;
    }

    /** @param list<int> $installerTabIds */
    public function normalizedPayload(
        CompleteInspectionItem $command,
        array $installerTabIds,
    ): string {
        return json_encode([
            'actorUserId' => $command->actorUserId,
            'installationCaseId' => $command->installationCaseId,
            'clientOperationId' => $command->clientOperationId,
            'deviceInstallationId' => $command->deviceInstallationId,
            'deviceTime' => $command->deviceTime,
            'expectedRevision' => $command->expectedRevision,
            'sectionId' => $command->sectionId,
            'itemId' => $command->itemId,
            'installerTabIds' => $installerTabIds,
        ], JSON_THROW_ON_ERROR);
    }

    private function canonicalUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D',
            $value,
        ) === 1;
    }

    private function timestampHasExplicitOffset(string $value): bool
    {
        if (preg_match('/T.*(?:Z|[+-][0-9]{2}:[0-9]{2})$/D', $value) !== 1) {
            return false;
        }

        try {
            new \DateTimeImmutable($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
