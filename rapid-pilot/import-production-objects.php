<?php

declare(strict_types=1);

use FMonitor2\InstallationProcess\PilotCaseImporter;
require_once __DIR__ . '/legacy-migration/LegacyMigrationRouter.php';

set_exception_handler(static function(Throwable $error):never {
    $reason=in_array($error->getMessage(),['QUARANTINED_EVIDENCE','PROVENANCE_CONFLICT','OPERATIONAL_ROUTE_NOT_ALLOWED','OPERATIONAL_CASE_NOT_ELIGIBLE','CONFIGURATION_INVALID'],true)?$error->getMessage():'OPERATIONAL_IMPORT_UNAVAILABLE';
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

$options=getopt('',['object-id:','dry-run']);
$selectedId=array_key_exists('object-id',$options)?filter_var($options['object-id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):null;
$dryRun=array_key_exists('dry-run',$options);
if(($selectedId===false)||($dryRun&&$selectedId===null))throw new InvalidArgumentException('CONFIGURATION_INVALID');

$manifestPath = requiredEnv('FMONITOR_PILOT_ACTIVE_MANIFEST');
$manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
$processPrefix = (string) ($manifest['processPrefix'] ?? '');
$legacyPrefix = (string) ($manifest['legacyPrefix'] ?? '');

$source = new mysqli(
    getenv('FMONITOR_SOURCE_HOST') ?: '127.0.0.1',
    requiredEnv('FMONITOR_SOURCE_USER'),
    requiredEnv('FMONITOR_SOURCE_PASSWORD'),
    getenv('FMONITOR_SOURCE_NAME') ?: 'c1_fmonitor',
    (int) (getenv('FMONITOR_SOURCE_PORT') ?: '13306'),
);
$source->set_charset('utf8mb4');

$targetConnection=static function():mysqli{$db=new mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'fmonitor2_demo',getenv('FMONITOR_DB_PASSWORD')?:'fmonitor2_demo_local',getenv('FMONITOR_DB_NAME')?:'fmonitor2_demo',(int)(getenv('FMONITOR_DB_PORT')?:23306));$db->set_charset('utf8mb4');return $db;};

$cutoff = getenv('FMONITOR_MIGRATION_CUTOFF') ?: '2026-08-30 23:59:59';
if($selectedId!==null){
    $source->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');$source->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
    try{
        $classificationRow=(new LegacyObjectMySqlClassificationSource($source))->read((int)$selectedId,$cutoff,false);
        $projection=$source->prepare("SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,COALESCE(NULLIF(plan_finish_date,'0000-00-00 00:00:00'),workdatefinish) plan_finish_date,NULL workdatefinish,ptoactdate,responsstroicontrol FROM fm_maintable WHERE id=? LIMIT 1");$projection->bind_param('i',$selectedId);$projection->execute();$row=$projection->get_result()->fetch_assoc();
        $source->commit();
    }catch(Throwable$error){$source->rollback();throw$error;}
    $classification=LegacyObjectClassification::classify($classificationRow);$route=LegacyMigrationRoute::decide($classification);
    if($route['applyBlocked'])throw new DomainException('QUARANTINED_EVIDENCE');
    if($route['route']!=='operational_case_import')throw new DomainException('OPERATIONAL_ROUTE_NOT_ALLOWED');
    $eligible=$row!==null&&trim((string)$row['ordadr_address'])!==''&&trim((string)$row['entrance'])!==''&&trim((string)$row['regnumber'])!==''&&(string)$row['workdatestart']>='2026-10-01'&&$row['plan_finish_date']!==null&&!str_starts_with((string)$row['plan_finish_date'],'0000-00-00');
    if(!$eligible)throw new DomainException('OPERATIONAL_CASE_NOT_ELIGIBLE');
    $base=['ok'=>true,'mode'=>$dryRun?'dry-run':'apply','selected'=>[(int)$selectedId],'route'=>$route['route'],'classification'=>$classification,'sourceCutoff'=>$cutoff];
    if($dryRun){echo json_encode($base,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),PHP_EOL;exit(0);}
    $target=$targetConnection();$exists=$target->query("SELECT id FROM `{$legacyPrefix}fm_maintable` WHERE id=".(int)$selectedId)->fetch_assoc()!==null;
    if(!$exists){$insert=$target->prepare("INSERT INTO `{$legacyPrefix}fm_maintable`(id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,workdatefinish,ptoactdate,responsstroicontrol) VALUES(?,?,?,?,?,?,?,?,?,?)");$values=array_map(static fn($v):?string=>$v===null?null:(string)$v,[$row['ordadr_address'],$row['entrance'],$row['regnumber'],$row['workdatestart'],$row['workdateendadjusted'],$row['plan_finish_date'],$row['workdatefinish'],$row['ptoactdate'],$row['responsstroicontrol']]);$insert->bind_param('isssssssss',$selectedId,...$values);$insert->execute();}
    $importer=new PilotCaseImporter($target,$processPrefix,$legacyPrefix);$importer->assertSchemaAvailable();$import=$importer->import([(int)$selectedId],gmdate('Y-m-d\TH:i:sP'));if(isset($import['rejected']))throw new DomainException('OPERATIONAL_CASE_NOT_ELIGIBLE');
    $case=$target->query("SELECT id FROM `{$processPrefix}fm2_installation_cases` WHERE legacy_installation_object_id=".(int)$selectedId)->fetch_assoc();if($case===null)throw new RuntimeException('Imported case missing');
    $proof=(new MigrationClassificationProvenanceTarget($target,$processPrefix))->reconcile('operational_case',(int)$selectedId,(int)$case['id'],$cutoff,$classification,gmdate('Y-m-d H:i:s'));
    echo json_encode($base+['copied'=>!$exists,'imported'=>$import['imported'],'alreadyPresent'=>$import['alreadyPresent']]+$proof,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),PHP_EOL;exit(0);
}

$target=$targetConnection();
$target->set_charset('utf8mb4');

$existing = [];
$existingResult = $target->query("SELECT id FROM `{$legacyPrefix}fm_maintable`");
foreach ($existingResult->fetch_all(MYSQLI_ASSOC) as $row) $existing[(int) $row['id']] = true;

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
}
$source->commit();
if ($selected === []) {
    $caseCount = (int) $target->query("SELECT COUNT(*) AS n FROM `{$processPrefix}fm2_installation_cases`")->fetch_assoc()['n'];
    echo json_encode(['ok'=>true,'copied'=>0,'imported'=>0,'queueCases'=>$caseCount,'reason'=>'NO_NEW_ELIGIBLE_OBJECTS'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),PHP_EOL;
    exit(0);
}

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
