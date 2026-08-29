<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$root = dirname(__DIR__);
$envPath = $root . '/.env';
if (!is_file($envPath)) {
    fwrite(STDERR, "Missing .env\n");
    exit(2);
}

$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    if (str_starts_with(ltrim($line), '#') || !str_contains($line, '=')) continue;
    [$name, $value] = explode('=', $line, 2);
    $env[trim($name)] = trim($value);
}

foreach (['FMONITOR_SOURCE_HOST', 'FMONITOR_SOURCE_PORT', 'FMONITOR_SOURCE_NAME', 'FMONITOR_SOURCE_USER', 'FMONITOR_SOURCE_PASSWORD'] as $required) {
    if (!array_key_exists($required, $env) || $env[$required] === '') {
        fwrite(STDERR, "Missing {$required}\n");
        exit(2);
    }
}

$db = new mysqli(
    $env['FMONITOR_SOURCE_HOST'],
    $env['FMONITOR_SOURCE_USER'],
    $env['FMONITOR_SOURCE_PASSWORD'],
    $env['FMONITOR_SOURCE_NAME'],
    (int) $env['FMONITOR_SOURCE_PORT'],
);
$db->set_charset('utf8mb4');
$db->query('SET SESSION TRANSACTION READ ONLY');
$db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY | MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);

$cutoff = (string) $db->query('SELECT NOW() AS cutoff_at')->fetch_assoc()['cutoff_at'];
$outputDir = $root . '/.local-analysis/legacy-installer-subcontractors-2026-08-29';
if (!is_dir($outputDir) && !mkdir($outputDir, 0700, true) && !is_dir($outputDir)) {
    throw new RuntimeException('Cannot create output directory');
}

function csv(string $path, array $header, array $rows): void
{
    $handle = fopen($path, 'wb');
    if ($handle === false) throw new RuntimeException("Cannot open {$path}");
    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, $header, ';');
    foreach ($rows as $row) fputcsv($handle, array_values($row), ';');
    fclose($handle);
    chmod($path, 0600);
}

$installerUnion = implode("\nUNION ALL\n", array_map(
    static fn (string $column): string => "SELECT id AS object_id, subsuplier AS contractor_id, {$column} AS installer_tab_id, workdatestart, ptoactdate FROM fm_maintable",
    ['installator', 'installator2', 'installator3', 'installator4'],
));

$contractorRows = $db->query(<<<SQL
SELECT
    m.subsuplier AS contractor_id,
    COALESCE(NULLIF(TRIM(v.name), ''), CONCAT('[неизвестное значение #', m.subsuplier, ']')) AS contractor_name,
    COUNT(DISTINCT m.id) AS object_count,
    COUNT(DISTINCT CASE WHEN u.installer_tab_id IS NOT NULL THEN m.id END) AS objects_with_real_installer,
    COUNT(DISTINCT u.installer_tab_id) AS unique_installer_count,
    COUNT(DISTINCT CASE WHEN m.ptoactdate IS NOT NULL AND m.ptoactdate NOT IN ('', '0000-00-00 00:00:00') THEN m.id END) AS completed_object_count
FROM fm_maintable m
LEFT JOIN fm_fields_values v ON v.id = m.subsuplier AND v.field_id = 7
LEFT JOIN (
    {$installerUnion}
) u ON u.object_id = m.id AND u.installer_tab_id NOT IN (0, 999999)
WHERE m.subsuplier IS NOT NULL AND m.subsuplier NOT IN ('', 0)
GROUP BY m.subsuplier, v.name
ORDER BY contractor_name, contractor_id
SQL)->fetch_all(MYSQLI_ASSOC);

$linkRows = $db->query(<<<SQL
SELECT
    u.installer_tab_id,
    TRIM(CONCAT_WS(' ', i.lastname, i.name, i.s_name)) AS installer_fio,
    u.contractor_id,
    COALESCE(NULLIF(TRIM(v.name), ''), CONCAT('[неизвестное значение #', u.contractor_id, ']')) AS contractor_name,
    COUNT(DISTINCT u.object_id) AS object_count,
    COUNT(DISTINCT CASE WHEN u.ptoactdate IS NOT NULL AND u.ptoactdate NOT IN ('', '0000-00-00 00:00:00') THEN u.object_id END) AS completed_object_count,
    MIN(NULLIF(DATE(u.workdatestart), '0000-00-00')) AS earliest_planned_start,
    MAX(NULLIF(DATE(u.ptoactdate), '0000-00-00')) AS latest_pto_act_date
FROM (
    {$installerUnion}
) u
LEFT JOIN fm_installators i ON i.tab_id = u.installer_tab_id
LEFT JOIN fm_fields_values v ON v.id = u.contractor_id AND v.field_id = 7
WHERE u.contractor_id IS NOT NULL AND u.contractor_id NOT IN ('', 0)
  AND u.installer_tab_id IS NOT NULL AND u.installer_tab_id NOT IN (0, 999999)
GROUP BY u.installer_tab_id, i.lastname, i.name, i.s_name, u.contractor_id, v.name
ORDER BY installer_fio, u.installer_tab_id, contractor_name, u.contractor_id
SQL)->fetch_all(MYSQLI_ASSOC);

$byInstaller = [];
foreach ($linkRows as $row) $byInstaller[(string) $row['installer_tab_id']][] = $row;
$conflictRows = [];
foreach ($byInstaller as $tabId => $rows) {
    if (count($rows) < 2) continue;
    $contractors = [];
    $totalObjects = 0;
    $completedObjects = 0;
    foreach ($rows as $row) {
        $contractors[] = sprintf('%s [#%s]: %s объектов', $row['contractor_name'], $row['contractor_id'], $row['object_count']);
        $totalObjects += (int) $row['object_count'];
        $completedObjects += (int) $row['completed_object_count'];
    }
    $conflictRows[] = [
        'installer_tab_id' => $tabId,
        'installer_fio' => $rows[0]['installer_fio'],
        'contractor_count' => count($rows),
        'total_pair_object_count' => $totalObjects,
        'completed_pair_object_count' => $completedObjects,
        'contractors' => implode(' | ', $contractors),
    ];
}
usort($conflictRows, static fn (array $a, array $b): int => [$b['contractor_count'], $b['total_pair_object_count'], $a['installer_fio']] <=> [$a['contractor_count'], $a['total_pair_object_count'], $b['installer_fio']]);

// A field-7 value may encode both the immediate subcontractor and a parent in
// parentheses. The candidate projection groups by the leading organization;
// raw values remain untouched in the primary exports.
function canonicalSubcontractorName(string $rawName): string
{
    $name = trim($rawName);
    $name = ltrim($name, '(');
    $head = trim(explode('(', $name, 2)[0]);
    $head = preg_replace('/^ООО\s+/u', '', $head) ?? $head;
    $head = str_replace(['«', '»', '"'], '', $head);
    $head = preg_replace('/\s+/u', ' ', trim($head)) ?? trim($head);
    $head = mb_strtoupper($head, 'UTF-8');
    if (str_contains($head, 'ДЕЛЕКА')) return 'СК ДЕЛЕКА';
    return $head;
}

$canonicalPairs = [];
foreach ($linkRows as $row) {
    $rawId = (int) $row['contractor_id'];
    if (str_starts_with($row['contractor_name'], '[неизвестное значение')) continue;
    $canonicalName = canonicalSubcontractorName($row['contractor_name']);
    if ($canonicalName === '') continue;
    $key = $row['installer_tab_id'] . ':' . $canonicalName;
    if (!isset($canonicalPairs[$key])) {
        $canonicalPairs[$key] = [
            'installer_tab_id' => $row['installer_tab_id'],
            'installer_fio' => $row['installer_fio'],
            'canonical_subcontractor_name' => $canonicalName,
            'raw_contractor_ids' => [],
            'object_count' => 0,
            'completed_object_count' => 0,
        ];
    }
    $canonicalPairs[$key]['raw_contractor_ids'][(string) $rawId] = true;
    $canonicalPairs[$key]['object_count'] += (int) $row['object_count'];
    $canonicalPairs[$key]['completed_object_count'] += (int) $row['completed_object_count'];
}
$canonicalLinkRows = [];
foreach ($canonicalPairs as $row) {
    $row['raw_contractor_ids'] = implode(',', array_keys($row['raw_contractor_ids']));
    $canonicalLinkRows[] = $row;
}
usort($canonicalLinkRows, static fn (array $a, array $b): int => [$a['installer_fio'], $a['installer_tab_id'], $a['canonical_subcontractor_name']] <=> [$b['installer_fio'], $b['installer_tab_id'], $b['canonical_subcontractor_name']]);
$canonicalDirectory = [];
foreach ($contractorRows as $row) {
    if (str_starts_with($row['contractor_name'], '[неизвестное значение')) continue;
    $canonicalName = canonicalSubcontractorName($row['contractor_name']);
    if ($canonicalName === '') continue;
    if (!isset($canonicalDirectory[$canonicalName])) {
        $canonicalDirectory[$canonicalName] = [
            'canonical_subcontractor_name' => $canonicalName,
            'raw_value_ids' => [],
            'raw_value_names' => [],
            'object_count' => 0,
            'completed_object_count' => 0,
            'linked_installer_ids' => [],
        ];
    }
    $canonicalDirectory[$canonicalName]['raw_value_ids'][(string) $row['contractor_id']] = true;
    $canonicalDirectory[$canonicalName]['raw_value_names'][$row['contractor_name']] = true;
    $canonicalDirectory[$canonicalName]['object_count'] += (int) $row['object_count'];
    $canonicalDirectory[$canonicalName]['completed_object_count'] += (int) $row['completed_object_count'];
}
foreach ($canonicalLinkRows as $row) {
    $canonicalDirectory[$row['canonical_subcontractor_name']]['linked_installer_ids'][(string) $row['installer_tab_id']] = true;
}
$canonicalDirectoryRows = [];
foreach ($canonicalDirectory as $row) {
    $row['raw_value_ids'] = implode(',', array_keys($row['raw_value_ids']));
    $row['raw_value_names'] = implode(' | ', array_keys($row['raw_value_names']));
    $row['unique_linked_installer_count'] = count($row['linked_installer_ids']);
    unset($row['linked_installer_ids']);
    $canonicalDirectoryRows[] = $row;
}
usort($canonicalDirectoryRows, static fn (array $a, array $b): int => $a['canonical_subcontractor_name'] <=> $b['canonical_subcontractor_name']);
$canonicalByInstaller = [];
foreach ($canonicalLinkRows as $row) $canonicalByInstaller[(string) $row['installer_tab_id']][] = $row;
$canonicalConflictRows = [];
foreach ($canonicalByInstaller as $tabId => $rows) {
    if (count($rows) < 2) continue;
    $contractors = [];
    $totalObjects = 0;
    foreach ($rows as $row) {
        $contractors[] = sprintf('%s: %s объектов', $row['canonical_subcontractor_name'], $row['object_count']);
        $totalObjects += (int) $row['object_count'];
    }
    $canonicalConflictRows[] = [
        'installer_tab_id' => $tabId,
        'installer_fio' => $rows[0]['installer_fio'],
        'canonical_contractor_count' => count($rows),
        'total_pair_object_count' => $totalObjects,
        'contractors' => implode(' | ', $contractors),
    ];
}
usort($canonicalConflictRows, static fn (array $a, array $b): int => [$b['canonical_contractor_count'], $b['total_pair_object_count'], $a['installer_fio']] <=> [$a['canonical_contractor_count'], $a['total_pair_object_count'], $b['installer_fio']]);

csv($outputDir . '/subcontractors.csv', array_keys($contractorRows[0] ?? [
    'contractor_id' => null, 'contractor_name' => null, 'object_count' => null,
    'objects_with_real_installer' => null, 'unique_installer_count' => null, 'completed_object_count' => null,
]), $contractorRows);
csv($outputDir . '/subcontractors-normalized.csv', array_keys($canonicalDirectoryRows[0] ?? [
    'canonical_subcontractor_name' => null, 'raw_value_ids' => null, 'raw_value_names' => null,
    'object_count' => null, 'completed_object_count' => null, 'unique_linked_installer_count' => null,
]), $canonicalDirectoryRows);
csv($outputDir . '/installer-subcontractor-links.csv', array_keys($linkRows[0] ?? [
    'installer_tab_id' => null, 'installer_fio' => null, 'contractor_id' => null, 'contractor_name' => null,
    'object_count' => null, 'completed_object_count' => null, 'earliest_planned_start' => null, 'latest_pto_act_date' => null,
]), $linkRows);
csv($outputDir . '/installer-subcontractor-conflicts.csv', array_keys($conflictRows[0] ?? [
    'installer_tab_id' => null, 'installer_fio' => null, 'contractor_count' => null,
    'total_pair_object_count' => null, 'completed_pair_object_count' => null, 'contractors' => null,
]), $conflictRows);
csv($outputDir . '/installer-subcontractor-links-canonical-candidates.csv', array_keys($canonicalLinkRows[0] ?? [
    'installer_tab_id' => null, 'installer_fio' => null,
    'canonical_subcontractor_name' => null, 'raw_contractor_ids' => null, 'object_count' => null,
    'completed_object_count' => null,
]), $canonicalLinkRows);
csv($outputDir . '/installer-subcontractor-conflicts-canonical-candidates.csv', array_keys($canonicalConflictRows[0] ?? [
    'installer_tab_id' => null, 'installer_fio' => null, 'canonical_contractor_count' => null,
    'total_pair_object_count' => null, 'contractors' => null,
]), $canonicalConflictRows);
$twoSubcontractorConflictRows = array_values(array_filter(
    $canonicalConflictRows,
    static fn (array $row): bool => (int) $row['canonical_contractor_count'] === 2,
));
csv($outputDir . '/installers-linked-to-exactly-two-subcontractors.csv', array_keys($twoSubcontractorConflictRows[0] ?? [
    'installer_tab_id' => null, 'installer_fio' => null, 'canonical_contractor_count' => null,
    'total_pair_object_count' => null, 'contractors' => null,
]), $twoSubcontractorConflictRows);

$unknownContractors = count(array_filter($contractorRows, static fn (array $row): bool => str_starts_with($row['contractor_name'], '[неизвестное значение')));
$unknownInstallers = count(array_filter($linkRows, static fn (array $row): bool => $row['installer_fio'] === ''));
$summary = [
    'cutoff_at' => $cutoff,
    'contractor_count' => count($contractorRows),
    'contractor_value_without_dictionary_name_count' => $unknownContractors,
    'installer_contractor_pair_count' => count($linkRows),
    'unique_linked_installer_count' => count($byInstaller),
    'installer_without_directory_name_pair_count' => $unknownInstallers,
    'conflicting_installer_count' => count($conflictRows),
    'canonical_known_contractor_count' => count(array_unique(array_map(static fn (array $row): string => canonicalSubcontractorName($row['contractor_name']), array_filter($contractorRows, static fn (array $row): bool => !str_starts_with($row['contractor_name'], '[неизвестное значение'))))),
    'canonical_known_conflicting_installer_count' => count($canonicalConflictRows),
    'max_contractors_per_installer' => $conflictRows === [] ? 0 : max(array_column($conflictRows, 'contractor_count')),
    'method' => 'current fm_maintable.subsuplier x current installator/installator2/installator3/installator4 co-occurrence; subsuplier resolves through fm_fields_values field_id=7; excludes 0 and 999999',
];
file_put_contents($outputDir . '/summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
chmod($outputDir . '/summary.json', 0600);

$db->rollback();
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
