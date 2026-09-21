<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/EquipmentFactsFixture.php';

use FMonitor2\InstallationProcess\JobsSchemaMigration;
use FMonitor2\Jobs\MariaDbJobQueue;

function erpReadinessCli(string $root, array $environment, string $mode = 'process-health'): array
{
    $pipes = [];
    $process = proc_open([PHP_BINARY, $root . '/bin/fmonitor2-jobs.php', $mode], [
        0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes, $root, $environment);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE jobs health process');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($process), trim($stdout), trim($stderr)];
}

$root = dirname(__DIR__, 2);
$fixture = new EquipmentFactsFixture('readiness');
try {
    JobsSchemaMigration::apply($fixture->db, $fixture->p);
    $environment = array_replace(getenv(), [
        'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'FMONITOR_DB_PORT' => (string) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
        'FMONITOR_DB_NAME' => $fixture->name,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        'FMONITOR_PROCESS_TABLE_PREFIX' => $fixture->p,
        'FMONITOR_SESSION_INSTANCE' => 'erp-readiness',
        'FMONITOR_ERP_HOST' => 'erp.example.invalid',
        'FMONITOR_ERP_DATABASE' => '1c-erp',
        'FMONITOR_ERP_USER' => 'reader',
        'FMONITOR_ERP_PASSWORD' => 'synthetic-password',
        'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY' => str_repeat('h', 40),
        'FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS' => '7',
        'FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS' => '500',
        'FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE' => '100',
    ]);
    [$exit, $out, $err] = erpReadinessCli($root, $environment);
    $missing = json_decode($out, true, 32, JSON_THROW_ON_ERROR);
    assertSameValue([70, '', false, ['stale_scheduler', 'stale_worker']],
        [$exit, $err, $missing['ok'] ?? null, $missing['reasons'] ?? null],
        'public readiness is non-GREEN while scheduler and worker are missing');

    $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
    $fixture->db->query("INSERT INTO {$fixture->p}fm2_worker_heartbeats VALUES"
        . "('worker:erp-readiness','{$now}'),('scheduler:erp-readiness','{$now}')");
    [$exit, $out, $err] = erpReadinessCli($root, $environment);
    assertSameValue([0, '', true], [$exit, $err, json_decode($out, true, 32, JSON_THROW_ON_ERROR)['ok'] ?? null],
        'public readiness is GREEN for fresh scheduler and worker');
    $queue = new MariaDbJobQueue($fixture->db, $fixture->p, static fn(): string => $now,
        static fn(): string => str_repeat('7', 64), ['erp.equipment-facts.sync' => [1]]);
    $dead = $queue->enqueue(['jobType' => 'erp.equipment-facts.sync', 'payloadVersion' => 1,
        'payload' => ['scheduleSlot' => '2026-09-21T10'], 'availableAtUtc' => $now,
        'idempotencyKey' => '72727272-7272-4272-8272-727272727272',
        'actor' => ['type' => 'system', 'id' => 'fixture']]);
    $lease = $queue->claim(['workerId' => 'fixture-worker', 'batch' => 1])[0];
    $queue->fail(['jobId' => $dead['jobId'], 'leaseToken' => $lease['leaseToken'],
        'failureCode' => 'FIXTURE_DEAD', 'retryable' => false]);
    [$processExit, $processOut] = erpReadinessCli($root, $environment);
    [$operatorExit, $operatorOut] = erpReadinessCli($root, $environment, 'health');
    assertSameValue([0, true, 70, false, true], [$processExit, json_decode($processOut, true, 32, JSON_THROW_ON_ERROR)['ok'] ?? null,
        $operatorExit, json_decode($operatorOut, true, 32, JSON_THROW_ON_ERROR)['ok'] ?? null,
        in_array('dead_jobs', json_decode($operatorOut, true, 32, JSON_THROW_ON_ERROR)['reasons'] ?? [], true)],
        'historical dead job remains operator-visible without poisoning repaired process readiness');

    foreach ([['scheduler:erp-readiness', 'stale_scheduler'], ['worker:erp-readiness', 'stale_worker']] as [$identity, $reason]) {
        $fixture->db->query("DELETE FROM {$fixture->p}fm2_worker_heartbeats WHERE worker_id='{$identity}'");
        [$exit, $out, $err] = erpReadinessCli($root, $environment);
        $result = json_decode($out, true, 32, JSON_THROW_ON_ERROR);
        assertSameValue([70, '', false, true], [$exit, $err, $result['ok'] ?? null, in_array($reason, $result['reasons'] ?? [], true)],
            'public readiness exposes allowlisted missing component ' . $reason);
        $fixture->db->query("INSERT INTO {$fixture->p}fm2_worker_heartbeats VALUES('{$identity}','{$now}')");
    }

    $invalid = $environment;
    unset($invalid['FMONITOR_ERP_PASSWORD']);
    [$exit, $out, $err] = erpReadinessCli($root, $invalid);
    assertSameValue([64, '', ['ok' => false, 'error' => 'CONFIGURATION_INVALID']],
        [$exit, $err, json_decode($out, true, 32, JSON_THROW_ON_ERROR)],
        'INTENDED_RED: public jobs readiness fails fast for missing ERP configuration');
} finally { $fixture->close(); }

echo "PASS: ERP-EQUIPMENT-FACTS-001 behavioral jobs readiness\n";
