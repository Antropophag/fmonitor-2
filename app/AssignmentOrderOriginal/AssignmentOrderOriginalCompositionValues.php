<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Keeps protocol corruption distinct from invalid business composition. */
final class AssignmentOrderOriginalCompositionValues
{
    public static function validate(AssignmentOrderCompositionSnapshot $value, int $caseId, int $orderId): void
    {
        if ($value->installationCaseId !== $caseId || $value->assignmentOrderId !== $orderId
            || ($value->status !== AssignmentOrderCompositionLookupStatus::FOUND
                && [$value->identity, $value->sha256, $value->installerIds, $value->controlEngineerUserId] !== [null, null, [], null]))
            throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
        if ($value->status === AssignmentOrderCompositionLookupStatus::UNAVAILABLE)
            throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
        if ($value->status === AssignmentOrderCompositionLookupStatus::NOT_FOUND)
            throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::ORDER_NOT_FOUND);
        if (!self::content($value)) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::INVALID_COMPOSITION);
    }

    public static function content(AssignmentOrderCompositionSnapshot $value): bool
    {
        if (!AssignmentOrderOriginalDataScalar::composition($value->identity, $value->assignmentOrderId)
            || ($value->controlEngineerUserId ?? 0) < 1 || !array_is_list($value->installerIds) || $value->installerIds === []) return false;
        $previous = 0;
        foreach ($value->installerIds as $id) {
            if (!is_int($id) || $id <= $previous) return false;
            $previous = $id;
        }
        $canonical = json_encode(['caseId' => $value->installationCaseId, 'compositionIdentity' => $value->identity,
            'engineerUserId' => $value->controlEngineerUserId, 'installers' => $value->installerIds,
            'orderId' => $value->assignmentOrderId], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $value->sha256 === hash('sha256', $canonical);
    }
}
