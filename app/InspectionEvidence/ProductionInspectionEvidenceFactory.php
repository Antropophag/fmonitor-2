<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class ProductionInspectionEvidenceFactory
{
    public static function create(
        \mysqli $connection,
        ProductionInspectionEvidenceConfig $config,
        ?InspectionEvidenceClock $clock = null,
    ): InspectionEvidenceApplication {
        if (!$connection->set_charset('utf8mb4')) {
            throw new \RuntimeException('Inspection evidence initialization failed.');
        }

        return new InspectionEvidence(new MariaDbInspectionEvidenceEnvironment(
            $connection,
            $config->processTablePrefix,
            $clock ?? new SystemInspectionEvidenceClock(),
        ));
    }

}
