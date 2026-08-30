<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/LegacyObjectClassification.php';

function exact(mixed $expected, mixed $actual, string $message): void { if ($expected !== $actual) throw new RuntimeException($message . '\n' . var_export($actual, true)); }

$base = ['ordadr_address' => 'redacted-address', 'entrance' => '2', 'regnumber' => 'redacted-registration',
    'factworkstartdate' => null, 'ptoactdate' => null, 'object_status' => null,
    'checklist_event_count' => 0, 'attribution_count' => 0, 'fact_percent' => 0, 'workstarted' => 0];
$examples = [
    $base + ['id' => 101],
    array_replace($base, ['id' => 102, 'factworkstartdate' => '2026-08-10 09:00:00', 'checklist_event_count' => 7, 'attribution_count' => 2]),
    array_replace($base, ['id' => 103, 'factworkstartdate' => '2026-05-01', 'ptoactdate' => '2026-08-02', 'object_status' => 259, 'checklist_event_count' => 19]),
    array_replace($base, ['id' => 104, 'factworkstartdate' => '2026-02-30']),
    array_replace($base, ['id' => 105, 'ptoactdate' => '2026-08-12']),
];
$results = array_map(LegacyObjectClassification::classify(...), $examples);
exact(['native_candidate','legacy_active','legacy_historical','native_candidate','legacy_historical'], array_column($results, 'category'), 'representative categories');
exact(['NO_ACTUAL_START_OR_PROGRESS_EVIDENCE'], $results[0]['reasonCodes'], 'planned-only object is native');
exact(['ACTUAL_START_RECORDED','CHECKLIST_HISTORY_PRESENT','WORK_ATTRIBUTION_HISTORY_PRESENT'], $results[1]['reasonCodes'], 'active evidence reasons');
exact(['PTO_ACT_RECORDED','LEGACY_FINISHED_STATUS'], $results[2]['reasonCodes'], 'historical precedence and reasons');
exact(['MALFORMED_FACTWORKSTARTDATE'], $results[3]['quarantineCodes'], 'malformed evidence quarantined without inventing progress');
exact(['COMPLETION_WITHOUT_START_EVIDENCE'], $results[4]['quarantineCodes'], 'incomplete historical chain quarantined');
$profile = LegacyObjectProfile::aggregate($examples);
exact(['native_candidate' => 2, 'legacy_active' => 1, 'legacy_historical' => 2], $profile['categories'], 'aggregate category counts');
exact(2, $profile['quarantinedObjects'], 'aggregate quarantine object count');
exact(['operational_case_import'=>2,'cutover_baseline'=>1,'historical_reconstruction'=>2], $profile['routes'], 'aggregate migration routes');
exact(2, $profile['applyBlocked'], 'quarantine blocks aggregate apply candidates');
exact(['COMPLETION_WITHOUT_START_EVIDENCE' => 1, 'MALFORMED_FACTWORKSTARTDATE' => 1], $profile['quarantineCounts'], 'aggregate quarantine reasons');
exact($profile, LegacyObjectProfile::aggregate($examples), 'repeat is deterministic');

echo "PASS: deterministic representative legacy classification and redacted profiling\n";
