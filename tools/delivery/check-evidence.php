<?php
declare(strict_types=1);

function deliveryFailure(string $category, string $receipt, string $detail): never
{
    $bounded = substr(str_replace(["\r", "\n"], ' ', $detail), 0, 240);
    fwrite(STDERR, "DELIVERY_EVIDENCE_FAILURE category=$category receipt=$receipt detail=$bounded\n");
    exit(1);
}

function deliveryExactKeys(array $value, array $expected, string $receipt, string $location): void
{
    $actual = array_keys($value);
    sort($actual, SORT_STRING);
    sort($expected, SORT_STRING);
    if ($actual !== $expected) {
        deliveryFailure('invalid_schema', $receipt, "$location fields do not match schema v1");
    }
}

function deliverySafePath(string $path, string $receipt): void
{
    if ($path === '' || str_starts_with($path, '/') || str_contains($path, "\0")) {
        deliveryFailure('unsafe_path', $receipt, 'artifact path must be normalized and repository-relative');
    }
    $parts = explode('/', str_replace('\\', '/', $path));
    if (in_array('', $parts, true) || in_array('.', $parts, true) || in_array('..', $parts, true)) {
        deliveryFailure('unsafe_path', $receipt, 'artifact path contains an unsafe segment');
    }
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
sort($receipts, SORT_STRING);
foreach ($receipts as $receiptPath) {
    $receipt = substr($receiptPath, strlen($repository) + 1);
    $contents = file_get_contents($receiptPath);
    if ($contents === false || !mb_check_encoding($contents, 'UTF-8')) {
        deliveryFailure('invalid_schema', $receipt, 'receipt must be readable UTF-8 JSON');
    }
    try {
        $data = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        deliveryFailure('invalid_schema', $receipt, 'receipt is malformed JSON');
    }
    if (!is_array($data)) {
        deliveryFailure('invalid_schema', $receipt, 'receipt must be a JSON object');
    }
    deliveryExactKeys($data, ['schemaVersion', 'sliceId', 'change', 'receiptId', 'supersedes', 'baseCommit', 'authors', 'artifacts'], $receipt, 'receipt');
    if ($data['schemaVersion'] !== 1 || !is_array($data['authors']) || !is_array($data['artifacts'])) {
        deliveryFailure('invalid_schema', $receipt, 'receipt schema version or object fields are invalid');
    }
    deliveryExactKeys($data['authors'], ['spec', 'test', 'implementation'], $receipt, 'authors');
    deliveryExactKeys($data['artifacts'], ['spec', 'tests', 'red', 'testReview', 'green', 'codeReview'], $receipt, 'artifacts');
    if (!is_array($data['artifacts']['spec'])) {
        deliveryFailure('invalid_schema', $receipt, 'artifacts.spec must be an object');
    }
    deliveryExactKeys($data['artifacts']['spec'], ['path', 'sha256'], $receipt, 'artifacts.spec');
    if (!is_string($data['artifacts']['spec']['path'])) {
        deliveryFailure('invalid_schema', $receipt, 'artifacts.spec.path must be a string');
    }
    deliverySafePath($data['artifacts']['spec']['path'], $receipt);
    $specPath = $repository . '/' . $data['artifacts']['spec']['path'];
    if (!file_exists($specPath)) {
        deliveryFailure('missing_artifact', $receipt, 'specification artifact is absent');
    }
    deliveryFailure('invalid_schema', $receipt, 'remaining receipt validation is not implemented');
}

deliveryFailure('invalid_schema', 'delivery/evidence', 'unreachable receipt validation state');
