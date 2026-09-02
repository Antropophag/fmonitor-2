<?php
declare(strict_types=1);

function deliveryFailure(string $category, string $receipt, string $detail): never
{
    $bounded = substr(str_replace(["\r", "\n"], ' ', $detail), 0, 240);
    fwrite(STDERR, "DELIVERY_EVIDENCE_FAILURE category=$category receipt=$receipt detail=$bounded\n");
    exit(1);
}

$repository = dirname(__DIR__, 2);
if ($argc === 3 && $argv[1] === '--repo') {
    $candidate = realpath($argv[2]);
    if ($candidate === false || !is_dir($candidate)) {
        deliveryFailure('invalid_input', 'delivery/evidence', 'test repository root is not a readable directory');
    }
    $repository = $candidate;
} elseif ($argc !== 1) {
    deliveryFailure('invalid_input', 'delivery/evidence', 'usage: check-evidence.php [--repo TEST_REPOSITORY]');
}

$git = proc_open(
    ['git', 'rev-parse', 'HEAD'],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    $repository,
);
if (!is_resource($git)) {
    deliveryFailure('invalid_input', 'delivery/evidence', 'git could not start');
}
fclose($pipes[0]);
$head = trim((string) stream_get_contents($pipes[1]));
$gitError = trim((string) stream_get_contents($pipes[2]));
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($git) !== 0 || preg_match('/^[0-9a-f]{40,64}$/D', $head) !== 1) {
    deliveryFailure('invalid_input', 'delivery/evidence', $gitError !== '' ? $gitError : 'repository HEAD is invalid');
}

$receiptRoot = $repository . '/delivery/evidence';
if (!is_dir($receiptRoot)) {
    deliveryFailure('missing_receipt', 'delivery/evidence', 'receipt directory is absent');
}
$receipts = glob($receiptRoot . '/*/*.json', GLOB_NOSORT);
if ($receipts === false || $receipts === []) {
    deliveryFailure('missing_receipt', 'delivery/evidence', 'no receipt JSON files discovered');
}

deliveryFailure('invalid_schema', 'delivery/evidence', 'receipt validation is not implemented by this tracer slice');
