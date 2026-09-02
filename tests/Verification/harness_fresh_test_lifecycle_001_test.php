<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** HARNESS-FRESH-TEST-LIFECYCLE-001 v0.1 */

function hftlWrite(string $path, string $contents, bool $executable = false): void
{
    if (file_put_contents($path, $contents) === false || ($executable && !chmod($path, 0700))) {
        throw new TestFailure("SETUP_FAILURE: cannot create fixture $path");
    }
}

function hftlRun(
    string $root,
    string $overlay,
    string $bin,
    string $log,
    string $dockerLog,
    string $verifyResult,
    string $teardownResult,
    bool $parallel,
): array {
    $environment = getenv();
    if (!is_array($environment)) {
        $environment = $_ENV;
    }
    $environment['PATH'] = $bin . PATH_SEPARATOR . ($environment['PATH'] ?? '');
    $environment['HFTL_STAGE_LOG'] = $log;
    $environment['HFTL_DOCKER_LOG'] = $dockerLog;
    if ($parallel) {
        $environment['MAKEFLAGS'] = '-j4';
    } else {
        unset($environment['MAKEFLAGS'], $environment['MFLAGS']);
    }

    $process = proc_open(
        [
            'make', '--no-print-directory', '-f', 'Makefile', '-f', $overlay,
            "VERIFY_RESULT=$verifyResult", "TEARDOWN_RESULT=$teardownResult",
            'fresh-test-verify',
        ],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        $environment,
    );
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: make fresh-test-verify did not start');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['status' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function hftlCount(array $result, string $line): int
{
    return preg_match_all('/^' . preg_quote($line, '/') . '$/m', $result['stdout'] . "\n" . $result['stderr']);
}

function hftlTerminalStdoutLine(array $result): string
{
    $lines = array_values(array_filter(explode("\n", trim($result['stdout']))));
    return $lines[array_key_last($lines)] ?? '';
}

function hftlObserved(string $log): array
{
    return is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
}

function hftlAssertStreams(array $result, string $stage, string $evidence): void
{
    assertSameValue(1, preg_match_all('/^HFTL_STDOUT ' . preg_quote($stage, '/') . '$/m', $result['stdout']), "$stage stdout must remain visible; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^HFTL_STDERR ' . preg_quote($stage, '/') . '$/m', $result['stderr']), "$stage stderr must remain visible; evidence=$evidence");
}

$root = dirname(__DIR__, 2);
$fixture = sys_get_temp_dir() . '/fmonitor-hftl-' . bin2hex(random_bytes(8));
$bin = $fixture . '/bin';
$log = $fixture . '/stages.log';
$dockerLog = $fixture . '/docker.log';
$overlay = $fixture . '/lifecycle.mk';

if (!mkdir($bin, 0700, true)) {
    throw new TestFailure("SETUP_FAILURE: cannot create fixture directory $bin");
}

try {
    hftlWrite($bin . '/lifecycle-stage', <<<'SH'
#!/bin/sh
set -eu
stage="$1"
result="$2"
printf '%s %s\n' "$stage" "$result" >> "$HFTL_STAGE_LOG"
printf 'HFTL_STDOUT %s\n' "$stage"
printf 'HFTL_STDERR %s\n' "$stage" >&2
[ "$result" = PASS ]
SH, true);
    hftlWrite($bin . '/docker', <<<'SH'
#!/bin/sh
set -eu
printf 'docker' >> "$HFTL_DOCKER_LOG"
for argument in "$@"; do
    printf ' %s' "$argument" >> "$HFTL_DOCKER_LOG"
done
printf '\n' >> "$HFTL_DOCKER_LOG"
exit 97
SH, true);
    hftlWrite($overlay, <<<'MAKE'
.PHONY: verify test-env-down
verify:
	@lifecycle-stage verify $(VERIFY_RESULT)
test-env-down:
	@lifecycle-stage test-env-down $(TEARDOWN_RESULT)
MAKE);

    $scenarios = [
        'green' => ['PASS', 'PASS', 0, 'FRESH_TEST_VERIFY_OK', false],
        'verify-failure' => ['FAIL', 'PASS', 2, 'FRESH_TEST_VERIFY_FAILURE verify_status=2 teardown_status=0', false],
        'teardown-failure' => ['PASS', 'FAIL', 2, 'FRESH_TEST_VERIFY_FAILURE verify_status=0 teardown_status=2', true],
        'dual-failure' => ['FAIL', 'FAIL', 2, 'FRESH_TEST_VERIFY_FAILURE verify_status=2 teardown_status=2', true],
    ];

    foreach ([false, true] as $parallel) {
        foreach ($scenarios as $name => [$verifyResult, $teardownResult, $outerStatus, $terminal, $teardownSetupFailure]) {
            hftlWrite($log, '');
            hftlWrite($dockerLog, '');
            $result = hftlRun($root, $overlay, $bin, $log, $dockerLog, $verifyResult, $teardownResult, $parallel);
            $observed = hftlObserved($log);
            $dockerInvocations = hftlObserved($dockerLog);
            $mode = $parallel ? 'MAKEFLAGS=-j4' : 'normal';
            $evidence = json_encode(
                ['scenario' => $name, 'mode' => $mode, 'observed' => $observed, 'docker' => $dockerInvocations] + $result,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );

            assertSameValue(
                [],
                $dockerInvocations,
                "RED_ASSERTION: fresh-test-verify must delegate only to the overlay verify and test-env-down targets, without duplicate Docker lifecycle logic, for $name under $mode; evidence=$evidence",
            );
            assertSameValue(
                ["verify $verifyResult", "test-env-down $teardownResult"],
                $observed,
                "RED_ASSERTION: fresh-test-verify must invoke public verify once, then public test-env-down once, for $name under $mode; evidence=$evidence",
            );
            hftlAssertStreams($result, 'verify', $evidence);
            hftlAssertStreams($result, 'test-env-down', $evidence);
            assertSameValue($outerStatus, $result['status'], "The public make status must reflect the complete $name lifecycle under $mode; evidence=$evidence");
            assertSameValue(1, hftlCount($result, $terminal), "$name must emit exactly one terminal lifecycle result under $mode; evidence=$evidence");
            assertSameValue($terminal, hftlTerminalStdoutLine($result), "$name lifecycle result must be the terminal stdout line under $mode; evidence=$evidence");
            assertSameValue($teardownSetupFailure ? 1 : 0, hftlCount($result, 'SETUP_FAILURE stage=test-env-down'), "Only teardown failure may receive setup classification for $name under $mode; evidence=$evidence");
            assertSameValue($name === 'green' ? 1 : 0, hftlCount($result, 'FRESH_TEST_VERIFY_OK'), "Success marker cardinality must match $name under $mode; evidence=$evidence");
            assertSameValue($name === 'green' ? 0 : 1, preg_match_all('/^FRESH_TEST_VERIFY_FAILURE verify_status=\d+ teardown_status=\d+$/m', $result['stdout'] . "\n" . $result['stderr']), "Failure marker cardinality must match $name under $mode; evidence=$evidence");
            if (!$teardownSetupFailure) {
                assertSameValue(0, preg_match_all('/^SETUP_FAILURE /m', $result['stdout'] . "\n" . $result['stderr']), "Verification failure alone must not be mislabeled as setup failure under $mode; evidence=$evidence");
            }
        }
    }

    echo "ok - HARNESS-FRESH-TEST-LIFECYCLE-001 always tears down after authoritative verification\n";
} finally {
    foreach ([$bin . '/lifecycle-stage', $bin . '/docker', $overlay, $log, $dockerLog] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    if (is_dir($bin)) {
        rmdir($bin);
    }
    if (is_dir($fixture)) {
        rmdir($fixture);
    }
}
