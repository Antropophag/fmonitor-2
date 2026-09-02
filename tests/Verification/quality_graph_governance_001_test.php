<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** QUALITY-GRAPH-GOVERNANCE-001 v0.5, Gate 2 RED. */

function qggRun(array $command, string $cwd, array $environment = []): array
{
    $process = proc_open(
        $command,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $cwd,
        array_replace(is_array(getenv()) ? getenv() : $_ENV, $environment),
    );
    if (!is_resource($process)) {
        throw new TestFailure('SETUP_FAILURE: governance command did not start');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['status' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

$root = dirname(__DIR__, 2);
$head = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse HEAD'));
assertSameValue(1, preg_match('/^[0-9a-f]{40}$/', $head), 'SETUP_FAILURE: test requires a Git checkout');

$result = qggRun(['make', '--no-print-directory', 'delivery-evidence-check'], $root);
$combined = $result['stdout'] . "\n" . $result['stderr'];
$evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

assertSameValue(true, $result['status'] !== 0, "RED_ASSERTION: missing receipt inventory must fail closed; evidence=$evidence");
assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=missing_receipt receipt=delivery\/evidence detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: public seam must classify the absent opt-in receipt root; evidence=$evidence");
assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "A failed governance run must never print success; evidence=$evidence");

echo "QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED\n";
