<?php

declare(strict_types=1);

use FMonitor2\InspectionEvidence\ItemCompletionEvidence;

/**
 * Independent value projection of every normative public evidence field.
 *
 * @return array{
 *   clientOperationId: string,
 *   installationCaseId: int,
 *   sectionId: int,
 *   itemId: int,
 *   actorUserId: int,
 *   assignedControlEngineerUserIdAtReceipt: ?int,
 *   deviceTime: string,
 *   serverReceivedAt: string,
 *   baseRevision: int,
 *   acceptedRevision: int,
 *   templateId: int,
 *   templateVersion: int,
 *   templateSha256: string,
 *   currentChecklistRevision: ?int,
 *   installerSnapshots: list<array{tabId: int, fullName: string, position: string}>
 * }
 */
function inspectionItemCompletionEvidenceProjection(?ItemCompletionEvidence $evidence): array
{
    if ($evidence === null) {
        throw new TestFailure('Expected public ItemCompletionEvidence, got null.');
    }

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
        'currentChecklistRevision' => property_exists($evidence, 'currentChecklistRevision')
            ? $evidence->currentChecklistRevision
            : null,
        'installerSnapshots' => array_map(
            static fn (object $installer): array => [
                'tabId' => $installer->tabId,
                'fullName' => $installer->fullName,
                'position' => $installer->position,
            ],
            $evidence->installerSnapshots,
        ),
    ];
}
