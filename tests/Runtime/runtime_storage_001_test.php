<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$repo = dirname(__DIR__, 2);
$prepare = $repo . '/bin/fmonitor2-runtime-prepare.php';
$check = $repo . '/bin/fmonitor2-runtime-check.php';
assertSameValue(true, is_file($prepare), 'INTENTIONAL_RED: runtime storage prepare CLI exists');
assertSameValue(true, is_file($check), 'INTENTIONAL_RED: runtime readiness CLI exists');
$token = bin2hex(random_bytes(6));
$temporaryRoot = realpath(sys_get_temp_dir());
if (!is_string($temporaryRoot)) {
    throw new TestFailure('SETUP_FAILURE: canonical temporary root unavailable');
}
$taskRoot = $temporaryRoot . '/fmonitor-runtime-storage-' . $token;

/** @return array{exit:int,stdout:string,stderr:string} */
function runtimeStorageRun(string $script, array $environment, string $repo): array
{
    $baseEnvironment = getenv();
    if (!is_array($baseEnvironment)) {
        throw new TestFailure('SETUP_FAILURE: process environment unavailable');
    }
    $pipes = [];
    $process = proc_open([PHP_BINARY, $script], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $repo, array_replace($baseEnvironment, $environment));
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: runtime CLI start');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

/** @return array<string,string> */
function runtimeStorageEnvironment(string $root, string $token): array
{
    return [
        'FMONITOR_DB_HOST' => '127.0.0.1',
        'FMONITOR_DB_PORT' => '1',
        'FMONITOR_DB_NAME' => 'not_accessed_by_prepare',
        'FMONITOR_DB_USER' => 'not_accessed_by_prepare',
        'FMONITOR_DB_PASSWORD' => 'secret-' . $token,
        'FMONITOR_PROCESS_TABLE_PREFIX' => 'runtime_',
        'FMONITOR_LEGACY_TABLE_PREFIX' => 'runtime_',
        'FMONITOR_SESSION_STATE_ROOT' => $root . '/state',
        'FMONITOR_SESSION_INSTANCE' => 'production',
        'FMONITOR_ARTIFACT_STORAGE_ROOT' => $root . '/state/artifacts',
        'FMONITOR_ORIGINAL_DB_PASSWORD_FILE' => $root . '/secrets/original-db-password',
        'FMONITOR_ORIGINAL_SAFE_LOG_FILE' => $root . '/state/log/original-safe.jsonl',
        'FMONITOR_TRUSTED_REQUEST_HOST' => 'fmonitor.example.test',
        'FMONITOR_TRUSTED_REQUEST_SCHEME' => 'https',
    ];
}

function runtimeStorageSnapshot(string $root): array
{
    $snapshot = [];
    if (!file_exists($root) && !is_link($root)) {
        return $snapshot;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $entry) {
        $path = $entry->getPathname();
        $snapshot[substr($path, strlen($root))] = [
            'type' => is_link($path) ? 'link' : ($entry->isDir() ? 'dir' : 'file'),
            'mode' => fileperms($path) & 0777,
            'size' => $entry->isFile() ? filesize($path) : null,
            'sha256' => $entry->isFile() ? hash_file('sha256', $path) : null,
            'mtime' => filemtime($path),
            'inode' => fileinode($path),
            'uid' => fileowner($path),
            'gid' => filegroup($path),
        ];
    }
    ksort($snapshot);
    return $snapshot;
}

function runtimeStorageRemove(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            runtimeStorageRemove($path . '/' . $entry);
        }
    }
    rmdir($path);
}

try {
    $environment = runtimeStorageEnvironment($taskRoot, $token);
    $first = runtimeStorageRun($prepare, $environment, $repo);
    assertSameValue([0, "{\"ok\":true}\n", ''], [$first['exit'], $first['stdout'], $first['stderr']], 'prepare creates fresh runtime resources without DB access');
    foreach ([$environment['FMONITOR_SESSION_STATE_ROOT'], $environment['FMONITOR_SESSION_STATE_ROOT'] . '/yii-sessions', $environment['FMONITOR_ARTIFACT_STORAGE_ROOT'], dirname($environment['FMONITOR_ORIGINAL_DB_PASSWORD_FILE']), dirname($environment['FMONITOR_ORIGINAL_SAFE_LOG_FILE'])] as $directory) {
        assertSameValue([true, 0700, posix_geteuid(), posix_getegid()], [is_dir($directory), fileperms($directory) & 0777, fileowner($directory), filegroup($directory)], "prepared private directory {$directory} owned by the executing runtime identity");
    }
    assertSameValue([$environment['FMONITOR_DB_PASSWORD'], 0600], [file_get_contents($environment['FMONITOR_ORIGINAL_DB_PASSWORD_FILE']), fileperms($environment['FMONITOR_ORIGINAL_DB_PASSWORD_FILE']) & 0777], 'prepare writes exact no-LF private DB credential');
    assertSameValue(['', 0600], [file_get_contents($environment['FMONITOR_ORIGINAL_SAFE_LOG_FILE']), fileperms($environment['FMONITOR_ORIGINAL_SAFE_LOG_FILE']) & 0777], 'prepare creates an empty private append log');

    file_put_contents($environment['FMONITOR_ARTIFACT_STORAGE_ROOT'] . '/preserved.pdf', '%PDF-runtime-preserve', LOCK_EX);
    file_put_contents($environment['FMONITOR_ORIGINAL_SAFE_LOG_FILE'], "{\"preserve\":true}\n", FILE_APPEND | LOCK_EX);
    clearstatcache(true, $taskRoot);
    $before = runtimeStorageSnapshot($taskRoot);
    sleep(1);
    $repeat = runtimeStorageRun($prepare, $environment, $repo);
    clearstatcache(true, $taskRoot);
    assertSameValue([0, "{\"ok\":true}\n", ''], [$repeat['exit'], $repeat['stdout'], $repeat['stderr']], 'prepare repeat succeeds');
    assertSameValue($before, runtimeStorageSnapshot($taskRoot), 'prepare repeat preserves bytes, modes, mtimes and inodes exactly');

    $checkEnvironment = array_replace($environment, ['FMONITOR_DB_PORT' => (string) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306), 'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', 'FMONITOR_DB_NAME' => getenv('FMONITOR_TEST_DB_NAME') ?: 'fmonitor2_test', 'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_USER') ?: 'fmonitor2_test', 'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_PASSWORD') ?: 'fmonitor2_test_local']);
    file_put_contents($environment['FMONITOR_ORIGINAL_DB_PASSWORD_FILE'], $checkEnvironment['FMONITOR_DB_PASSWORD'], LOCK_EX);
    chmod($environment['FMONITOR_ORIGINAL_DB_PASSWORD_FILE'], 0600);
    clearstatcache(true, $taskRoot);
    $checkBefore = runtimeStorageSnapshot($taskRoot);
    $checkResult = runtimeStorageRun($check, $checkEnvironment, $repo);
    assertSameValue(true, in_array($checkResult['exit'], [0, 69, 70], true), 'readiness reports a documented success, DB-unavailable, or schema-not-ready outcome');
    assertSameValue(true, in_array(trim($checkResult['stdout']), ['{"ok":true}', '{"ok":false,"reason":"DATABASE_UNAVAILABLE"}', '{"ok":false,"reason":"SCHEMA_NOT_READY"}'], true), 'readiness stdout is a stable non-disclosing outcome');
    assertSameValue('', $checkResult['stderr'], 'readiness emits no configuration or secret diagnostics');
    clearstatcache(true, $taskRoot);
    assertSameValue($checkBefore, runtimeStorageSnapshot($taskRoot), 'readiness check performs no filesystem writes even when DB/schema outcome varies');

    foreach (['symlink-root', 'wrong-secret', 'unsafe-mode', 'wrong-log-type'] as $case) {
        $caseRoot = $taskRoot . '-' . $case;
        $caseEnvironment = runtimeStorageEnvironment($caseRoot, $token);
        mkdir($caseRoot, 0700, true);
        if ($case === 'symlink-root') {
            mkdir($caseRoot . '/target', 0700);
            symlink($caseRoot . '/target', $caseEnvironment['FMONITOR_SESSION_STATE_ROOT']);
        } else {
            $ok = runtimeStorageRun($prepare, $caseEnvironment, $repo);
            assertSameValue(0, $ok['exit'], "{$case} prerequisite prepare");
            if ($case === 'wrong-secret') {
                file_put_contents($caseEnvironment['FMONITOR_ORIGINAL_DB_PASSWORD_FILE'], 'different-secret', LOCK_EX);
            } elseif ($case === 'unsafe-mode') {
                chmod($caseEnvironment['FMONITOR_ORIGINAL_DB_PASSWORD_FILE'], 0644);
            } else {
                unlink($caseEnvironment['FMONITOR_ORIGINAL_SAFE_LOG_FILE']);
                mkdir($caseEnvironment['FMONITOR_ORIGINAL_SAFE_LOG_FILE'], 0700);
            }
        }
        $invalidBefore = runtimeStorageSnapshot($caseRoot);
        $invalid = runtimeStorageRun($prepare, $caseEnvironment, $repo);
        assertSameValue([70, "{\"ok\":false,\"reason\":\"RUNTIME_STORAGE_INVALID\"}\n", ''], [$invalid['exit'], $invalid['stdout'], $invalid['stderr']], "{$case} fails closed without secret disclosure");
        assertSameValue($invalidBefore, runtimeStorageSnapshot($caseRoot), "{$case} failure performs no mutation");
        runtimeStorageRemove($caseRoot);
    }

    echo "PASS: PRODUCTION-HTTP-RUNTIME-001 storage prepare/replay/readiness contract\n";
} finally {
    runtimeStorageRemove($taskRoot);
}
