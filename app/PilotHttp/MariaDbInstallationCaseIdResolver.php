<?php

declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use FMonitor2\InspectionEvidence\ProductionInspectionEvidenceConfig;

final class MariaDbInstallationCaseIdResolver
{
    public function __construct(
        private readonly \mysqli $connection,
        private readonly ProductionInspectionEvidenceConfig $config,
    ) {
    }

    public function __invoke(int $objectId): ?int
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT id FROM `{$this->config->processTablePrefix}fm2_installation_cases` "
                . 'WHERE legacy_installation_object_id=? LIMIT 2',
            );
            $statement->bind_param('i', $objectId);
            $statement->execute();
            $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $failure) {
            throw new PilotHttpInfrastructureUnavailable(
                'INSTALLATION_CASE_RESOLUTION_FAILED',
                0,
                $failure,
            );
        }

        if (\count($rows) > 1) {
            throw new PilotHttpInfrastructureUnavailable('AMBIGUOUS_INSTALLATION_CASE');
        }

        return $rows === [] ? null : (int) $rows[0]['id'];
    }
}
