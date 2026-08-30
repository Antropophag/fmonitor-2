<?php

declare(strict_types=1);

final class OtizMigratedEvidenceInputs
{
    public static function forObject(int $objectId, array $reconciliationByObject): array
    {
        $row = $reconciliationByObject[$objectId] ?? null;
        if (!is_array($row)) return ['mode'=>'synthetic_fallback','reconciliationClaim'=>false,'admittedEvidence'=>null,'exclusionReason'=>'NO_MATCHING_IMPORTED_SNAPSHOT'];
        if (in_array('LEGACY_UNASSIGNED_SENTINEL',$row['conflictCodes']??[],true)) return ['mode'=>'synthetic_fallback','reconciliationClaim'=>false,'admittedEvidence'=>null,'exclusionReason'=>'LEGACY_UNASSIGNED_SENTINEL'];
        if ($row['evidenceGrade'] !== 'A' || $row['confidence'] !== 'high' || $row['conflictCodes'] !== []) {
            return ['mode'=>'synthetic_fallback','reconciliationClaim'=>false,'admittedEvidence'=>null,'exclusionReason'=>'EVIDENCE_NOT_CONFIRMED'];
        }
        return ['mode'=>'synthetic_fallback_with_confirmed_evidence','reconciliationClaim'=>false,'admittedEvidence'=>[
            'sourceLabel'=>$row['sourceLabel'], 'sourceLocator'=>$row['sourceLocator'], 'snapshotHash'=>$row['contentSha256'],
            'projectionHash'=>$row['projectionHash'], 'evidenceGrade'=>$row['evidenceGrade'], 'classification'=>$row['classification'],
            'checklistEventCount'=>$row['counts']['checklistEvents'], 'progressMapping'=>$row['progressMapping'], 'attributionObservations'=>$row['attributionObservations'],
            'workforceFacts'=>$row['workforceFacts'],
        ], 'exclusionReason'=>'CALCULATION_OPERAND_MAPPING_NOT_APPROVED'];
    }
}
