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

function deliveryGit(string $repository, array $arguments, string $receipt): string
{
    $process = proc_open(array_merge(['git'], $arguments), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $repository);
    if (!is_resource($process)) deliveryFailure('invalid_input', $receipt, 'git command did not start');
    fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) deliveryFailure('commit_mismatch', $receipt, trim((string) $stderr));
    return trim((string) $stdout);
}

function deliveryMetadata(string $contents, string $receipt, string $kind): array
{
    if (preg_match('/\A```delivery-metadata\R([^\r\n]+)\R```\R/', $contents, $match) !== 1) deliveryFailure('metadata_mismatch', $receipt, "$kind metadata block is absent");
    try { $metadata = json_decode($match[1], true, 32, JSON_THROW_ON_ERROR); } catch (JsonException) { deliveryFailure('metadata_mismatch', $receipt, "$kind metadata is malformed"); }
    if (!is_array($metadata) || ($metadata['kind'] ?? null) !== $kind) deliveryFailure('metadata_mismatch', $receipt, "$kind metadata kind differs");
    return $metadata;
}

function deliveryFirstBlobCommit(string $repository, string $path, string $hash, string $receipt): string
{
    $commits = array_values(array_filter(explode("\n", deliveryGit($repository, ['log', '--all', '--format=%H', '--diff-filter=A', '--', $path], $receipt))));
    $matches = [];
    foreach ($commits as $commit) {
        $blob = deliveryGit($repository, ['show', "$commit:$path"], $receipt) . "\n";
        if (hash('sha256', $blob) === $hash) $matches[] = $commit;
    }
    if (count($matches) !== 1) deliveryFailure('gate_order', $receipt, "$path has no unique first matching blob commit");
    return $matches[0];
}

function deliveryAncestor(string $repository, string $older, string $newer, string $receipt): void
{
    if ($older === $newer) deliveryFailure('gate_order', $receipt, 'required gate commits are equal');
    $process = proc_open(['git', 'merge-base', '--is-ancestor', $older, $newer], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $repository);
    if (!is_resource($process)) deliveryFailure('invalid_input', $receipt, 'git ancestry check did not start');
    foreach ($pipes as $pipe) fclose($pipe);
    if (proc_close($process) !== 0) deliveryFailure('gate_order', $receipt, "$older is not an ancestor of $newer");
}

function deliveryDiffSet(string $repository, string $from, string $to, ?string $pathspec, array $excluded, string $receipt): array
{
    $arguments = ['diff', '--no-renames', '--name-status', "$from..$to"];
    if ($pathspec !== null) array_push($arguments, '--', $pathspec);
    $result = [];
    foreach (array_filter(explode("\n", deliveryGit($repository, $arguments, $receipt))) as $line) {
        [$status, $path] = explode("\t", $line, 2);
        if (in_array($path, $excluded, true)) continue;
        if (!in_array($status, ['A', 'M', 'D'], true)) deliveryFailure('unsafe_path', $receipt, "$path has unsupported Git status $status");
        $result[] = ['path' => $path, 'status' => $status, 'sha256' => $status === 'D' ? null : hash('sha256', deliveryGit($repository, ['show', "$to:$path"], $receipt) . "\n")];
    }
    usort($result, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
    return $result;
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
$sliceHistories = [];
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
    if (!is_string($data['sliceId']) || $data['sliceId'] === '') {
        deliveryFailure('invalid_schema', $receipt, 'sliceId must be a nonempty string');
    }
    $sliceDirectory = dirname($receipt);
    if (isset($sliceHistories[$data['sliceId']]) && $sliceHistories[$data['sliceId']]['directory'] !== $sliceDirectory) {
        deliveryFailure('duplicate_slice', $receipt, 'sliceId already claimed under ' . $sliceHistories[$data['sliceId']]['directory']);
    }
    if (!isset($sliceHistories[$data['sliceId']])) {
        $sliceHistories[$data['sliceId']] = ['directory' => $sliceDirectory, 'receipts' => []];
    }
    if (!is_string($data['receiptId']) || preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/D', $data['receiptId']) !== 1 || isset($sliceHistories[$data['sliceId']]['receipts'][$data['receiptId']])) {
        deliveryFailure('invalid_history', $receipt, 'receiptId is invalid or reused');
    }
    if ($data['supersedes'] !== null && !is_string($data['supersedes'])) {
        deliveryFailure('invalid_history', $receipt, 'supersedes must be null or a receiptId');
    }
    $sliceHistories[$data['sliceId']]['receipts'][$data['receiptId']] = ['supersedes' => $data['supersedes'], 'path' => $receipt, 'absolute' => $receiptPath];
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
    if (is_link($specPath) || !is_file($specPath)) {
        deliveryFailure('unsafe_path', $receipt, 'specification artifact must be a regular non-symlink file');
    }
    $expectedHash = $data['artifacts']['spec']['sha256'];
    if (!is_string($expectedHash) || preg_match('/^[0-9a-f]{64}$/D', $expectedHash) !== 1) {
        deliveryFailure('invalid_schema', $receipt, 'artifacts.spec.sha256 must be lowercase SHA-256');
    }
    if (!hash_equals($expectedHash, hash_file('sha256', $specPath))) {
        deliveryFailure('hash_mismatch', $receipt, 'specification artifact digest differs');
    }
    $schemas = ['red' => ['path', 'sha256'], 'testReview' => ['path', 'sha256', 'reviewer', 'verdict', 'specSha256'], 'green' => ['path', 'sha256'], 'codeReview' => ['path', 'sha256', 'reviewer', 'verdict', 'specSha256', 'reviewedCommit']];
    $contents = ['spec' => (string) file_get_contents($specPath)];
    foreach ($schemas as $kind => $keys) {
        $artifact = $data['artifacts'][$kind];
        if (!is_array($artifact)) deliveryFailure('invalid_schema', $receipt, "artifacts.$kind must be an object");
        deliveryExactKeys($artifact, $keys, $receipt, "artifacts.$kind");
        if (!is_string($artifact['path'])) deliveryFailure('invalid_schema', $receipt, "artifacts.$kind.path must be a string");
        deliverySafePath($artifact['path'], $receipt); $path = $repository . '/' . $artifact['path'];
        if (!is_file($path) || is_link($path)) deliveryFailure('missing_artifact', $receipt, "$kind artifact is absent or unsafe");
        if (!is_string($artifact['sha256']) || !hash_equals($artifact['sha256'], hash_file('sha256', $path))) deliveryFailure('hash_mismatch', $receipt, "$kind artifact digest differs");
        $contents[$kind] = (string) file_get_contents($path);
    }
    $metadata = [];
    foreach ($contents as $kind => $value) $metadata[$kind] = deliveryMetadata($value, $receipt, $kind === 'testReview' ? 'test-review' : ($kind === 'codeReview' ? 'code-review' : $kind));
    if (($metadata['spec']['author'] ?? null) !== $data['authors']['spec'] || ($metadata['red']['author'] ?? null) !== $data['authors']['test'] || ($metadata['green']['author'] ?? null) !== $data['authors']['implementation']) deliveryFailure('metadata_mismatch', $receipt, 'artifact authors differ');
    $specHash = $data['artifacts']['spec']['sha256'];
    foreach (['red', 'testReview', 'green', 'codeReview'] as $kind) if (($metadata[$kind]['specSha256'] ?? null) !== $specHash) deliveryFailure('stale_spec', $receipt, "$kind spec digest is stale");
    foreach (['testReview', 'codeReview'] as $kind) {
        $artifact = $data['artifacts'][$kind];
        if (($metadata[$kind]['reviewer'] ?? null) !== $artifact['reviewer'] || ($metadata[$kind]['verdict'] ?? null) !== 'APPROVED' || $artifact['verdict'] !== 'APPROVED') deliveryFailure('metadata_mismatch', $receipt, "$kind review differs");
    }
    if ($data['authors']['test'] === $data['artifacts']['testReview']['reviewer'] || $data['authors']['implementation'] === $data['artifacts']['codeReview']['reviewer']) deliveryFailure('non_independent_review', $receipt, 'reviewer equals author');
    $redCommit = deliveryFirstBlobCommit($repository, $data['artifacts']['red']['path'], $data['artifacts']['red']['sha256'], $receipt);
    $testReviewCommit = deliveryFirstBlobCommit($repository, $data['artifacts']['testReview']['path'], $data['artifacts']['testReview']['sha256'], $receipt);
    $greenCommit = deliveryFirstBlobCommit($repository, $data['artifacts']['green']['path'], $data['artifacts']['green']['sha256'], $receipt);
    $codeReviewCommit = deliveryFirstBlobCommit($repository, $data['artifacts']['codeReview']['path'], $data['artifacts']['codeReview']['sha256'], $receipt);
    foreach ([[$data['baseCommit'], $redCommit], [$redCommit, $testReviewCommit], [$testReviewCommit, $greenCommit], [$greenCommit, $codeReviewCommit]] as [$older, $newer]) deliveryAncestor($repository, $older, $newer, $receipt);
    if (($metadata['testReview']['redCommit'] ?? null) !== $redCommit || ($metadata['codeReview']['implementationCommit'] ?? null) !== $greenCommit || $data['artifacts']['codeReview']['reviewedCommit'] !== $greenCommit) deliveryFailure('commit_mismatch', $receipt, 'reviewed commit differs');
    $tests = deliveryDiffSet($repository, $data['baseCommit'], $redCommit, 'tests/', [], $receipt);
    if ($data['artifacts']['tests'] !== $tests) deliveryFailure('metadata_mismatch', $receipt, 'receipt test set differs');
    foreach (['red', 'testReview', 'green', 'codeReview'] as $kind) if (($metadata[$kind]['tests'] ?? null) !== $tests) deliveryFailure('metadata_mismatch', $receipt, "$kind test set differs");
    $implementation = deliveryDiffSet($repository, $testReviewCommit, $greenCommit, null, [$data['artifacts']['green']['path'], 'openspec/changes/' . $data['change'] . '/tasks.md'], $receipt);
    foreach (['green', 'codeReview'] as $kind) if (($metadata[$kind]['implementationFiles'] ?? null) !== $implementation) deliveryFailure('metadata_mismatch', $receipt, "$kind implementation set differs");
    $sliceHistories[$data['sliceId']]['receipts'][$data['receiptId']]['greenCommit'] = $greenCommit;
    $sliceHistories[$data['sliceId']]['receipts'][$data['receiptId']]['codeReviewPath'] = $data['artifacts']['codeReview']['path'];
    $sliceHistories[$data['sliceId']]['receipts'][$data['receiptId']]['change'] = $data['change'];
}

foreach ($sliceHistories as $history) {
    $referenced = [];
    foreach ($history['receipts'] as $id => $item) {
        if ($item['supersedes'] === null) continue;
        if (!isset($history['receipts'][$item['supersedes']])) deliveryFailure('invalid_history', $item['path'], 'supersedes target is missing');
        $referenced[$item['supersedes']] = true;
        $older = $history['receipts'][$item['supersedes']];
        $olderCommit = deliveryFirstBlobCommit($repository, $older['path'], hash_file('sha256', $older['absolute']), $item['path']);
        $newerCommit = deliveryFirstBlobCommit($repository, $item['path'], hash_file('sha256', $item['absolute']), $item['path']);
        deliveryAncestor($repository, $olderCommit, $newerCommit, $item['path']);
    }
    $leaves = array_diff(array_keys($history['receipts']), array_keys($referenced));
    if (count($leaves) !== 1) deliveryFailure('invalid_history', $history['directory'], 'receipt history must have exactly one current leaf');
    $visited = [];
    $cursor = array_values($leaves)[0];
    while ($cursor !== null) {
        if (isset($visited[$cursor])) deliveryFailure('invalid_history', $history['directory'], 'receipt supersession contains a cycle');
        $visited[$cursor] = true;
        $cursor = $history['receipts'][$cursor]['supersedes'];
    }
    if (count($visited) !== count($history['receipts'])) deliveryFailure('invalid_history', $history['directory'], 'receipt history is disconnected');
    $leaf = $history['receipts'][array_values($leaves)[0]];
    $changedAfterReview = array_filter(explode("\n", deliveryGit($repository, ['diff', '--no-renames', '--name-only', $leaf['greenCommit'] . '..' . $head], $leaf['path'])));
    foreach ($changedAfterReview as $changedPath) {
        $allowed = $changedPath === $leaf['codeReviewPath']
            || str_starts_with($changedPath, $history['directory'] . '/')
            || $changedPath === 'openspec/changes/' . $leaf['change'] . '/tasks.md'
            || str_starts_with($changedPath, 'docs/operations/');
        if (!$allowed) deliveryFailure('commit_mismatch', $leaf['path'], "governed path changed after review: $changedPath");
    }
}

echo 'DELIVERY_EVIDENCE_OK receipts=' . count($sliceHistories) . " head=$head\n";
