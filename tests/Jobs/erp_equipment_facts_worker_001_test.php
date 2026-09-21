<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/EquipmentFactsFixture.php';

use FMonitor2\InstallationProcess\JobsSchemaMigration;
use FMonitor2\Jobs\{MariaDbEquipmentFactsScheduler, MariaDbJobQueue};

$root = dirname(__DIR__, 2);
$handler = $root . '/bin/fmonitor2-job-handler.php';
assertSameValue(true, is_file($handler), 'ERP-EQUIPMENT-FACTS-001 public handler executable exists');

/** @return array{int,string,string} */
function erpWorkerHandler(string $handler, string $root, array $job, array $override = [], array $remove = []): array
{
    $pipes = [];
    $environment = array_replace(getenv(), [
        'FMONITOR_DB_HOST' => '127.0.0.1',
        'FMONITOR_DB_PORT' => '1',
        'FMONITOR_DB_NAME' => 'unreachable',
        'FMONITOR_DB_USER' => 'none',
        'FMONITOR_DB_PASSWORD' => 'none',
        'FMONITOR_PROCESS_TABLE_PREFIX' => 'erp_worker_',
        'FMONITOR_ERP_HOST' => 'erp.invalid',
        'FMONITOR_ERP_DATABASE' => '1c-erp',
        'FMONITOR_ERP_USER' => 'reader',
        'FMONITOR_ERP_PASSWORD' => 'synthetic-not-a-secret',
        'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY' => str_repeat('a', 64),
        'FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS' => '500',
        'FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS' => '5',
        'FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE' => '100',
    ], $override);
    foreach ($remove as $name) unset($environment[$name]);
    $process = proc_open([PHP_BINARY, $handler], [
        0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes, $root, $environment);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE ERP handler process');
    fwrite($pipes[0], json_encode($job, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), trim($stdout), trim($stderr)];
}

$job = [
    'jobId' => 812,
    'jobIdentity' => '12121212-1212-4212-8212-121212121212',
    'jobType' => 'erp.equipment-facts.sync',
    'payloadVersion' => 1,
    'payload' => ['scheduleSlot' => '2026-09-21T12'],
    'attempt' => 1,
    'leaseToken' => str_repeat('b', 64),
    'leasedAtUtc' => '2026-09-21T09:00:00.000000Z',
    'leaseExpiresAtUtc' => '2026-09-21T09:05:00.000000Z',
];

[$exit, $stdout, $stderr] = erpWorkerHandler($handler, $root, $job);
assertSameValue('', $stderr, 'handler writes no connection details to stderr');
$result = json_decode($stdout, true, 32, JSON_THROW_ON_ERROR);
assertSameValue(false, $exit === 64 || ($result['error'] ?? null) === 'CONFIGURATION_INVALID',
    'INTENDED_RED: exact ERP v1 claim reaches its handler instead of claim rejection');
assertSameValue(['retryable', 'ERP_EQUIPMENT_FACTS_SYNC_FAILED'],
    [$result['status'] ?? null, $result['failureCode'] ?? null],
    'structurally valid but unreachable ERP becomes safe retryable outcome');
$serialized = json_encode($result, JSON_THROW_ON_ERROR);
foreach (['erp.invalid', 'synthetic-not-a-secret', 'SELECT ', '[1c-erp]', 'sourceOrderNumber'] as $forbidden) {
    assertSameValue(false, str_contains($serialized, $forbidden), 'handler receipt excludes ' . $forbidden);
}

$unknown = $job;
$unknown['payloadVersion'] = 2;
[$unknownExit, $unknownOut, $unknownErr] = erpWorkerHandler($handler, $root, $unknown);
assertSameValue([64, '', ['ok' => false, 'error' => 'CONFIGURATION_INVALID']],
    [$unknownExit, $unknownErr, json_decode($unknownOut, true, 32, JSON_THROW_ON_ERROR)],
    'unknown ERP version is rejected before source access');
foreach (['FMONITOR_ERP_PASSWORD', 'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY'] as $missingName) {
    [$invalidExit, $invalidOut, $invalidErr] = erpWorkerHandler($handler, $root, $job, [], [$missingName]);
    assertSameValue([64, '', ['ok' => false, 'error' => 'CONFIGURATION_INVALID']],
        [$invalidExit, $invalidErr, json_decode($invalidOut, true, 32, JSON_THROW_ON_ERROR)],
        'public handler process fails fast before restart-loop when missing ' . $missingName);
}
foreach ([
    'FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS' => '0',
    'FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS' => '0',
    'FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE' => '0',
] as $name => $invalidValue) {
    [$invalidExit, $invalidOut, $invalidErr] = erpWorkerHandler($handler, $root, $job, [$name => $invalidValue]);
    assertSameValue([64, '', ['ok' => false, 'error' => 'CONFIGURATION_INVALID']],
        [$invalidExit, $invalidErr, json_decode($invalidOut, true, 32, JSON_THROW_ON_ERROR)],
        'public handler process fails fast before restart-loop for invalid ' . $name);
}

$fixture = new EquipmentFactsFixture('worker');
try {
    $fixture->migrate();
    JobsSchemaMigration::apply($fixture->db, $fixture->p);
    $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
    $scheduler = new MariaDbEquipmentFactsScheduler($fixture->db, $fixture->p);
    $scheduled = $scheduler->tick($now);
    $repeat = $scheduler->tick($now);
    assertSameValue([true, false, $scheduled['jobId']], [$scheduled['created'], $repeat['created'], $repeat['jobId']],
        'current-slot startup tick creates exactly one durable ERP job');

    $environment = array_replace(getenv(), [
        'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'FMONITOR_DB_PORT' => (string) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
        'FMONITOR_DB_NAME' => $fixture->name,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        'FMONITOR_PROCESS_TABLE_PREFIX' => $fixture->p,
        'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY' => EquipmentFactsFixture::HMAC_KEY,
        'FMONITOR_TEST_ERP_HANDLER_MODE' => 'completed',
    ]);
    $pipes = [];
    $worker = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/erp_equipment_facts_worker_process.php'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $environment);
    if (!is_resource($worker)) throw new TestFailure('SETUP_FAILURE worker process');
    $deadline = microtime(true) + 10;
    do {
        $status = $fixture->db->query("SELECT status FROM {$fixture->p}fm2_jobs WHERE job_id=" . (int) $scheduled['jobId'])->fetch_column();
        if ($status === 'completed') break;
        usleep(50_000);
    } while (microtime(true) < $deadline);
    proc_terminate($worker, SIGTERM);
    foreach ([1, 2] as $fd) { stream_get_contents($pipes[$fd]); fclose($pipes[$fd]); }
    proc_close($worker);
    assertSameValue('completed', $status, 'scheduler current-slot job executes through real worker/claim/handler');

    $queue = new MariaDbJobQueue($fixture->db, $fixture->p, null, null, ['erp.equipment-facts.sync' => [1]]);
    $failureJob = $queue->enqueue([
        'jobType' => 'erp.equipment-facts.sync', 'payloadVersion' => 1,
        'payload' => ['scheduleSlot' => '2026-09-21T13'], 'availableAtUtc' => $now,
        'idempotencyKey' => '43434343-4343-4343-8343-434343434343',
        'actor' => ['type' => 'system', 'id' => 'erp-equipment-facts-hourly-v1'],
    ]);
    $environment['FMONITOR_TEST_ERP_HANDLER_MODE'] = 'failed';
    $pipes = [];
    $worker = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/erp_equipment_facts_worker_process.php'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $environment);
    if (!is_resource($worker)) throw new TestFailure('SETUP_FAILURE retry worker process');
    $deadline = microtime(true) + 10;
    do {
        $row = $fixture->db->query("SELECT status,failure_code,attempt FROM {$fixture->p}fm2_jobs WHERE job_id=" . (int) $failureJob['jobId'])->fetch_assoc();
        $run = $fixture->db->query("SELECT status,reason FROM {$fixture->p}fm2_equipment_fact_runs WHERE run_id='42424242-4242-4242-8242-424242424242'")->fetch_assoc();
        if (($row['status'] ?? null) === 'ready' && (int) ($row['attempt'] ?? 0) === 1 && $run !== null) break;
        usleep(50_000);
    } while (microtime(true) < $deadline);
    proc_terminate($worker, SIGTERM);
    foreach ([1, 2] as $fd) { stream_get_contents($pipes[$fd]); fclose($pipes[$fd]); }
    proc_close($worker);
    assertSameValue(['ready', 'ERP_EQUIPMENT_FACTS_SYNC_FAILED', 1, 'failed', 'SOURCE_UNAVAILABLE'],
        [$row['status'] ?? null, $row['failure_code'] ?? null, (int) ($row['attempt'] ?? 0), $run['status'] ?? null, $run['reason'] ?? null],
        'SOURCE_UNAVAILABLE persists safe failed run and schedules retry');
} finally { $fixture->close(); }

echo "PASS: ERP-EQUIPMENT-FACTS-001 exact worker/handler claim\n";
