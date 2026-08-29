<?php

declare(strict_types=1);

use FMonitor2\InstallationProcess\PilotCaseImporter;

spl_autoload_register(static function (string $class): void {
    $prefix = 'FMonitor2\\InstallationProcess\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = dirname(__DIR__) . '/app/InstallationProcess/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require $path;
});

function requiredEnv(string $name): string
{
    $value = getenv($name);
    if ($value === false || $value === '') throw new RuntimeException("Missing {$name}");
    return $value;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$manifestPath = requiredEnv('FMONITOR_PILOT_ACTIVE_MANIFEST');
$manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
$processPrefix = (string) ($manifest['processPrefix'] ?? '');
$legacyPrefix = (string) ($manifest['legacyPrefix'] ?? '');
$limit = 100;

$source = new mysqli(
    getenv('FMONITOR_SOURCE_HOST') ?: '127.0.0.1',
    requiredEnv('FMONITOR_SOURCE_USER'),
    requiredEnv('FMONITOR_SOURCE_PASSWORD'),
    getenv('FMONITOR_SOURCE_NAME') ?: 'c1_fmonitor',
    (int) (getenv('FMONITOR_SOURCE_PORT') ?: '13306'),
);
$source->set_charset('utf8mb4');

$target = new mysqli('127.0.0.1', 'fmonitor2_demo', 'fmonitor2_demo_local', 'fmonitor2_demo', 23306);
$target->set_charset('utf8mb4');

$existing = [];
$existingResult = $target->query("SELECT id FROM `{$legacyPrefix}fm_maintable`");
foreach ($existingResult->fetch_all(MYSQLI_ASSOC) as $row) $existing[(int) $row['id']] = true;

$sql = <<<'SQL'
SELECT id, ordadr_address, entrance, regnumber, workdatestart,
       workdateendadjusted,
       COALESCE(NULLIF(plan_finish_date, '0000-00-00 00:00:00'), workdatefinish) AS plan_finish_date,
       NULL AS workdatefinish, ptoactdate,
       responsstroicontrol
FROM fm_maintable
WHERE factworkstartdate = '0000-00-00 00:00:00'
  AND object_status <> 259
  AND workdatestart >= '2026-10-01'
  AND TRIM(COALESCE(ordadr_address, '')) <> ''
  AND TRIM(COALESCE(entrance, '')) <> ''
  AND TRIM(COALESCE(regnumber, '')) <> ''
  AND COALESCE(NULLIF(workdateendadjusted, '0000-00-00 00:00:00'), NULLIF(plan_finish_date, '0000-00-00 00:00:00'), NULLIF(workdatefinish, '0000-00-00 00:00:00')) IS NOT NULL
  AND (ptoactdate IS NULL OR ptoactdate = '' OR ptoactdate = '0000-00-00 00:00:00')
ORDER BY workdatestart, id
LIMIT 250
SQL;

$selected = [];
foreach ($source->query($sql)->fetch_all(MYSQLI_ASSOC) as $row) {
    $id = (int) $row['id'];
    if (isset($existing[$id])) continue;
    $selected[] = $row;
    if (count($selected) === $limit) break;
}
if (count($selected) !== $limit) throw new RuntimeException('Production selection returned fewer than 100 eligible new objects');

$target->begin_transaction();
try {
    $insert = $target->prepare(
        "INSERT INTO `{$legacyPrefix}fm_maintable` "
        . '(id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,workdatefinish,ptoactdate,responsstroicontrol) '
        . 'VALUES (?,?,?,?,?,?,?,?,?,?)'
    );
    foreach ($selected as $row) {
        $id = (int) $row['id'];
        $values = array_map(static fn ($value): ?string => $value === null ? null : (string) $value, [
            $row['ordadr_address'], $row['entrance'], $row['regnumber'], $row['workdatestart'],
            $row['workdateendadjusted'], $row['plan_finish_date'], $row['workdatefinish'],
            $row['ptoactdate'], $row['responsstroicontrol'],
        ]);
        $insert->bind_param('isssssssss', $id, ...$values);
        $insert->execute();
    }
    $target->commit();
} catch (Throwable $error) {
    $target->rollback();
    throw $error;
}

$ids = array_map(static fn (array $row): int => (int) $row['id'], $selected);
$importer = new PilotCaseImporter($target, $processPrefix, $legacyPrefix);
$importer->assertSchemaAvailable();
$result = $importer->import($ids, (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:sP'));
if (isset($result['rejected'])) throw new RuntimeException('Local pilot importer rejected production rows');

$caseCount = (int) $target->query("SELECT COUNT(*) AS n FROM `{$processPrefix}fm2_installation_cases`")->fetch_assoc()['n'];
echo json_encode([
    'ok' => true,
    'copied' => count($selected),
    'imported' => count($result['imported']),
    'queueCases' => $caseCount,
    'firstId' => $ids[0],
    'lastId' => $ids[array_key_last($ids)],
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
