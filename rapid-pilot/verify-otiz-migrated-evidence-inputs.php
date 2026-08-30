<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/OtizMigratedEvidenceInputs.php';
function evidenceCheck(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }

$confirmed = ['legacyObjectId'=>7101,'evidenceGrade'=>'A','confidence'=>'high','conflictCodes'=>[],
    'sourceLabel'=>'Legacy FMonitor · только чтение','sourceLocator'=>'fm_maintable+checklist_logs','contentSha256'=>str_repeat('a',64),
    'projectionHash'=>str_repeat('b',64),'classification'=>'legacy_active','counts'=>['checklistEvents'=>12],
    'attributionObservations'=>[['tabId'=>'1042','observedName'=>'Наблюдаемый монтажник','source'=>'legacy_attribution_log']],
    'workforceFacts'=>[['tabId'=>'1042','employmentStatus'=>'employed','authoritySystem'=>'one_c_zup','source'=>'one_c_zup_via_bitrix']]];
$input = OtizMigratedEvidenceInputs::forObject(7101, [7101=>$confirmed]);
evidenceCheck($input['mode'] === 'synthetic_fallback_with_confirmed_evidence' && $input['reconciliationClaim'] === false, 'confirmed evidence remains distinct from synthetic calculation operands');
evidenceCheck($input['admittedEvidence']['attributionObservations'][0]['source'] === 'legacy_attribution_log', 'attribution remains an observation');
evidenceCheck($input['admittedEvidence']['workforceFacts'][0]['authoritySystem'] === 'one_c_zup', 'employment remains an independent workforce fact');
evidenceCheck($input['exclusionReason'] === 'CALCULATION_OPERAND_MAPPING_NOT_APPROVED', 'unapproved semantic mapping cannot affect calculation');
$conflicted = $confirmed; $conflicted['evidenceGrade']='Q'; $conflicted['confidence']='low'; $conflicted['conflictCodes']=['ORPHAN_ATTRIBUTION'];
$excluded = OtizMigratedEvidenceInputs::forObject(7101, [7101=>$conflicted]);
evidenceCheck($excluded['admittedEvidence'] === null && $excluded['exclusionReason'] === 'EVIDENCE_NOT_CONFIRMED', 'conflicted evidence is excluded automatically');
evidenceCheck(OtizMigratedEvidenceInputs::forObject(9999, [])['exclusionReason'] === 'NO_MATCHING_IMPORTED_SNAPSHOT', 'missing evidence is explicitly synthetic fallback');
echo json_encode(['ok'=>true,'confirmedMode'=>$input['mode'],'conflictedAdmission'=>$excluded['admittedEvidence']], JSON_THROW_ON_ERROR), "\n";
