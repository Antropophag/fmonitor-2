<?php
declare(strict_types=1);

function qgvFail(string $category, string $detail): never
{
    $detail = substr(str_replace(["\r", "\n"], ' ', $detail), 0, 240);
    fwrite(STDERR, "QUALITY_GRAPH_VALIDATION_FAILURE category=$category detail=$detail\n");
    exit(1);
}

function qgvRemove(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}

$sourceRoot = dirname(__DIR__, 2);
$repository = $sourceRoot;
if ($argc === 3 && $argv[1] === '--repo') {
    $resolved = realpath($argv[2]);
    if ($resolved === false || !is_dir($resolved)) {
        qgvFail('invalid_input', 'test repository root is invalid');
    }
    $repository = $resolved;
} elseif ($argc !== 1) {
    qgvFail('invalid_input', 'usage: check-quality-graph.php [--repo TEST_REPOSITORY]');
}

$relativeFiles = [
    'quality-graph.yml',
    'pyproject.toml',
    '.quality-graph/manifest.json',
    '.quality-graph/generated-publisher-v0.1.7.yml',
    '.github/workflows/quality-graph.yml',
    '.github/workflows/quality-graph-push.yml',
    '.github/workflows/quality-graph-publish.yml',
];
foreach ($relativeFiles as $relative) {
    $path = $repository . '/' . $relative;
    if (!is_file($path) || is_link($path)) {
        qgvFail('missing_generated_file', "$relative is absent or unsafe");
    }
}

foreach (['.github/workflows/quality-graph.yml', '.github/workflows/quality-graph-push.yml'] as $runnerRelative) {
    $runner = (string) file_get_contents($repository . '/' . $runnerRelative);
    preg_match_all('/^\s*-?\s*uses:\s*([^\s]+)$/m', $runner, $uses);
    if ($uses[1] === []) {
        qgvFail('runner_security', "$runnerRelative has no pinned third-party actions");
    }
    foreach ($uses[1] as $action) {
        if (preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+@[0-9a-f]{40}$/D', $action) !== 1) {
            qgvFail('runner_security', "$runnerRelative contains a floating or invalid action ref");
        }
    }
    $checkoutCount = preg_match_all('/^\s*-\s*uses:\s*actions\/checkout@[0-9a-f]{40}$/m', $runner);
    $disabledCredentialCount = preg_match_all("/^\s+persist-credentials: 'false'$/m", $runner);
    if ($checkoutCount < 1 || $checkoutCount !== $disabledCredentialCount) {
        qgvFail('runner_security', "$runnerRelative must disable credentials for every checkout");
    }
    if (preg_match_all('/^permissions:\n  contents: read$/m', $runner) !== 1) {
        qgvFail('runner_security', "$runnerRelative top-level permissions must be contents read only");
    }
    if (preg_match('/^    permissions:\n(?!      contents: read\n    steps:)/m', $runner) === 1
        || preg_match('/^      (?!contents: read$)[a-z-]+:\s*(?:read|write)$/m', $runner) === 1
    ) {
        qgvFail('runner_security', "$runnerRelative job permissions must be contents read only");
    }
}

$project = (string) file_get_contents($repository . '/pyproject.toml');
$withoutApprovedPackages = str_replace(
    ['quality-graph-cli==0.1.7', 'quality-graph-github==0.1.7'],
    '',
    $project,
    $approvedPackageCount,
);
if ($approvedPackageCount !== 2 || preg_match('/quality-graph-(?:cli|github)/', $withoutApprovedPackages) === 1) {
    qgvFail('toolchain_pin_drift', 'project must contain only the exact approved Quality Graph package set');
}

$baseline = (string) file_get_contents($repository . '/.quality-graph/generated-publisher-v0.1.7.yml');
$issueTrigger = "  issue_comment:\n    types:\n      - created\n      - edited\n";
$publishWrites = "      actions: read\n      checks: write\n      contents: read\n      issues: write\n      pull-requests: write\n    steps:\n";
$publishReduced = "      actions: read\n      checks: write\n      contents: read\n    steps:\n";
$commandJob = <<<'YAML'
  command:
    name: Handle Quality Graph command
    if: github.event_name == 'issue_comment' && github.event.issue.pull_request
    runs-on: ubuntu-latest
    permissions:
      actions: write
      contents: read
      issues: write
      pull-requests: write
    steps:
      - name: Handle trusted Quality Graph command
        uses: alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef
        with:
          operation: command
YAML;
$commandJob .= "\n";
if (substr_count($baseline, $issueTrigger) !== 1 || substr_count($baseline, $publishWrites) !== 1 || substr_count($baseline, $commandJob) !== 1) {
    qgvFail('publisher_baseline_drift', 'generated publisher no longer matches the reviewed v0.1.7 transformation source');
}
$expected = str_replace($issueTrigger, '', $baseline);
$expected = str_replace($commandJob, '', $expected);
$expected = str_replace($publishWrites, $publishReduced, $expected);
$publisher = (string) file_get_contents($repository . '/.github/workflows/quality-graph-publish.yml');
if (!hash_equals(hash('sha256', $expected), hash('sha256', $publisher))) {
    qgvFail('publisher_override_drift', 'deployable publisher differs from the allowlisted privilege-removal transformation');
}

$temporary = sys_get_temp_dir() . '/fmonitor-qgv-' . bin2hex(random_bytes(8));
try {
    foreach ($relativeFiles as $relative) {
        $target = $temporary . '/' . $relative;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0700, true) && !is_dir(dirname($target))) {
            qgvFail('setup_failure', "cannot create validation directory for $relative");
        }
        $source = $relative === '.github/workflows/quality-graph-publish.yml'
            ? $repository . '/.quality-graph/generated-publisher-v0.1.7.yml'
            : $repository . '/' . $relative;
        if (!copy($source, $target)) {
            qgvFail('setup_failure', "cannot stage $relative");
        }
    }
    $qg = $sourceRoot . '/.venv/bin/qg';
    if (!is_file($qg)) {
        $which = proc_open(['sh', '-c', 'command -v qg'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $whichPipes);
        if (is_resource($which)) {
            fclose($whichPipes[0]);
            $qg = trim((string) stream_get_contents($whichPipes[1]));
            fclose($whichPipes[1]); fclose($whichPipes[2]); proc_close($which);
        }
    }
    if ($qg === '' || !is_file($qg)) {
        qgvFail('setup_failure', 'run uv sync before Quality Graph validation');
    }
    $process = proc_open([$qg, 'validate'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $temporary);
    if (!is_resource($process)) {
        qgvFail('setup_failure', 'qg validate did not start');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        qgvFail('generated_drift', trim($stdout . ' ' . $stderr));
    }
} finally {
    qgvRemove($temporary);
}

$manifest = json_decode((string) file_get_contents($repository . '/.quality-graph/manifest.json'), true);
$digest = is_array($manifest) ? ($manifest['graphDigest'] ?? null) : null;
if (!is_string($digest) || preg_match('/^[0-9a-f]{64}$/D', $digest) !== 1) {
    qgvFail('invalid_manifest', 'graphDigest is absent or invalid');
}
echo "QUALITY_GRAPH_VALIDATION_OK digest=$digest\n";
