<?php

declare(strict_types=1);

function detailEnv(string $name): string
{
    $value = getenv($name);
    if ($value === false || $value === '') throw new RuntimeException("Missing {$name}");
    return $value;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$manifest = json_decode((string) file_get_contents(detailEnv('FMONITOR_PILOT_ACTIVE_MANIFEST')), true, flags: JSON_THROW_ON_ERROR);
$prefix = (string) ($manifest['processPrefix'] ?? '');
if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new RuntimeException('Invalid process prefix');

$source = new mysqli(
    getenv('FMONITOR_SOURCE_HOST') ?: '127.0.0.1',
    detailEnv('FMONITOR_SOURCE_USER'),
    detailEnv('FMONITOR_SOURCE_PASSWORD'),
    getenv('FMONITOR_SOURCE_NAME') ?: 'c1_fmonitor',
    (int) (getenv('FMONITOR_SOURCE_PORT') ?: '13306'),
);
$source->set_charset('utf8mb4');
$target = new mysqli('127.0.0.1', 'fmonitor2_demo', 'fmonitor2_demo_local', 'fmonitor2_demo', 23306);
$target->set_charset('utf8mb4');

$ids = [];
foreach ($target->query("SELECT legacy_installation_object_id FROM `{$prefix}fm2_installation_cases` ORDER BY legacy_installation_object_id")->fetch_all(MYSQLI_ASSOC) as $row) $ids[] = (int) $row['legacy_installation_object_id'];
if ($ids === []) throw new RuntimeException('No pilot objects');

$metadata = [];
$metaSql = "SELECT f.id,f.sysname,COALESCE(NULLIF(vf.showname,''),f.name) label,f.type FROM fm_fields f LEFT JOIN fm_view_fields vf ON vf.fields_id=f.id AND vf.views_id=4 AND vf.status=1 ORDER BY f.id";
foreach ($source->query($metaSql)->fetch_all(MYSQLI_ASSOC) as $row) $metadata[(string) $row['sysname']] = $row;

$physical = [];
foreach ($source->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fm_maintable'")->fetch_all(MYSQLI_ASSOC) as $row) $physical[(string) $row['COLUMN_NAME']] = true;
$wanted = ['area','district','zavnumber','floors','weight','speed','paired','pittype','pitmaterial','doorcabin_type','typepitdoor','workdatestart','workdatefinish','workdatestartadjusted','workdateendadjusted','plan_finish_date','equipmentproduced','equipmentdeliverydate','equiponobject','measurements','factworkstartdate','generalcontractor','subsuplier','respperson','contact_phone','headofconstructarea','contact_phone_headofconstruct','responsstroicontrol','contact_phone_itn','openingactuploaded','openingactverified','siteplanuploaded','siteplanverified','transferactsign','transferactdate','transferactdeliverdate','transferactstatus','acttransfertoulhdate','transfer_act_uploaded','transferactverified','ptoactdate','non_conformance_act_date','declarations','contractor_docs_transfer_date','object_status','control_flag','comments','sm_comment'];
$wanted = array_values(array_filter($wanted, static fn(string $field): bool => isset($physical[$field])));

$dictionary = [];
foreach ($source->query('SELECT field_id,id,name FROM fm_fields_values')->fetch_all(MYSQLI_ASSOC) as $row) $dictionary[(int) $row['field_id']][(string) $row['id']] = (string) $row['name'];
$users = [];
foreach ($source->query('SELECT id,name FROM users')->fetch_all(MYSQLI_ASSOC) as $row) $users[(string) $row['id']] = (string) $row['name'];

$idList = implode(',', $ids);
$select = implode(',', array_map(static fn(string $field): string => "`{$field}`", $wanted));
$rows = $source->query("SELECT id,{$select} FROM fm_maintable WHERE id IN ({$idList}) ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);

$target->query("CREATE TABLE IF NOT EXISTS `{$prefix}fm2_pilot_object_details` (object_id INT NOT NULL PRIMARY KEY,payload_json LONGTEXT NOT NULL,source_updated_at VARCHAR(40) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$upsert = $target->prepare("INSERT INTO `{$prefix}fm2_pilot_object_details`(object_id,payload_json,source_updated_at) VALUES(?,?,?) ON DUPLICATE KEY UPDATE payload_json=VALUES(payload_json),source_updated_at=VALUES(source_updated_at)");
foreach ($rows as $row) {
    $fields = [];
    foreach ($wanted as $field) {
        $meta = $metadata[$field] ?? ['id' => 0, 'label' => $field, 'type' => 0];
        $raw = trim((string) ($row[$field] ?? ''));
        $display = $raw;
        if ($raw === '' || preg_match('/^0000-00-00/', $raw) === 1) $display = '';
        elseif ((int) $meta['type'] === 4) $display = $dictionary[(int) $meta['id']][$raw] ?? $raw;
        elseif ((int) $meta['type'] === 10) $display = $users[$raw] ?? $raw;
        elseif ((int) $meta['type'] === 11) $display = $raw === '1' ? 'Да' : 'Нет';
        elseif ((int) $meta['type'] === 5 && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $date) === 1) $display = "{$date[3]}.{$date[2]}.{$date[1]}";
        $fields[$field] = ['label' => (string) $meta['label'], 'display' => $display];
    }
    $payload = json_encode(['objectId' => (int) $row['id'], 'fields' => $fields], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $id = (int) $row['id'];
    $upsert->bind_param('iss', $id, $payload, $now);
    $upsert->execute();
}

echo json_encode(['ok' => true, 'objects' => count($rows), 'fields' => count($wanted)], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
