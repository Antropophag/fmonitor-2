<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** HARNESS-FULL-AGGREGATION-001 v0.2 */

function hfaWriteExecutable(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false || !chmod($path, 0700)) {
        throw new TestFailure("SETUP_FAILURE: cannot create fixture executable $path");
    }
}

function hfaRun(string $root, string $overlay, string $bin, string $log): array
{
    $environment = getenv();
    if (!is_array($environment)) {
        $environment = $_ENV;
    }
    $environment['PATH'] = $bin . PATH_SEPARATOR . ($environment['PATH'] ?? '');
    $environment['HFA_STAGE_LOG'] = $log;
    $process = proc_open(
        ['make', '--no-print-directory', '-f', 'Makefile', '-f', $overlay, 'verify'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        $environment,
    );
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: make verify did not start');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['status' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function hfaWriteOverlay(string $path, array $results = []): void
{
    $results += [
        'test-db-reset' => 'PASS',
        'migrate' => 'PASS',
        'architecture-check' => 'PASS',
        'lint' => 'PASS',
        'unit-test' => 'PASS',
        'db-test' => 'PASS',
        'characterization-test' => 'PASS',
        'e2e-test' => 'PASS',
    ];
    foreach ($results as $stage => $result) {
        if (!in_array($result, ['PASS', 'FAIL'], true)) {
            throw new TestFailure("SETUP_FAILURE: invalid fixture result $result for $stage");
        }
    }
    $contents = <<<MAKE
.PHONY: test-env-up test-db-reset migrate architecture-check lint unit-test db-test characterization-test e2e-test
test-env-up:
	@:
test-db-reset:
	@stage test-db-reset {$results['test-db-reset']}
migrate:
	@stage migrate {$results['migrate']}
architecture-check:
	@stage architecture-check {$results['architecture-check']}
lint:
	@stage lint {$results['lint']}
unit-test:
	@stage unit-test {$results['unit-test']}
db-test:
	@stage db-test {$results['db-test']}
characterization-test:
	@stage characterization-test {$results['characterization-test']}
e2e-test:
	@stage e2e-test {$results['e2e-test']}
MAKE;
    if (file_put_contents($path, $contents . "\n") === false) {
        throw new TestFailure("SETUP_FAILURE: cannot create make fixture $path");
    }
}

function hfaProtocolLines(array $result): array
{
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    preg_match_all('/^VERIFY_STAGE (?:test-db-reset|migrate|architecture-check|lint|unit-test|db-test|characterization-test|e2e-test|diff-check) (?:PASS|FAIL)$/m', $combined, $matches);
    return $matches[0];
}

function hfaExactLineCount(array $result, string $line): int
{
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    return preg_match_all('/^' . preg_quote($line, '/') . '$/m', $combined);
}

function hfaAssertEvidenceVisible(array $result, array $stages, string $evidence): void
{
    foreach ($stages as $stage) {
        assertSameValue(1, preg_match_all('/^HFA_STDOUT ' . preg_quote($stage, '/') . '$/m', $result['stdout']), "stdout evidence from $stage must remain visible through make verify; evidence=$evidence");
        assertSameValue(1, preg_match_all('/^HFA_STDERR ' . preg_quote($stage, '/') . '$/m', $result['stderr']), "stderr evidence from $stage must remain visible through make verify; evidence=$evidence");
    }
}

$root = dirname(__DIR__, 2);
$fixture = sys_get_temp_dir() . '/fmonitor-hfa-' . bin2hex(random_bytes(8));
$bin = $fixture . '/bin';
$log = $fixture . '/stages.log';
$overlay = $fixture . '/stages.mk';

if (!mkdir($bin, 0700, true)) {
    throw new TestFailure("SETUP_FAILURE: cannot create fixture directory $bin");
}

try {
    hfaWriteExecutable($bin . '/stage', <<<'SH'
#!/bin/sh
set -eu
stage="$1"
result="$2"
printf '%s %s\n' "$stage" "$result" | tee -a "$HFA_STAGE_LOG"
printf 'HFA_STDOUT %s\n' "$stage"
printf 'HFA_STDERR %s\n' "$stage" >&2
[ "$result" = PASS ]
SH);
    hfaWriteExecutable($bin . '/git', <<<'SH'
#!/bin/sh
set -eu
if [ "$#" -eq 2 ] && [ "$1" = diff ] && [ "$2" = --check ]; then
    exec stage diff-check PASS
fi
printf 'SETUP_FAILURE: unexpected git fixture call:' >&2
printf ' %s' "$@" >&2
printf '\n' >&2
exit 2
SH);
    hfaWriteOverlay($overlay, ['db-test' => 'FAIL']);
    $result = hfaRun($root, $overlay, $bin, $log);
    $observed = is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $expected = [
        'test-db-reset PASS',
        'migrate PASS',
        'architecture-check PASS',
        'lint PASS',
        'unit-test PASS',
        'db-test FAIL',
        'characterization-test PASS',
        'e2e-test PASS',
        'diff-check PASS',
    ];
    $stageNames = array_map(static fn (string $line): string => explode(' ', $line, 2)[0], $expected);
    $evidence = json_encode($result + ['observedStages' => $observed], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue($expected, $observed, "RED_ASSERTION: make verify must attempt all nine stages in stable order after a middle DB regression; evidence=$evidence");
    hfaAssertEvidenceVisible($result, $stageNames, $evidence);
    assertSameValue([
        'VERIFY_STAGE test-db-reset PASS',
        'VERIFY_STAGE migrate PASS',
        'VERIFY_STAGE architecture-check PASS',
        'VERIFY_STAGE lint PASS',
        'VERIFY_STAGE unit-test PASS',
        'VERIFY_STAGE db-test FAIL',
        'VERIFY_STAGE characterization-test PASS',
        'VERIFY_STAGE e2e-test PASS',
        'VERIFY_STAGE diff-check PASS',
    ], hfaProtocolLines($result), "RED_ASSERTION: failure output must contain exactly one ordered result for every verification stage; evidence=$evidence");
    assertSameValue(1, hfaExactLineCount($result, 'FULL_VERIFICATION_FAILURE count=1 stages=db-test'), "RED_ASSERTION: failure output must contain one machine-readable terminal summary; evidence=$evidence");
    assertSameValue('FULL_VERIFICATION_FAILURE count=1 stages=db-test', array_values(array_filter(explode("\n", trim($result['stdout']))))[array_key_last(array_values(array_filter(explode("\n", trim($result['stdout'])))))], "RED_ASSERTION: machine-readable failure summary must be the terminal stdout line; evidence=$evidence");
    assertSameValue(true, $result['status'] !== 0, "A failed required stage must make verify exit nonzero; evidence=$evidence");
    assertSameValue(0, hfaExactLineCount($result, 'VERIFY_OK'), "A failed verification must not print VERIFY_OK; evidence=$evidence");

    file_put_contents($log, '');
    hfaWriteOverlay($overlay);
    $result = hfaRun($root, $overlay, $bin, $log);
    $observed = is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $expected = array_map(static fn (string $line): string => str_replace('db-test FAIL', 'db-test PASS', $line), $expected);
    $evidence = json_encode($result + ['observedStages' => $observed], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue($expected, $observed, "Successful make verify must execute all nine fixture stages in stable order; evidence=$evidence");
    hfaAssertEvidenceVisible($result, $stageNames, $evidence);
    assertSameValue(array_map(static fn (string $line): string => 'VERIFY_STAGE ' . $line, $expected), hfaProtocolLines($result), "RED_ASSERTION: success output must contain exactly one ordered PASS result for every verification stage; evidence=$evidence");
    assertSameValue(0, $result['status'], "All passing stages must make verify exit zero; evidence=$evidence");
    assertSameValue(0, hfaExactLineCount($result, 'FULL_VERIFICATION_FAILURE count=1 stages=db-test'), "Successful verification must not print a failure summary; evidence=$evidence");
    assertSameValue(1, hfaExactLineCount($result, 'VERIFY_OK'), "RED_ASSERTION: successful verification must print exactly one terminal VERIFY_OK; evidence=$evidence");
    assertSameValue(true, str_ends_with(trim($result['stdout']), 'VERIFY_OK'), "RED_ASSERTION: VERIFY_OK must be the terminal stdout line; evidence=$evidence");

    file_put_contents($log, '');
    hfaWriteOverlay($overlay, ['test-db-reset' => 'FAIL']);
    $result = hfaRun($root, $overlay, $bin, $log);
    $observed = is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $expected = [
        'test-db-reset FAIL',
        'architecture-check PASS',
        'lint PASS',
        'unit-test PASS',
        'characterization-test PASS',
        'diff-check PASS',
    ];
    $evidence = json_encode($result + ['observedStages' => $observed], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue($expected, $observed, "RED_ASSERTION: a test-db-reset setup failure must not execute DB-dependent db-test or e2e-test fixtures, while every independent stage still executes in stable order; evidence=$evidence");
    hfaAssertEvidenceVisible($result, [
        'test-db-reset',
        'architecture-check',
        'lint',
        'unit-test',
        'characterization-test',
        'diff-check',
    ], $evidence);
    assertSameValue(1, hfaExactLineCount($result, 'SETUP_FAILURE stage=test-db-reset'), "RED_ASSERTION: the reset blocker must remain visibly classified as SETUP_FAILURE and tied to test-db-reset; evidence=$evidence");
    assertSameValue(1, hfaExactLineCount($result, 'SETUP_FAILURE stage=migrate cause=test-db-reset outcome=SKIP'), "RED_ASSERTION: migrate must report an explicit setup-caused skip and must not execute after reset failure; evidence=$evidence");
    assertSameValue(1, hfaExactLineCount($result, 'SETUP_FAILURE stage=db-test cause=test-db-reset outcome=SKIP'), "RED_ASSERTION: db-test must report an explicit setup-caused skip rather than an assertion regression; evidence=$evidence");
    assertSameValue(1, hfaExactLineCount($result, 'SETUP_FAILURE stage=e2e-test cause=test-db-reset outcome=SKIP'), "RED_ASSERTION: e2e-test must report an explicit setup-caused skip rather than an assertion regression; evidence=$evidence");
    assertSameValue([
        'VERIFY_STAGE test-db-reset FAIL',
        'VERIFY_STAGE migrate FAIL',
        'VERIFY_STAGE architecture-check PASS',
        'VERIFY_STAGE lint PASS',
        'VERIFY_STAGE unit-test PASS',
        'VERIFY_STAGE db-test FAIL',
        'VERIFY_STAGE characterization-test PASS',
        'VERIFY_STAGE e2e-test FAIL',
        'VERIFY_STAGE diff-check PASS',
    ], hfaProtocolLines($result), "RED_ASSERTION: setup-blocked DB-dependent stages must retain explicit FAIL protocol results in stable order; evidence=$evidence");
    assertSameValue(1, hfaExactLineCount($result, 'FULL_VERIFICATION_FAILURE count=4 stages=test-db-reset,migrate,db-test,e2e-test'), "RED_ASSERTION: setup failure summary must count reset, skipped migration, and both blocked DB-dependent stages exactly; evidence=$evidence");
    assertSameValue('FULL_VERIFICATION_FAILURE count=4 stages=test-db-reset,migrate,db-test,e2e-test', array_values(array_filter(explode("\n", trim($result['stdout']))))[array_key_last(array_values(array_filter(explode("\n", trim($result['stdout'])))))], "RED_ASSERTION: setup failure summary must be the terminal stdout line; evidence=$evidence");
    assertSameValue(true, $result['status'] !== 0, "A setup-blocked verification must exit nonzero; evidence=$evidence");
    assertSameValue(0, hfaExactLineCount($result, 'VERIFY_OK'), "A setup-blocked verification must not print VERIFY_OK; evidence=$evidence");

    file_put_contents($log, '');
    hfaWriteOverlay($overlay, ['architecture-check' => 'FAIL', 'unit-test' => 'FAIL']);
    $result = hfaRun($root, $overlay, $bin, $log);
    $observed = is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $expected = [
        'test-db-reset PASS',
        'migrate PASS',
        'architecture-check FAIL',
        'lint PASS',
        'unit-test FAIL',
        'db-test PASS',
        'characterization-test PASS',
        'e2e-test PASS',
        'diff-check PASS',
    ];
    $stageNames = array_map(static fn (string $line): string => explode(' ', $line, 2)[0], $expected);
    $evidence = json_encode($result + ['observedStages' => $observed], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue($expected, $observed, "RED_ASSERTION: two non-adjacent ordinary failures must not stop any of the nine verification stages; evidence=$evidence");
    hfaAssertEvidenceVisible($result, $stageNames, $evidence);
    assertSameValue(array_map(static fn (string $line): string => 'VERIFY_STAGE ' . $line, $expected), hfaProtocolLines($result), "RED_ASSERTION: two ordinary failures must each have exactly one ordered protocol result alongside every attempted stage; evidence=$evidence");
    assertSameValue(1, hfaExactLineCount($result, 'FULL_VERIFICATION_FAILURE count=2 stages=architecture-check,unit-test'), "RED_ASSERTION: terminal summary must count and name both ordinary failures in stage order; evidence=$evidence");
    assertSameValue('FULL_VERIFICATION_FAILURE count=2 stages=architecture-check,unit-test', array_values(array_filter(explode("\n", trim($result['stdout']))))[array_key_last(array_values(array_filter(explode("\n", trim($result['stdout'])))))], "RED_ASSERTION: two-failure summary must be the terminal stdout line; evidence=$evidence");
    assertSameValue(true, $result['status'] !== 0, "Two failed required stages must make verify exit nonzero; evidence=$evidence");
    assertSameValue(0, hfaExactLineCount($result, 'VERIFY_OK'), "A verification with two ordinary failures must not print VERIFY_OK; evidence=$evidence");
    echo "ok - HARNESS-FULL-AGGREGATION-001 aggregates a middle regression across all nine stages\n";
} finally {
    foreach ([$bin . '/stage', $bin . '/git', $overlay, $log] as $path) {
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
