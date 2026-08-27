<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function envValue(string $name, ?string $default = null): string
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        if ($default !== null) {
            return $default;
        }
        throw new RuntimeException("Required environment variable {$name} is missing");
    }
    return $value;
}

function connectDb(string $prefix, array $defaults = []): mysqli
{
    $db = new mysqli(
        envValue("{$prefix}_HOST", $defaults['host'] ?? null),
        envValue("{$prefix}_USER", $defaults['user'] ?? null),
        envValue("{$prefix}_PASSWORD", $defaults['password'] ?? null),
        envValue("{$prefix}_NAME", $defaults['name'] ?? null),
        (int) envValue("{$prefix}_PORT", $defaults['port'] ?? '3306')
    );
    $db->set_charset('utf8mb4');
    return $db;
}

function nullableText(mixed $value): ?string
{
    $text = trim((string) $value);
    return ($text === '' || $text === '0') ? null : $text;
}

function positiveInt(mixed $value): ?int
{
    $number = (int) $value;
    return $number > 0 ? $number : null;
}

function positiveDecimal(mixed $value): ?float
{
    $number = (float) $value;
    return $number > 0 ? $number : null;
}

$source = connectDb('FMONITOR_SOURCE');
$target = connectDb('FMONITOR_DEMO', [
    'host' => '127.0.0.1',
    'port' => '23306',
    'user' => 'fmonitor2_demo',
    'password' => 'fmonitor2_demo_local',
    'name' => 'fmonitor2_demo',
]);

$cutoff = envValue('FMONITOR_IMPORT_PLANNED_FROM', '2026-10-01');
$limit = max(1, min(500, (int) envValue('FMONITOR_IMPORT_LIMIT', '170')));
$sourceCutoff = (new DateTimeImmutable())->format('Y-m-d H:i:s');

$target->begin_transaction();
try {
    $batch = $target->prepare(
        "INSERT INTO fm2_import_batches
         (source_system, source_cutoff_at, started_at, status, notes)
         VALUES ('legacy_fmonitor', ?, NOW(), 'running', ?)"
    );
    $notes = "Unopened orders with planned start from {$cutoff}";
    $batch->bind_param('ss', $sourceCutoff, $notes);
    $batch->execute();
    $batchId = $target->insert_id;

    // This account is read-only. The source query is intentionally a single SELECT.
    $select = $source->prepare(
        "SELECT * FROM fm_maintable
         WHERE factworkstartdate = '0000-00-00 00:00:00'
           AND workdatestart >= ?
           AND object_status <> 259
         ORDER BY workdatestart, id
         LIMIT ?"
    );
    $select->bind_param('si', $cutoff, $limit);
    $select->execute();
    $rows = $select->get_result();

    $objectUpsert = $target->prepare(
        "INSERT INTO fm2_objects
         (legacy_order_id, order_number, unom, address_text, entrance, district,
          administrative_okrug, source_updated_at, imported_at, import_batch_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, '0000-00-00 00:00:00'), NOW(), ?)
         ON DUPLICATE KEY UPDATE
           order_number=VALUES(order_number), unom=VALUES(unom),
           address_text=VALUES(address_text), entrance=VALUES(entrance),
           district=VALUES(district), administrative_okrug=VALUES(administrative_okrug),
           source_updated_at=VALUES(source_updated_at), imported_at=NOW(),
           import_batch_id=VALUES(import_batch_id), id=LAST_INSERT_ID(id)"
    );
    $specUpsert = $target->prepare(
        "INSERT INTO fm2_equipment_specs
         (object_id, factory_number, capacity_kg, floor_count, nominal_speed_mps,
          shaft_material, cabin_door_type, shaft_door_type)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE factory_number=VALUES(factory_number),
           capacity_kg=VALUES(capacity_kg), floor_count=VALUES(floor_count),
           nominal_speed_mps=VALUES(nominal_speed_mps), shaft_material=VALUES(shaft_material),
           cabin_door_type=VALUES(cabin_door_type), shaft_door_type=VALUES(shaft_door_type)"
    );
    $caseUpsert = $target->prepare(
        "INSERT INTO fm2_installation_cases
         (object_id, process_state, planned_start_date, planned_finish_date, created_at, updated_at)
         VALUES (?, 'needs_order', DATE(?), DATE(?), NOW(), NOW())
         ON DUPLICATE KEY UPDATE planned_start_date=VALUES(planned_start_date),
           planned_finish_date=VALUES(planned_finish_date), updated_at=NOW(), id=LAST_INSERT_ID(id)"
    );
    $taskInsert = $target->prepare(
        "INSERT INTO fm2_process_tasks
         (installation_case_id, task_type, assignee_role, due_date, task_status, created_at)
         SELECT ?, 'prepare_order', 'fkr', DATE_SUB(?, INTERVAL 14 DAY), 'open', NOW()
         WHERE NOT EXISTS (
           SELECT 1 FROM fm2_process_tasks
           WHERE installation_case_id=? AND task_type='prepare_order' AND task_status='open'
         )"
    );
    $auditInsert = $target->prepare(
        "INSERT INTO fm2_import_records
         (import_batch_id, legacy_order_id, object_id, import_status, raw_payload_json)
         VALUES (?, ?, ?, 'imported', ?)"
    );

    $imported = 0;
    while ($row = $rows->fetch_assoc()) {
        $legacyId = (int) $row['id'];
        $orderNumber = trim((string) $row['regnumber']);
        $address = trim((string) $row['ordadr_address']);
        if ($orderNumber === '' || $address === '') {
            throw new RuntimeException("Legacy order {$legacyId} has no registration number or address");
        }

        $unom = nullableText($row['unom']);
        $entrance = nullableText($row['entrance']);
        $district = nullableText($row['district']);
        $okrug = nullableText($row['area']);
        $updatedAt = (string) $row['last_ctime'];
        $objectUpsert->bind_param(
            'isssssssi',
            $legacyId, $orderNumber, $unom, $address, $entrance, $district,
            $okrug, $updatedAt, $batchId
        );
        $objectUpsert->execute();
        $objectId = $target->insert_id;

        $factoryNumber = nullableText($row['zavnumber']);
        $capacity = positiveInt($row['weight']);
        $floors = positiveInt($row['floors']);
        $speed = positiveDecimal($row['speed']);
        $shaftMaterial = nullableText($row['pitmaterial']);
        $cabinDoor = nullableText($row['doorcabin_type']);
        $shaftDoor = nullableText($row['typepitdoor']);
        $specUpsert->bind_param(
            'isiidsss', $objectId, $factoryNumber, $capacity, $floors, $speed,
            $shaftMaterial, $cabinDoor, $shaftDoor
        );
        $specUpsert->execute();

        $plannedStart = (string) $row['workdatestart'];
        $plannedFinish = (string) $row['workdatefinish'];
        $caseUpsert->bind_param('iss', $objectId, $plannedStart, $plannedFinish);
        $caseUpsert->execute();
        $caseId = $target->insert_id;

        $taskInsert->bind_param('isi', $caseId, $plannedStart, $caseId);
        $taskInsert->execute();

        $rawJson = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $auditInsert->bind_param('iiis', $batchId, $legacyId, $objectId, $rawJson);
        $auditInsert->execute();
        $imported++;
    }

    $finish = $target->prepare(
        "UPDATE fm2_import_batches
         SET finished_at=NOW(), imported_count=?, status='completed'
         WHERE id=?"
    );
    $finish->bind_param('ii', $imported, $batchId);
    $finish->execute();
    $target->commit();
    fwrite(STDOUT, "Imported {$imported} unopened orders into batch {$batchId}.\n");
} catch (Throwable $error) {
    $target->rollback();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
