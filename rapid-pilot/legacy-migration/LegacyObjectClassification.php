<?php

declare(strict_types=1);

final class LegacyObjectClassification
{
    public const VERSION = 'legacy-object-classification-v1';
    public const CATEGORIES = ['native_candidate', 'legacy_active', 'legacy_historical'];

    /** @param array<string,mixed> $row */
    public static function classify(array $row): array
    {
        $quarantine = [];
        $id = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) $quarantine[] = 'INVALID_LEGACY_OBJECT_ID';
        foreach (['ordadr_address', 'entrance', 'regnumber'] as $field) {
            if (trim((string)($row[$field] ?? '')) === '') $quarantine[] = 'MISSING_' . strtoupper($field);
        }

        $dates = [];
        foreach (['factworkstartdate', 'ptoactdate'] as $field) {
            try { $dates[$field] = self::date($row[$field] ?? null); }
            catch (InvalidArgumentException) { $dates[$field] = null; $quarantine[] = 'MALFORMED_' . strtoupper($field); }
        }
        $checklistEvents = self::count($row['checklist_event_count'] ?? 0, 'CHECKLIST_EVENT_COUNT', $quarantine);
        $attributions = self::count($row['attribution_count'] ?? 0, 'ATTRIBUTION_COUNT', $quarantine);
        $factPercent = filter_var($row['fact_percent'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($factPercent === false || $factPercent < 0 || $factPercent > 100) { $quarantine[] = 'INVALID_FACT_PERCENT'; $factPercent = 0.0; }
        $workStarted = (string)($row['workstarted'] ?? '0') !== '0' && trim((string)($row['workstarted'] ?? '')) !== '';
        $statusFinished = (string)($row['object_status'] ?? '') === '259';

        $completionReasons = [];
        if ($dates['ptoactdate'] !== null) $completionReasons[] = 'PTO_ACT_RECORDED';
        if ($statusFinished) $completionReasons[] = 'LEGACY_FINISHED_STATUS';
        $startReasons = [];
        if ($dates['factworkstartdate'] !== null) $startReasons[] = 'ACTUAL_START_RECORDED';
        if ($checklistEvents > 0) $startReasons[] = 'CHECKLIST_HISTORY_PRESENT';
        if ($attributions > 0) $startReasons[] = 'WORK_ATTRIBUTION_HISTORY_PRESENT';
        if ($factPercent > 0) $startReasons[] = 'FACT_PROGRESS_RECORDED';
        if ($workStarted) $startReasons[] = 'LEGACY_WORK_STARTED_FLAG';

        if ($completionReasons !== []) {
            $category = 'legacy_historical';
            $reasons = $completionReasons;
            if ($startReasons === []) $quarantine[] = 'COMPLETION_WITHOUT_START_EVIDENCE';
        } elseif ($startReasons !== []) {
            $category = 'legacy_active';
            $reasons = $startReasons;
        } else {
            $category = 'native_candidate';
            $reasons = ['NO_ACTUAL_START_OR_PROGRESS_EVIDENCE'];
        }

        sort($quarantine, SORT_STRING);
        return [
            'classificationVersion' => self::VERSION,
            'category' => $category,
            'reasonCodes' => $reasons,
            'quarantineCodes' => array_values(array_unique($quarantine)),
        ];
    }

    private static function date(mixed $raw): ?string
    {
        if ($raw === null) return null;
        $value = trim((string)$raw);
        if ($value === '' || preg_match('/^0+(?:-0+){0,2}(?: 00:00:00)?$/D', $value) === 1) return null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}:\d{2})?$/D', $value, $parts) !== 1
            || !checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1])) throw new InvalidArgumentException();
        return "{$parts[1]}-{$parts[2]}-{$parts[3]}";
    }

    /** @param list<string> $quarantine */
    private static function count(mixed $raw, string $field, array &$quarantine): int
    {
        $value = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($value === false) { $quarantine[] = 'INVALID_' . $field; return 0; }
        return $value;
    }
}

final class LegacyObjectProfile
{
    /** @param iterable<array<string,mixed>> $rows */
    public static function aggregate(iterable $rows): array
    {
        $categories = array_fill_keys(LegacyObjectClassification::CATEGORIES, 0);
        $reasons = $quarantine = [];
        $total = 0; $quarantined = 0;
        foreach ($rows as $row) {
            $result = LegacyObjectClassification::classify($row); $total++;
            $categories[$result['category']]++;
            foreach ($result['reasonCodes'] as $code) $reasons[$code] = ($reasons[$code] ?? 0) + 1;
            if ($result['quarantineCodes'] !== []) $quarantined++;
            foreach ($result['quarantineCodes'] as $code) $quarantine[$code] = ($quarantine[$code] ?? 0) + 1;
        }
        ksort($reasons, SORT_STRING); ksort($quarantine, SORT_STRING);
        $routes = ['operational_case_import' => $categories['native_candidate'],
            'cutover_baseline' => $categories['legacy_active'],
            'historical_reconstruction' => $categories['legacy_historical']];
        return ['classificationVersion' => LegacyObjectClassification::VERSION, 'total' => $total,
            'categories' => $categories, 'quarantinedObjects' => $quarantined,
            'routes' => $routes, 'applyBlocked' => $quarantined,
            'reasonCounts' => $reasons, 'quarantineCounts' => $quarantine];
    }
}
