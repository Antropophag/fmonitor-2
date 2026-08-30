<?php

declare(strict_types=1);

require_once __DIR__ . '/LegacyObjectClassification.php';

final class MigratedEvidenceReconciliation
{
    public static function load(mysqli $db, string $prefix): array
    {
        if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new InvalidArgumentException('Invalid local table prefix');
        $table = $db->real_escape_string($prefix . 'fm2_history_source_snapshots');
        if ((int)$db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_assoc()['n'] === 0) return [];
        $snapshots = $db->query("SELECT * FROM `{$prefix}fm2_history_source_snapshots` ORDER BY legacy_object_id,id DESC")->fetch_all(MYSQLI_ASSOC);
        $issues = $db->query("SELECT q.snapshot_id,q.code,q.diagnostic_json FROM `{$prefix}fm2_history_import_quarantine` q ORDER BY q.snapshot_id,q.issue_no")->fetch_all(MYSQLI_ASSOC);
        $bySnapshot = []; foreach ($issues as $issue) $bySnapshot[(int)$issue['snapshot_id']][] = $issue;
        $seen = []; $result = [];
        foreach ($snapshots as $snapshot) {
            $objectId = (int)$snapshot['legacy_object_id']; if (isset($seen[$objectId])) continue; $seen[$objectId] = true;
            $result[] = self::project($snapshot, $bySnapshot[(int)$snapshot['id']] ?? []);
        }
        return $result;
    }

    public static function project(array $snapshot, array $importIssues): array
    {
        $payload = json_decode((string)$snapshot['payload_json'], true, flags: JSON_THROW_ON_ERROR);
        $object = is_array($payload['object'] ?? null) ? $payload['object'] : [];
        $events = is_array($payload['checklistEvents'] ?? null) ? $payload['checklistEvents'] : [];
        $attributions = is_array($payload['attributions'] ?? null) ? $payload['attributions'] : [];
        $classification = LegacyObjectClassification::classify($object + ['checklist_event_count' => count($events), 'attribution_count' => count($attributions)]);
        $conflicts = $classification['quarantineCodes']; foreach ($importIssues as $issue) $conflicts[] = (string)$issue['code'];
        $conflicts = array_values(array_unique($conflicts)); sort($conflicts, SORT_STRING);
        $grade = $conflicts !== [] ? 'Q' : ($events !== [] && $attributions !== [] ? 'A' : (($events !== [] || $attributions !== []) ? 'B' : 'C'));
        $projection = [
            'snapshotId' => (int)$snapshot['id'], 'legacyObjectId' => (int)$snapshot['legacy_object_id'],
            'regnumber' => trim((string)($object['regnumber'] ?? '')), 'address' => trim((string)($object['ordadr_address'] ?? '')),
            'sourceLabel' => 'Legacy FMonitor · только чтение', 'sourceSystem' => (string)$snapshot['source_system'],
            'sourceLocator' => (string)$snapshot['source_locator'], 'contentSha256' => (string)$snapshot['content_sha256'],
            'cutoffAt' => (string)$snapshot['cutoff_at'], 'extractorVersion' => (string)$snapshot['extractor_version'],
            'classification' => $classification['category'], 'classificationVersion' => $classification['classificationVersion'],
            'reasonCodes' => $classification['reasonCodes'], 'evidenceGrade' => $grade,
            'confidence' => $grade === 'A' ? 'high' : ($grade === 'B' ? 'medium' : 'low'),
            'counts' => ['checklistEvents' => count($events), 'attributions' => count($attributions)],
            'conflictCodes' => $conflicts, 'quarantineCount' => count($importIssues) + count($classification['quarantineCodes']),
        ];
        $projection['projectionHash'] = hash('sha256', json_encode($projection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        return $projection;
    }
}
