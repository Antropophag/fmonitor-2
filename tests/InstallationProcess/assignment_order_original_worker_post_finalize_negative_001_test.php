<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigration;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationDatabaseFixture;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationWorkerBootstrap;

// Gate 2 correction for Gate 5 finding 6: READY belongs only to the real
// AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT application callback.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$token = bin2hex(random_bytes(8));
$database = 't_aoou_barrier_' . $token;
$prefix = 'lease_';
$root = (realpath(sys_get_temp_dir()) ?: throw new RuntimeException('Synthetic temporary directory unavailable')) . '/aoou-barrier-negative-' . $token;
$private = $root . '/private';
$config = $root . '/config.json';
$passwordFile = $root . '/password';
$safeLog = $root . '/safe.log';
$admin = new mysqli($host, $user, $password, '', $port);
$db = null;
$process = null;
$pairs = $pipes = [];
$remove = static function (string $path) use (&$remove): void {
    if (is_file($path) || is_link($path)) { unlink($path); return; }
    if (!is_dir($path)) return;
    foreach (scandir($path) ?: [] as $entry) if ($entry !== '.' && $entry !== '..') $remove($path . '/' . $entry);
    rmdir($path);
};

try {
    assertSameValue(1, preg_match('/^t_aoou_barrier_[0-9a-f]{16}$/D', $database), 'Cleanup database is independently bounded.');
    assertSameValue(1, preg_match('#^' . preg_quote((realpath(sys_get_temp_dir()) ?: throw new RuntimeException('Synthetic temporary directory unavailable')), '#') . '/aoou-barrier-negative-[0-9a-f]{16}$#D', $root), 'Cleanup root is independently bounded.');
    $admin->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    mkdir($root, 0700); mkdir($private, 0700);
    file_put_contents($passwordFile, $password . "\n"); chmod($passwordFile, 0600);
    file_put_contents($safeLog, ''); chmod($safeLog, 0600);
    $db = new mysqli($host, $user, $password, $database, $port);
    $db->set_charset('utf8mb4');
    FMonitor2\InstallationProcess\ProductionProcessSchemaMigration::apply($db, $prefix);
    FMonitor2\InstallationProcess\IdentityAccessSchemaMigration::apply($db, $prefix);
    FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration::apply($db, $prefix);
    FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration::apply($db, $prefix);
    AssignmentOrderOriginalSchemaMigration::apply($db, $prefix);
    AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($db, $prefix);
    if (!class_exists(AssignmentOrderOriginalVerificationWorkerBootstrap::class)) throw new TestFailure('Worker bootstrap seam is absent.');

    $workerConfig = [
        'databaseDsn' => "host={$host};port={$port};database={$database};charset=utf8mb4",
        'databaseUser' => $user, 'databasePasswordFile' => $passwordFile, 'tablePrefix' => $prefix,
        'privateStorageRoot' => $private, 'safeLogFile' => $safeLog, 'clockUtc' => '2026-09-02T07:00:00Z',
        'rootIdSequenceCsv' => 'original-0056', 'revisionIdSequenceCsv' => 'revision-0056',
        'inspectorMode' => 'injected_passive', 'faultPoint' => null,
        'barrierEvent' => 'after_private_finalize_before_commit',
    ];
    file_put_contents($config, json_encode($workerConfig, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n"); chmod($config, 0600);
    for ($index = 0; $index < 4; ++$index) $pairs[] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    $process = proc_open(
        [PHP_BINARY, dirname(__DIR__) . '/Support/assignment_order_original_worker_entry.php', $config],
        [0 => ['file','/dev/null','r'], 1 => ['pipe','w'], 2 => ['pipe','w'], 3 => $pairs[0][1], 4 => $pairs[1][1], 5 => $pairs[2][1], 6 => $pairs[3][1]],
        $pipes, dirname(__DIR__, 2),
    );
    if (!is_resource($process)) throw new TestFailure('Worker process construction failed.');
    foreach ($pairs as $pair) fclose($pair[1]);
    $pdf = base64_encode("%PDF-1.4\n%%EOF\n");
    $command = [
        'requestId' => '00000000-0000-4000-8000-000000000560', 'mode' => 'initial',
        'installationCaseId' => 4512, 'assignmentOrderId' => 81,
        // This active fixture user is deliberately unauthorized for the exact
        // upload capability, so application execution must never finalize.
        'actorUserId' => 17, 'documentDate' => '2026-09-01', 'compositionConfirmed' => true,
        'rootOriginalId' => null, 'targetRevisionId' => null, 'expectedCurrentRevisionId' => null, 'correctionReason' => null,
        'upload' => ['bytesBase64' => $pdf, 'originalFilename' => 'must-not-finalize.pdf', 'declaredMediaType' => 'application/pdf'],
    ];
    fwrite($pairs[0][0], json_encode($command, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
    stream_socket_shutdown($pairs[0][0], STREAM_SHUT_WR);
    foreach ([$pairs[2][0], $pairs[3][0], $pipes[1], $pipes[2]] as $endpoint) stream_set_blocking($endpoint, false);
    $barrier = $result = $stdout = $stderr = '';
    $released = false;
    $exit = null;
    $deadline = microtime(true) + 3.0;
    do {
        foreach ([[$pairs[2][0], &$barrier], [$pairs[3][0], &$result], [$pipes[1], &$stdout], [$pipes[2], &$stderr]] as [&$channel, &$buffer]) {
            $chunk = fread($channel, 4096);
            if (is_string($chunk) && $chunk !== '') $buffer .= $chunk;
        }
        unset($channel, $buffer);
        // Release only to bound the known-bad implementation; this is not a
        // test-owned finalized fact or lock and cannot make an empty barrier pass.
        if (!$released && $barrier !== '') {
            fwrite($pairs[1][0], "RELEASE 00000000-0000-4000-8000-000000000560\n");
            stream_socket_shutdown($pairs[1][0], STREAM_SHUT_WR);
            $released = true;
        }
        $status = proc_get_status($process);
        if (!$status['running']) { $exit = $status['exitcode']; break; }
        usleep(10_000);
    } while (microtime(true) < $deadline);
    if ($exit === null) throw new TestFailure('Worker exceeded bounded completion deadline.');
    foreach ([[$pairs[2][0], &$barrier], [$pairs[3][0], &$result], [$pipes[1], &$stdout], [$pipes[2], &$stderr]] as [&$channel, &$buffer]) {
        while (($chunk = fread($channel, 4096)) !== false && $chunk !== '') $buffer .= $chunk;
    }
    unset($channel, $buffer);
    proc_close($process); $process = null;
    assertSameValue('', $barrier, 'Unauthorized command emits no post-finalize READY because no actual finalize lifecycle event occurred.');
    $decoded = json_decode(rtrim($result, "\n"), true, 512, JSON_THROW_ON_ERROR);
    assertSameValue(['rejected','authorization_denied',false], [$decoded['status'], $decoded['reasonCode'], $decoded['retryable']], 'Unauthorized worker result remains exact.');
    assertSameValue(['',''], [$stdout,$stderr], 'Negative lifecycle run keeps stdio empty.');
    assertSameValue(0, $exit, 'Corrected negative lifecycle worker exits successfully.');
    assertSameValue([], array_values(array_diff(scandir($private) ?: [], ['.','..'])), 'No manual finalized metadata, lock or content is fabricated.');
} finally {
    foreach ($pairs as $pair) foreach ($pair as $endpoint) if (is_resource($endpoint)) fclose($endpoint);
    foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
    if (is_resource($process)) {
        $status = proc_get_status($process);
        if ($status['running']) {
            proc_terminate($process, 15);
            $deadline = microtime(true) + 0.5;
            do { usleep(10_000); $status = proc_get_status($process); } while ($status['running'] && microtime(true) < $deadline);
        }
        if ($status['running']) {
            proc_terminate($process, 9);
            $deadline = microtime(true) + 0.5;
            do { usleep(10_000); $status = proc_get_status($process); } while ($status['running'] && microtime(true) < $deadline);
        }
        proc_close($process);
    }
    if ($db instanceof mysqli) $db->close();
    try { $admin->query("DROP DATABASE IF EXISTS `{$database}`"); } catch (Throwable) {}
    $admin->close(); $remove($root);
}

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_WORKER_POST_FINALIZE_NEGATIVE_OK\n");
