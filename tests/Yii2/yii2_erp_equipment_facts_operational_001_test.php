<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/PreopeningFixture.php';

use FMonitor2\InstallationProcess\{EquipmentFactsApplication, EquipmentFactsSchemaMigration};

function erpManualCli(string $root, array $environment): array
{
    $pipes = [];
    $process = proc_open([PHP_BINARY, $root . '/bin/yii', 'erp-equipment-facts-sync/run', '--interactive=0'], [
        0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes, $root, $environment);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE ERP manual CLI process');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($process), trim($stdout), trim($stderr)];
}

$root = dirname(__DIR__, 2);
$fixture = null;
try {
    $fixture = new PreopeningFixture($root);
    EquipmentFactsSchemaMigration::apply($fixture->db, $fixture->p);
    $fixture->db->query("UPDATE {$fixture->p}fm_maintable SET zavnumber='ORD-4512' WHERE id=4512");
    $environment = array_replace(getenv(), $fixture->environment(), [
        'FMONITOR_ERP_HOST' => '127.0.0.1',
        'FMONITOR_ERP_DATABASE' => '1c-erp',
        'FMONITOR_ERP_USER' => 'reader',
        'FMONITOR_ERP_PASSWORD' => 'synthetic-unreachable-password',
        'FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY' => str_repeat('m', 40),
        'FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS' => '1',
        'FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS' => '500',
        'FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE' => '100',
    ]);
    [$exit, $out, $err] = erpManualCli($root, $environment);
    $failed = json_decode($out, true, 32, JSON_THROW_ON_ERROR);
    assertSameValue([1, '', 'failed', 'SOURCE_UNAVAILABLE'],
        [$exit, $err, $failed['status'] ?? null, $failed['reason'] ?? null],
        'INTENDED_RED: public manual command uses direct env and records safe source failure');
    assertSameValue(1, (int) $fixture->db->query("SELECT COUNT(*) FROM {$fixture->p}fm2_equipment_fact_runs WHERE run_id='"
        . $fixture->db->real_escape_string((string) ($failed['runId'] ?? '')) . "' AND status='failed' AND reason='SOURCE_UNAVAILABLE'")->fetch_column(),
        'INTENDED_RED: manual source failure is persisted through EquipmentFactsApplication');
    $safe = json_encode($failed, JSON_THROW_ON_ERROR);
    foreach (['synthetic-unreachable-password', '127.0.0.1', 'SELECT ', '[1c-erp]', 'ORD-4512'] as $forbidden) {
        assertSameValue(false, str_contains($safe, $forbidden), 'manual receipt excludes ' . $forbidden);
    }

    $owner = new EquipmentFactsApplication($fixture->db, $fixture->p, str_repeat('m', 40));
    $owner->execute([
        'actor' => ['type' => 'system', 'id' => 'erp-equipment-facts-hourly-v1'],
        'kind' => 'complete', 'runId' => '61616161-6161-4161-8161-616161616161',
        'observedAtUtc' => '2026-09-21T10:00:00.000000Z',
        'records' => [[
            'sourceOrderNumber' => 'ORD-4512', 'readinessDate' => '2026-09-10',
            'firstShipmentDate' => '2026-09-11', 'fullShipmentDate' => '2026-09-12',
        ]],
    ]);
    $fixture->start(['FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY' => str_repeat('m', 40)]);
    $cookies = [];
    $fixture->login($cookies, 95);
    $card = $fixture->request('GET', '/pilot/objects/4512', [], $cookies);
    assertSameValue(200, $card['status'], 'authorized object card remains available after manual/hourly runs');
    foreach (['10.09.2026', '11.09.2026', '12.09.2026', '21.09.2026', '1С ERP'] as $expected) {
        assertSameValue(true, str_contains($card['body'], $expected), 'authorized card displays independent ERP fact ' . $expected);
    }
} finally { if ($fixture instanceof PreopeningFixture) $fixture->close(); }

echo "PASS: ERP-EQUIPMENT-FACTS-001 public manual command and authorized card\n";
