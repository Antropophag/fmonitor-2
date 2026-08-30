<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/MigratedEvidenceReconciliation.php';
function reconcileCheck(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$payload = ['object' => ['id' => 973, 'regnumber' => '77-00973', 'ordadr_address' => 'Контрольный адрес', 'entrance' => '1', 'ptoactdate' => '2026-08-01'], 'checklistEvents' => [['id' => 1]], 'attributions' => [['id' => 2]]];
$snapshot = ['id' => 5, 'legacy_object_id' => 973, 'source_system' => 'legacy_fmonitor', 'source_locator' => 'fm_maintable+checklist_logs', 'cutoff_at' => '2026-08-30 23:59:59', 'extractor_version' => 'history-import-001-v1', 'content_sha256' => str_repeat('a', 64), 'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
$first = MigratedEvidenceReconciliation::project($snapshot, []); $repeat = MigratedEvidenceReconciliation::project($snapshot, []);
reconcileCheck($first === $repeat, 'same immutable snapshot must produce the same projection');
reconcileCheck([$first['classification'],$first['evidenceGrade'],$first['confidence']] === ['legacy_historical','A','high'], 'complete dated evidence grade');
$quarantined = MigratedEvidenceReconciliation::project($snapshot, [['code'=>'ORPHAN_ATTRIBUTION','diagnostic_json'=>'{}']]);
reconcileCheck($quarantined['evidenceGrade'] === 'Q' && $quarantined['confidence'] === 'low', 'quarantine lowers confidence');
reconcileCheck($quarantined['conflictCodes'] === ['ORPHAN_ATTRIBUTION'] && $quarantined['projectionHash'] !== $first['projectionHash'], 'conflict is visible and hashed');
$sentinelPayload=$payload;$sentinelPayload['attributions']=[['id'=>3,'tab_id'=>'0999999','fio'=>'Не закреплён','ctime'=>'2026-08-01 10:00:00']];$sentinelSnapshot=$snapshot;$sentinelSnapshot['payload_json']=json_encode($sentinelPayload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$sentinel=MigratedEvidenceReconciliation::project($sentinelSnapshot,[],['999999'=>['fio'=>'Sentinel must not project','position'=>'none','employment_status'=>'employed','reconciliation_state'=>'delivered','authority_system'=>'one_c_zup','workforce_source'=>'test','workforce_source_updated_at'=>'2026-08-30T12:00:00+03:00']]);
reconcileCheck($sentinel['conflictCodes']===['LEGACY_UNASSIGNED_SENTINEL']&&$sentinel['evidenceGrade']==='Q'&&$sentinel['confidence']==='low','unassigned sentinel is an explicit blocking conflict');
reconcileCheck($sentinel['attributionObservations']===[]&&$sentinel['workforceFacts']===[],'sentinel never becomes performer or workforce fact');
reconcileCheck($sentinel['progressMapping']['candidateProgressBp']===null&&$sentinel['progressMapping']['eligibleForCalculation']===false&&in_array('LEGACY_UNASSIGNED_SENTINEL',$sentinel['progressMapping']['conflictCodes'],true),'sentinel blocks progress mapping');
echo json_encode(['ok'=>true,'classification'=>$first['classification'],'grade'=>$first['evidenceGrade'],'projectionHash'=>$first['projectionHash']], JSON_THROW_ON_ERROR), "\n";
