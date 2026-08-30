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
echo json_encode(['ok'=>true,'classification'=>$first['classification'],'grade'=>$first['evidenceGrade'],'projectionHash'=>$first['projectionHash']], JSON_THROW_ON_ERROR), "\n";
