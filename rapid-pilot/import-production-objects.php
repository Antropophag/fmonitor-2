<?php

declare(strict_types=1);

use FMonitor2\InstallationProcess\PilotCaseImporter;
require_once __DIR__ . '/legacy-migration/LegacyMigrationRouter.php';

set_exception_handler(static function(Throwable $error):never {
    $reason=in_array($error->getMessage(),['QUARANTINED_EVIDENCE','PROVENANCE_CONFLICT'],true)?$error->getMessage():'OPERATIONAL_IMPORT_UNAVAILABLE';
    echo json_encode(['ok'=>false,'reason'=>$reason],JSON_THROW_ON_ERROR),PHP_EOL;exit(2);
});

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

$cutoff = getenv('FMONITOR_MIGRATION_CUTOFF') ?: '2026-08-30 23:59:59';
$sql = <<<'SQL'
SELECT id, ordadr_address, entrance, regnumber, workdatestart,
       workdateendadjusted,
       COALESCE(NULLIF(plan_finish_date, '0000-00-00 00:00:00'), workdatefinish) AS plan_finish_date,
       NULL AS workdatefinish, ptoactdate,
       responsstroicontrol,factworkstartdate,object_status,fact_percent,workstarted,
       (SELECT COUNT(*) FROM fm_install_checklists_values_log l WHERE l.value_id=m.id AND l.ctime<=?) checklist_event_count,
       (SELECT COUNT(*) FROM fm_install_checklists_values_installators_log ai JOIN fm_install_checklists_values v ON v.id=ai.checklist_value_id WHERE v.value_id=m.id AND ai.ctime<=?) attribution_count
FROM fm_maintable m
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
$classifications = [];
$existingClassifications = [];
$source->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$source->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
$selection = $source->prepare($sql); $selection->bind_param('ss',$cutoff,$cutoff); $selection->execute();
foreach ($selection->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $id = (int) $row['id'];
    $classification=LegacyObjectClassification::classify($row);$route=LegacyMigrationRoute::decide($classification);
    if($route['route']!=='operational_case_import'||$route['applyBlocked'])continue;
    if (isset($existing[$id])) { $existingClassifications[$id]=$classification; continue; }
    $selected[] = $row;
    $classifications[$id]=$classification;
    if (count($selected) === $limit) break;
}
$source->commit();
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
$provenance=new MigrationClassificationProvenanceTarget($target,$processPrefix);$provenanceCreated=0;$provenanceBackfilled=0;
foreach($classifications+$existingClassifications as$id=>$classification){$case=$target->query("SELECT id FROM `{$processPrefix}fm2_installation_cases` WHERE legacy_installation_object_id=".(int)$id)->fetch_assoc();if($case===null){if(isset($existingClassifications[$id]))continue;throw new RuntimeException('Imported case missing');}$proof=$provenance->reconcile('operational_case',(int)$id,(int)$case['id'],$cutoff,$classification,gmdate('Y-m-d H:i:s'));if($proof['provenanceCreated']){if(isset($existingClassifications[$id]))$provenanceBackfilled++;else $provenanceCreated++;}}

$caseCount = (int) $target->query("SELECT COUNT(*) AS n FROM `{$processPrefix}fm2_installation_cases`")->fetch_assoc()['n'];
echo json_encode([
    'ok' => true,
    'copied' => count($selected),
    'imported' => count($result['imported']),
    'queueCases' => $caseCount,
    'classificationVersion'=>LegacyObjectClassification::VERSION,
    'provenanceCreated'=>$provenanceCreated,
    'provenanceBackfilled'=>$provenanceBackfilled,
    'firstId' => $ids[0],
    'lastId' => $ids[array_key_last($ids)],
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
