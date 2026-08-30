<?php

declare(strict_types=1);

function nativeOnlyEnv(string $name): string
{
    $value = getenv($name);
    if ($value === false || $value === '') throw new RuntimeException("Missing {$name}");
    return $value;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$options = getopt('', ['expect-empty-cases']);
$manifest = json_decode((string) file_get_contents(nativeOnlyEnv('FMONITOR_PILOT_ACTIVE_MANIFEST')), true, flags: JSON_THROW_ON_ERROR);
$prefix = (string) ($manifest['processPrefix'] ?? '');
$legacyPrefix = (string) ($manifest['legacyPrefix'] ?? '');
if (($manifest['mode'] ?? null) !== 'native-only' || preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1 || preg_match('/^[A-Za-z0-9_]+$/D', $legacyPrefix) !== 1) {
    throw new DomainException('GENERATION_NOT_NATIVE_ONLY');
}

$db = new mysqli(nativeOnlyEnv('FMONITOR_DB_HOST'), nativeOnlyEnv('FMONITOR_DB_USER'), nativeOnlyEnv('FMONITOR_DB_PASSWORD'), nativeOnlyEnv('FMONITOR_DB_NAME'), (int) nativeOnlyEnv('FMONITOR_DB_PORT'));
$db->set_charset('utf8mb4');
$db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
try {
    $exists = static function (mysqli $db, string $table): bool {
        $escaped = $db->real_escape_string($table);
        return (int) $db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$escaped}'")->fetch_assoc()['n'] === 1;
    };
    $count = static function (mysqli $db, string $table, string $where = '1=1') use ($exists): int {
        return $exists($db, $table) ? (int) $db->query("SELECT COUNT(*) n FROM `{$table}` WHERE {$where}")->fetch_assoc()['n'] : 0;
    };
    $cases = $count($db, $prefix . 'fm2_installation_cases');
    $nativeCases = $exists($db, $prefix . 'fm2_migration_classification_provenance')
        ? $count($db, $prefix . 'fm2_installation_cases', "EXISTS (SELECT 1 FROM `{$prefix}fm2_migration_classification_provenance` p WHERE p.output_kind='operational_case' AND p.output_id=`{$prefix}fm2_installation_cases`.id AND p.legacy_object_id=`{$prefix}fm2_installation_cases`.legacy_installation_object_id AND p.category='native_candidate' AND p.classification_version='legacy-object-classification-v1' AND p.reason_codes_json='[\"NO_ACTUAL_START_OR_PROGRESS_EVIDENCE\"]' AND p.classification_sha256 REGEXP '^[a-f0-9]{64}$')")
        : 0;
    $forbidden = [
        'legacyActiveBaselines' => $count($db, $prefix . 'fm2_legacy_active_baselines'),
        'historicalSnapshots' => $count($db, $prefix . 'fm2_history_source_snapshots'),
        'historicalQuarantine' => $count($db, $prefix . 'fm2_history_import_quarantine'),
        'activeCaseProvenance' => $count($db, $prefix . 'fm2_active_case_provenance'),
        'migrationQuarantineRegistry' => $count($db, $prefix . 'fm2_migration_quarantine_registry'),
        'migrationQuarantineObservations' => $count($db, $prefix . 'fm2_migration_quarantine_observations'),
        'migrationQuarantineDecisions' => $count($db, $prefix . 'fm2_migration_quarantine_decisions'),
        'migratedEvidenceProjection' => $count($db, $prefix . 'fm2_migrated_evidence_projection'),
        'migratedEvidenceConflicts' => $count($db, $prefix . 'fm2_migrated_evidence_conflicts'),
        'migratedEvidenceDecisions' => $count($db, $prefix . 'fm2_migrated_evidence_decisions'),
        'migratedEvidenceDecisionState' => $count($db, $prefix . 'fm2_migrated_evidence_decision_state'),
        'otizSnapshotEvidence' => $count($db, $prefix . 'fm2_pilot_otiz_snapshot_evidence'),
        'objectDetailQuarantine' => $count($db, $prefix . 'fm2_pilot_object_detail_quarantine'),
        'syntheticOtizOperands' => $count($db, $prefix . 'fm2_pilot_otiz_snapshot_objects', "inputs_json LIKE '%synthetic_rapid_pilot%'"),
    ];
    $fixtureDirectoryRows = $count($db, $legacyPrefix . 'users') + $count($db, $legacyPrefix . 'users_roles');
    $localMirrorObjects = $count($db, $legacyPrefix . 'fm_maintable');
    $emptyExpected = isset($options['expect-empty-cases']);
    $ok = $cases === $nativeCases && $cases === $localMirrorObjects && (!$emptyExpected || $cases === 0) && $fixtureDirectoryRows === 0 && array_sum($forbidden) === 0;
    $db->commit();
    $result = ['ok' => $ok, 'mode' => 'read-only-verification', 'generationMode' => 'native-only', 'cases' => $cases, 'nativeCandidateCases' => $nativeCases, 'localMirrorObjects' => $localMirrorObjects, 'fixtureDirectoryRows' => $fixtureDirectoryRows, 'forbidden' => $forbidden, 'identifiersExposed' => false];
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
    if (!$ok) exit(2);
} catch (Throwable $error) {
    $db->rollback();
    throw $error;
} finally {
    $db->close();
}
