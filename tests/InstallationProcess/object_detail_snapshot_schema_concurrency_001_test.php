<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigrationVerification;

// OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4: causally observed two-creator race.
function odcStart(string $database, string $prefix, string $token, string $mode): array
{
    $process = proc_open([PHP_BINARY, dirname(__DIR__).'/Support/object_detail_schema_concurrency_worker.php', $database, $prefix, $token, $mode], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: worker launch');
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    return ['process'=>$process,'pipes'=>$pipes,'buffer'=>'','stderr'=>'','bytes'=>0,'exit'=>null];
}
function odcRead(array &$child, float $seconds = 8): string
{
    $deadline = hrtime(true) + (int)($seconds * 1e9);
    while (!str_contains($child['buffer'], "\n")) {
        foreach ([1,2] as $fd) {
            $chunk = fread($child['pipes'][$fd], min(4096, 8193 - $child['bytes']));
            if ($chunk === false) throw new TestFailure('SETUP_FAILURE: worker pipe');
            $child['bytes'] += strlen($chunk);
            $child[$fd === 1 ? 'buffer' : 'stderr'] .= $chunk;
            if ($child['bytes'] > 8192) throw new TestFailure('SETUP_FAILURE: worker output bound');
        }
        if ($child['bytes'] > 8192 || $child['stderr'] !== '') throw new TestFailure('SETUP_FAILURE: worker protocol/output');
        if (hrtime(true) >= $deadline || (feof($child['pipes'][1]) && !str_contains($child['buffer'], "\n"))) throw new TestFailure('SETUP_FAILURE: worker deadline/EOF');
        usleep(1000);
    }
    $at = strpos($child['buffer'], "\n") + 1;
    $line = substr($child['buffer'], 0, $at);
    $child['buffer'] = substr($child['buffer'], $at);
    return $line;
}
function odcStop(array &$child, bool $expectSuccess = false): void
{
    if (!is_resource($child['process'])) return;
    try {
        if ($expectSuccess) {
            $deadline = hrtime(true) + 3_000_000_000;
            do {
                $status = proc_get_status($child['process']);
                if (!$status['running']) { $child['exit'] = $status['exitcode']; break; }
                usleep(1000);
            } while (hrtime(true) < $deadline);
        }
    } finally {
        $status = proc_get_status($child['process']);
        if ($status['running']) {
            proc_terminate($child['process'], 15);
            $deadline = hrtime(true) + 1_000_000_000;
            do { usleep(1000); $status = proc_get_status($child['process']); }
            while ($status['running'] && hrtime(true) < $deadline);
            if ($status['running']) proc_terminate($child['process'], 9);
        }
        foreach ([1,2] as $fd) {
            if ($child['bytes'] > 8192) break;
            $tail = stream_get_contents($child['pipes'][$fd], 8193 - $child['bytes']);
            if (is_string($tail)) {
                $child['bytes'] += strlen($tail);
                $child[$fd === 1 ? 'buffer' : 'stderr'] .= $tail;
            }
        }
        foreach ($child['pipes'] as $pipe) if (is_resource($pipe)) fclose($pipe);
        $code = proc_close($child['process']);
        $child['process'] = null;
        if ($child['exit'] === null || $child['exit'] < 0) $child['exit'] = $code;
    }
    if ($child['bytes'] > 8192) throw new TestFailure('SETUP_FAILURE: worker final output bound');
    if ($expectSuccess) assertSameValue([0,'','',true], [$child['exit'],$child['buffer'],$child['stderr'],$child['bytes'] <= 8192], 'worker exits with exact bounded protocol only');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$admin = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local', '', (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
$database = 'fm2_ods_concurrent_' . bin2hex(random_bytes(6));
$created = false;
$children = [];
try {
    $admin->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $admin->select_db($database);
    echo "PREREQUISITE PASS: isolated schema database\n";
    assertSameValue(true, class_exists(ObjectDetailSnapshotSchemaMigrationVerification::class), 'INTENDED_RED: required real migration observer seam absent');
    $tokenA = bin2hex(random_bytes(6));
    $tokenB = bin2hex(random_bytes(6));
    $tokenC = bin2hex(random_bytes(6));
    $children['a'] = odcStart($database, 'race_', $tokenA, 'hold');
    $start = odcRead($children['a']);
    assertSameValue(1, preg_match('/^START '. $tokenA .' ([1-9][0-9]*)\n$/D', $start, $match), 'A started with real connection identity');
    $aId = (int)$match[1];
    assertSameValue("READY {$tokenA}\n", odcRead($children['a']), 'A reached acquired-lock barrier');
    $lock = hash('sha256', "object-detail-schema-v1\0{$database}\0race_");
    assertSameValue($aId, (int)$admin->query("SELECT IS_USED_LOCK('{$lock}')")->fetch_column(), 'independent A ownership proof');
    $children['c'] = odcStart($database, 'other_', $tokenC, 'normal');
    assertSameValue(1, preg_match('/^START '. $tokenC .' [1-9][0-9]*\n$/D', odcRead($children['c'])), 'independent namespace caller starts');
    $result = static fn(string $prefix, bool $applied): string => 'RESULT '.json_encode(['applied'=>$applied,'schemaVersion'=>12,'tablesCreated'=>$applied ? [$prefix.'fm2_pilot_object_detail_quarantine',$prefix.'fm2_pilot_object_details'] : []], JSON_THROW_ON_ERROR)."\n";
    assertSameValue($result('other_', true), odcRead($children['c']), 'other namespace completes while A holds race lock');
    odcStop($children['c'], true);
    assertSameValue($aId, (int)$admin->query("SELECT IS_USED_LOCK('{$lock}')")->fetch_column(), 'A still holds its namespace lock');
    $children['b'] = odcStart($database, 'race_', $tokenB, 'normal');
    assertSameValue(1, preg_match('/^START '. $tokenB .' ([1-9][0-9]*)\n$/D', odcRead($children['b']), $match), 'B starts after A READY');
    $bId = (int)$match[1];
    assertSameValue(true, $aId !== $bId, 'two real distinct creators');
    $waiting = false;
    $deadline = hrtime(true) + 3_000_000_000;
    do {
        $row = $admin->query("SELECT STATE,INFO FROM information_schema.PROCESSLIST WHERE ID={$bId}")->fetch_assoc();
        $waiting = is_array($row) && stripos((string)$row['STATE'], 'lock') !== false && str_contains((string)$row['INFO'], $lock) && str_contains((string)$row['INFO'], 'GET_LOCK');
        if ($waiting) break;
        usleep(1000);
    } while (hrtime(true) < $deadline);
    assertSameValue(true, $waiting, 'B is independently observed waiting on the exact named lock');
    assertSameValue(0, (int)$admin->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('race_fm2_pilot_object_details','race_fm2_pilot_object_detail_quarantine')")->fetch_column(), 'neither race creator writes before A release');
    assertSameValue(strlen("RELEASE {$tokenA}\n"), fwrite($children['a']['pipes'][0], "RELEASE {$tokenA}\n"), 'bounded release write');
    fflush($children['a']['pipes'][0]);
    assertSameValue($result('race_', true), odcRead($children['a']), 'A creates exactly both tables');
    assertSameValue($result('race_', false), odcRead($children['b']), 'B sees exact compatible repeat');
    odcStop($children['a'], true);
    odcStop($children['b'], true);
    assertSameValue(['other_fm2_pilot_object_detail_quarantine','other_fm2_pilot_object_details','race_fm2_pilot_object_detail_quarantine','race_fm2_pilot_object_details'], array_column($admin->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME'), 'exact final family inventory');
    assertSameValue(1, (int)$admin->query("SELECT IS_FREE_LOCK('{$lock}')")->fetch_column(), 'race lock released after both creators');
    foreach (['race_', 'other_'] as $checkPrefix) {
        foreach (['fm2_pilot_object_details','fm2_pilot_object_detail_quarantine'] as $member) {
            assertSameValue(0, (int)$admin->query('SELECT COUNT(*) FROM ' . $checkPrefix . $member)->fetch_column(), 'all concurrent-created tables remain data-free');
        }
    }
    echo "PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 causal two-creator serialization and namespace independence\n";
} finally {
    try {
        $cleanupError = null;
        foreach ($children as &$child) {
            try { odcStop($child); }
            catch (Throwable $error) { $cleanupError ??= $error; }
        }
        unset($child);
        if ($cleanupError !== null) throw $cleanupError;
    }
    finally {
        try { if ($created) $admin->query("DROP DATABASE `{$database}`"); }
        finally { $admin->close(); }
    }
}
