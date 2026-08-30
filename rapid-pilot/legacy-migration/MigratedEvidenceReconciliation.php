<?php

declare(strict_types=1);

require_once __DIR__ . '/LegacyObjectClassification.php';
require_once __DIR__ . '/LegacyChecklistProgressMapping.php';

final class MigratedEvidenceReconciliation
{
    public static function load(mysqli $db, string $prefix): array
    {
        if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new InvalidArgumentException('Invalid local table prefix');
        $table = $db->real_escape_string($prefix . 'fm2_history_source_snapshots');
        if ((int)$db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_assoc()['n'] === 0) return [];
        $snapshots = $db->query("SELECT * FROM `{$prefix}fm2_history_source_snapshots` ORDER BY legacy_object_id,id DESC")->fetch_all(MYSQLI_ASSOC);
        $issues = $db->query("SELECT q.snapshot_id,q.code,q.diagnostic_json FROM `{$prefix}fm2_history_import_quarantine` q ORDER BY q.snapshot_id,q.issue_no")->fetch_all(MYSQLI_ASSOC);
        $workforce = [];
        $workforceTable = $db->real_escape_string($prefix . 'fm2_workforce_catalog');
        if ((int)$db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$workforceTable}'")->fetch_assoc()['n'] > 0) {
            foreach ($db->query("SELECT installer_tab_id,fio,position,employment_status,workforce_source,workforce_source_updated_at,reconciliation_state,authority_system FROM `{$prefix}fm2_workforce_catalog`")->fetch_all(MYSQLI_ASSOC) as $worker) $workforce[(string)$worker['installer_tab_id']] = $worker;
        }
        $bySnapshot = []; foreach ($issues as $issue) $bySnapshot[(int)$issue['snapshot_id']][] = $issue;
        $seen = []; $result = [];
        foreach ($snapshots as $snapshot) {
            $objectId = (int)$snapshot['legacy_object_id']; if (isset($seen[$objectId])) continue; $seen[$objectId] = true;
            $result[] = self::project($snapshot, $bySnapshot[(int)$snapshot['id']] ?? [], $workforce);
        }
        return $result;
    }

    public static function project(array $snapshot, array $importIssues, array $workforce = []): array
    {
        $payload = json_decode((string)$snapshot['payload_json'], true, flags: JSON_THROW_ON_ERROR);
        $object = is_array($payload['object'] ?? null) ? $payload['object'] : [];
        $events = is_array($payload['checklistEvents'] ?? null) ? $payload['checklistEvents'] : [];
        $attributions = is_array($payload['attributions'] ?? null) ? $payload['attributions'] : [];
        $classification = LegacyObjectClassification::classify($object + ['checklist_event_count' => count($events), 'attribution_count' => count($attributions)]);
        $derivedConflicts=[];foreach($attributions as$attribution){$tab=ltrim(trim((string)($attribution['tab_id']??'')),'0');if($tab==='999999'){$derivedConflicts[]='LEGACY_UNASSIGNED_SENTINEL';break;}}
        $conflicts = array_merge($classification['quarantineCodes'],$derivedConflicts); foreach ($importIssues as $issue) $conflicts[] = (string)$issue['code'];
        $conflicts = array_values(array_unique($conflicts)); sort($conflicts, SORT_STRING);
        $grade = $conflicts !== [] ? 'Q' : ($events !== [] && $attributions !== [] ? 'A' : (($events !== [] || $attributions !== []) ? 'B' : 'C'));
        $observations = [];
        foreach ($attributions as $attribution) {
            $tab = trim((string)($attribution['tab_id'] ?? '')); if ($tab === ''||ltrim($tab,'0')==='999999') continue;
            $observations[$tab] = ['tabId'=>$tab, 'observedName'=>trim((string)($attribution['fio'] ?? '')), 'observedAt'=>(string)($attribution['ctime'] ?? ''), 'source'=>'legacy_attribution_log'];
        }
        ksort($observations, SORT_STRING); $workforceFacts = [];
        foreach ($observations as $tab => $_observation) if (isset($workforce[$tab])) $workforceFacts[$tab] = [
            'tabId'=>$tab, 'fullName'=>(string)$workforce[$tab]['fio'], 'position'=>(string)$workforce[$tab]['position'],
            'employmentStatus'=>(string)$workforce[$tab]['employment_status'], 'reconciliationState'=>(string)$workforce[$tab]['reconciliation_state'],
            'authoritySystem'=>(string)$workforce[$tab]['authority_system'], 'source'=>(string)$workforce[$tab]['workforce_source'],
            'sourceUpdatedAt'=>(string)$workforce[$tab]['workforce_source_updated_at'],
        ];
        $progressMapping=LegacyChecklistProgressMapping::profile($payload);if(in_array('LEGACY_UNASSIGNED_SENTINEL',$derivedConflicts,true)){$progressMapping['candidateProgressBp']=null;$progressMapping['eligibleForCalculation']=false;$progressMapping['conflictCodes'][]='LEGACY_UNASSIGNED_SENTINEL';$progressMapping['conflictCodes']=array_values(array_unique($progressMapping['conflictCodes']));sort($progressMapping['conflictCodes'],SORT_STRING);}
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
            'progressMapping' => $progressMapping,
            'attributionObservations' => array_values($observations), 'workforceFacts' => array_values($workforceFacts),
            'conflictCodes' => $conflicts, 'quarantineCount' => count($importIssues) + count($classification['quarantineCodes']) + count($derivedConflicts),
        ];
        $projection['projectionHash'] = hash('sha256', json_encode($projection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        return $projection;
    }
}
