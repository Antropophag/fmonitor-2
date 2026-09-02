<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

interface InspectionEvidenceView
{
    public function getItemCompletion(
        int $installationCaseId,
        string $clientOperationId,
    ): ?ItemCompletionEvidence;
}
