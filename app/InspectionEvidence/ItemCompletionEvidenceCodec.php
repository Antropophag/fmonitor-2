<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class ItemCompletionEvidenceCodec
{
    /** @return array<string, mixed> */
    public function toStored(ItemCompletionEvidence $evidence, string $normalizedPayload): array
    {
        return [
            'clientOperationId' => $evidence->clientOperationId,
            'installationCaseId' => $evidence->installationCaseId,
            'sectionId' => $evidence->sectionId,
            'itemId' => $evidence->itemId,
            'actorUserId' => $evidence->actorUserId,
            'assignedControlEngineerUserIdAtReceipt' => $evidence->assignedControlEngineerUserIdAtReceipt,
            'deviceTime' => $evidence->deviceTime,
            'serverReceivedAt' => $evidence->serverReceivedAt,
            'baseRevision' => $evidence->baseRevision,
            'acceptedRevision' => $evidence->acceptedRevision,
            'templateId' => $evidence->templateId,
            'templateVersion' => $evidence->templateVersion,
            'templateSha256' => $evidence->templateSha256,
            'currentChecklistRevision' => $evidence->currentChecklistRevision,
            'installerSnapshots' => $evidence->installerSnapshots,
            'normalizedPayload' => $normalizedPayload,
        ];
    }

    /** @param array<string, mixed> $stored */
    public function fromStored(array $stored): ItemCompletionEvidence
    {
        return new ItemCompletionEvidence(
            $stored['clientOperationId'],
            $stored['installationCaseId'],
            $stored['sectionId'],
            $stored['itemId'],
            $stored['actorUserId'],
            $stored['assignedControlEngineerUserIdAtReceipt'],
            $stored['deviceTime'],
            $stored['serverReceivedAt'],
            $stored['baseRevision'],
            $stored['acceptedRevision'],
            $stored['templateId'],
            $stored['templateVersion'],
            $stored['templateSha256'],
            $stored['currentChecklistRevision'],
            $stored['installerSnapshots'],
        );
    }
}
