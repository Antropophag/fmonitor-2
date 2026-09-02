<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** HARNESS-CANONICAL-MIGRATION-STAGE-001 v0.1 */

function hcmWrite(string $path, string $contents, bool $executable = false): void
{
    if (file_put_contents($path, $contents) === false || ($executable && !chmod($path, 0700))) {
        throw new TestFailure("SETUP_FAILURE: cannot create fixture $path");
    }
}

function hcmRun(string $root, string $overlay, string $bin, string $log, string $target, array $variables = []): array
{
    $environment = getenv();
    if (!is_array($environment)) {
        $environment = $_ENV;
    }
    $environment['PATH'] = $bin . PATH_SEPARATOR . ($environment['PATH'] ?? '');
    $environment['HCM_STAGE_LOG'] = $log;
    $command = array_merge(
        ['make', '--no-print-directory', '-f', 'Makefile', '-f', $overlay],
        array_map(static fn (string $name, string $value): string => "$name=$value", array_keys($variables), $variables),
        [$target],
    );
    $process = proc_open(
        $command,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        $environment,
    );
    if (!is_resource($process)) {
        throw new TestFailure("SETUP_FAILURE: make $target did not start");
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['status' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function hcmLines(string $path): array
{
    return is_file($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
}

function hcmProtocol(array $result): array
{
    preg_match_all('/^VERIFY_STAGE (?:test-db-reset|migrate|architecture-check|lint|unit-test|db-test|characterization-test|e2e-test|diff-check) (?:PASS|FAIL)$/m', $result['stdout'] . "\n" . $result['stderr'], $matches);
    return $matches[0];
}

function hcmCount(array $result, string $line): int
{
    return preg_match_all('/^' . preg_quote($line, '/') . '$/m', $result['stdout'] . "\n" . $result['stderr']);
}

function hcmTerminalStdoutLine(array $result): string
{
    $lines = array_values(array_filter(explode("\n", trim($result['stdout']))));
    return $lines[array_key_last($lines)] ?? '';
}

$root = dirname(__DIR__, 2);
$fixture = sys_get_temp_dir() . '/fmonitor-hcm-' . bin2hex(random_bytes(8));
$bin = $fixture . '/bin';
$log = $fixture . '/stages.log';
$overlay = $fixture . '/verify.mk';
$migrateOverlay = $fixture . '/migrate.mk';
$marker = $fixture . '/database.marker';

if (!mkdir($bin, 0700, true)) {
    throw new TestFailure("SETUP_FAILURE: cannot create fixture directory $bin");
}

try {
    hcmWrite($bin . '/stage', <<<'SH'
#!/bin/sh
set -eu
name="$1"
result="$2"
printf '%s %s\n' "$name" "$result" >> "$HCM_STAGE_LOG"
printf 'HCM_STDOUT %s\n' "$name"
printf 'HCM_STDERR %s\n' "$name" >&2
[ "$result" = PASS ]
SH, true);
    hcmWrite($bin . '/git', <<<'SH'
#!/bin/sh
set -eu
if [ "$#" -eq 2 ] && [ "$1" = diff ] && [ "$2" = --check ]; then
    exec stage diff-check PASS
fi
exit 2
SH, true);
    hcmWrite($overlay, <<<'MAKE'
.PHONY: test-env-up test-db-reset migrate architecture-check lint unit-test db-test characterization-test e2e-test
test-env-up:
	@:
test-db-reset:
	@stage test-db-reset $(RESET_RESULT)
migrate:
	@stage migrate $(MIGRATE_RESULT)
architecture-check:
	@stage architecture-check PASS
lint:
	@stage lint PASS
unit-test:
	@stage unit-test PASS
db-test:
	@stage db-test PASS
characterization-test:
	@stage characterization-test PASS
e2e-test:
	@stage e2e-test PASS
MAKE);

    $result = hcmRun($root, $overlay, $bin, $log, 'verify', ['RESET_RESULT' => 'PASS', 'MIGRATE_RESULT' => 'PASS']);
    $expectedStages = ['test-db-reset', 'migrate', 'architecture-check', 'lint', 'unit-test', 'db-test', 'characterization-test', 'e2e-test', 'diff-check'];
    $evidence = json_encode($result + ['log' => hcmLines($log)], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(array_map(static fn (string $stage): string => "$stage PASS", $expectedStages), hcmLines($log), "RED_ASSERTION: successful make verify must execute the exact nine-stage reset-then-migrate order; evidence=$evidence");
    assertSameValue(array_map(static fn (string $stage): string => "VERIFY_STAGE $stage PASS", $expectedStages), hcmProtocol($result), "Successful make verify must publish exactly one ordered PASS per stage; evidence=$evidence");
    assertSameValue(0, $result['status'], "Successful nine-stage verification must exit zero; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'VERIFY_OK'), "Successful verification must emit exactly one VERIFY_OK; evidence=$evidence");

    hcmWrite($log, '');
    $result = hcmRun($root, $overlay, $bin, $log, 'verify', ['RESET_RESULT' => 'PASS', 'MIGRATE_RESULT' => 'FAIL']);
    $evidence = json_encode($result + ['log' => hcmLines($log)], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue([
        'test-db-reset PASS', 'migrate FAIL', 'architecture-check PASS', 'lint PASS', 'unit-test PASS',
        'characterization-test PASS', 'diff-check PASS',
    ], hcmLines($log), "A raw migration failure must skip DB/E2E commands while independent stages continue; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'SETUP_FAILURE stage=migrate'), "Raw migration failure must be classified exactly once; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^HCM_STDOUT migrate$/m', $result['stdout']), "Failed migration stdout must remain visible exactly once through make verify; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^HCM_STDERR migrate$/m', $result['stderr']), "Failed migration stderr must remain visible exactly once through make verify; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'SETUP_FAILURE stage=db-test cause=migrate outcome=SKIP'), "db-test must name migrate as its setup blocker; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'SETUP_FAILURE stage=e2e-test cause=migrate outcome=SKIP'), "e2e-test must name migrate as its setup blocker; evidence=$evidence");
    assertSameValue([
        'VERIFY_STAGE test-db-reset PASS', 'VERIFY_STAGE migrate FAIL', 'VERIFY_STAGE architecture-check PASS',
        'VERIFY_STAGE lint PASS', 'VERIFY_STAGE unit-test PASS', 'VERIFY_STAGE db-test FAIL',
        'VERIFY_STAGE characterization-test PASS', 'VERIFY_STAGE e2e-test FAIL', 'VERIFY_STAGE diff-check PASS',
    ], hcmProtocol($result), "Migration-blocked stages must retain explicit ordered protocol results; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'FULL_VERIFICATION_FAILURE count=3 stages=migrate,db-test,e2e-test'), "Migration failure summary must count exactly the migration and blocked stages; evidence=$evidence");
    assertSameValue('FULL_VERIFICATION_FAILURE count=3 stages=migrate,db-test,e2e-test', hcmTerminalStdoutLine($result), "Migration failure summary must be the terminal stdout line; evidence=$evidence");
    assertSameValue(true, $result['status'] !== 0, "Migration failure must exit nonzero; evidence=$evidence");
    assertSameValue(0, hcmCount($result, 'VERIFY_OK'), "Migration-blocked verification must not emit VERIFY_OK; evidence=$evidence");

    hcmWrite($log, '');
    $result = hcmRun($root, $overlay, $bin, $log, 'verify', ['RESET_RESULT' => 'FAIL', 'MIGRATE_RESULT' => 'PASS']);
    $evidence = json_encode($result + ['log' => hcmLines($log)], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue([
        'test-db-reset FAIL', 'architecture-check PASS', 'lint PASS', 'unit-test PASS',
        'characterization-test PASS', 'diff-check PASS',
    ], hcmLines($log), "Reset failure must not invoke migrate, DB, or E2E commands; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'SETUP_FAILURE stage=migrate cause=test-db-reset outcome=SKIP'), "Skipped migration must name reset as its cause; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'SETUP_FAILURE stage=db-test cause=test-db-reset outcome=SKIP'), "Skipped db-test must name immediate reset cause; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'SETUP_FAILURE stage=e2e-test cause=test-db-reset outcome=SKIP'), "Skipped e2e-test must name immediate reset cause; evidence=$evidence");
    assertSameValue([
        'VERIFY_STAGE test-db-reset FAIL', 'VERIFY_STAGE migrate FAIL', 'VERIFY_STAGE architecture-check PASS',
        'VERIFY_STAGE lint PASS', 'VERIFY_STAGE unit-test PASS', 'VERIFY_STAGE db-test FAIL',
        'VERIFY_STAGE characterization-test PASS', 'VERIFY_STAGE e2e-test FAIL', 'VERIFY_STAGE diff-check PASS',
    ], hcmProtocol($result), "Reset-blocked stages must retain explicit ordered protocol results; evidence=$evidence");
    assertSameValue(1, hcmCount($result, 'FULL_VERIFICATION_FAILURE count=4 stages=test-db-reset,migrate,db-test,e2e-test'), "Reset failure summary must count every setup-blocked stage in protocol order; evidence=$evidence");
    assertSameValue('FULL_VERIFICATION_FAILURE count=4 stages=test-db-reset,migrate,db-test,e2e-test', hcmTerminalStdoutLine($result), "Reset failure summary must be the terminal stdout line; evidence=$evidence");
    assertSameValue(true, $result['status'] !== 0, "Reset-blocked verification must exit nonzero; evidence=$evidence");
    assertSameValue(0, hcmCount($result, 'VERIFY_OK'), "Reset-blocked verification must not emit VERIFY_OK; evidence=$evidence");

    hcmWrite($log, '');
    hcmWrite($marker, "preserve-me\n");
    hcmWrite($bin . '/php', <<<'SH'
#!/bin/sh
set -eu
[ "$#" -eq 1 ]
[ "$1" = bin/fmonitor2-migrate.php ]
[ "${FMONITOR_DB_HOST:-}" = 127.0.0.1 ]
[ "${FMONITOR_DB_PORT:-}" = 23306 ]
[ "${FMONITOR_DB_NAME:-}" = fmonitor2_test ]
[ "${FMONITOR_DB_USER:-}" = fmonitor2_test ]
[ "${FMONITOR_DB_PASSWORD:-}" = fmonitor2_test_local ]
[ "${FMONITOR_PROCESS_TABLE_PREFIX+x}" = x ]
[ -z "$FMONITOR_PROCESS_TABLE_PREFIX" ]
[ -f "$HCM_DB_MARKER" ]
marker="$(cat "$HCM_DB_MARKER")"
printf 'canonical-runner marker=%s\n' "$marker" | tee -a "$HCM_STAGE_LOG"
SH, true);
    hcmWrite($migrateOverlay, <<<'MAKE'
.PHONY: test-env-up test-db-reset
test-env-up:
	@printf 'FORBIDDEN test-env-up\n' >> "$(HCM_STAGE_LOG)"
test-db-reset:
	@printf 'FORBIDDEN test-db-reset\n' >> "$(HCM_STAGE_LOG)"
MAKE);
    putenv("HCM_DB_MARKER=$marker");
    $first = hcmRun($root, $migrateOverlay, $bin, $log, 'migrate');
    $second = hcmRun($root, $migrateOverlay, $bin, $log, 'migrate');
    putenv('HCM_DB_MARKER');
    $evidence = json_encode(['first' => $first, 'second' => $second, 'log' => hcmLines($log)], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue([
        'canonical-runner marker=preserve-me',
        'canonical-runner marker=preserve-me',
    ], hcmLines($log), "RED_ASSERTION: make migrate twice must invoke the canonical runner twice without an implicit reset and preserve the database marker; evidence=$evidence");
    assertSameValue(0, $first['status'], "First public migration must succeed; evidence=$evidence");
    assertSameValue(0, $second['status'], "Repeated public migration must exercise runner idempotency; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^canonical-runner marker=preserve-me$/m', $first['stdout']), "Canonical runner stdout must remain visible through the first make migrate invocation; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^canonical-runner marker=preserve-me$/m', $second['stdout']), "Canonical runner stdout must remain visible through the repeated make migrate invocation; evidence=$evidence");
    assertSameValue(true, is_file($marker), "Public migration must preserve the existing database marker; evidence=$evidence");

    echo "ok - HARNESS-CANONICAL-MIGRATION-STAGE-001 verifies reset/migrate orchestration and non-destructive migration\n";
} finally {
    putenv('HCM_DB_MARKER');
    foreach ([$bin . '/stage', $bin . '/git', $bin . '/php', $overlay, $migrateOverlay, $log, $marker] as $path) {
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
