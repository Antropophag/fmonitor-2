<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** QUALITY-GRAPH-GOVERNANCE-001 v0.6, untrusted runner security RED. */

function qgrsRemove(string $path): void
{
    if (!is_dir($path)) return;
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    rmdir($path);
}

function qgrsRun(string $root, string $fixture): array
{
    $process = proc_open(['php', $root . '/tools/delivery/check-quality-graph.php', '--repo', $fixture], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $fixture);
    if (!is_resource($process)) throw new TestFailure('SETUP_FAILURE: runner security validator did not start');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return ['status' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function qgrsCopyFixture(string $root, string $fixture): void
{
    $files = [
        'quality-graph.yml', 'pyproject.toml', '.quality-graph/manifest.json',
        '.quality-graph/generated-publisher-v0.1.7.yml',
        '.github/workflows/quality-graph.yml', '.github/workflows/quality-graph-push.yml',
        '.github/workflows/quality-graph-publish.yml',
    ];
    foreach ($files as $relative) {
        $target = $fixture . '/' . $relative;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0700, true) && !is_dir(dirname($target))) throw new TestFailure("SETUP_FAILURE: cannot create fixture for $relative");
        if (!copy($root . '/' . $relative, $target)) throw new TestFailure("SETUP_FAILURE: cannot copy $relative");
    }
}

function qgrsRejects(string $root, string $fixture, string $case): void
{
    $result = qgrsRun($root, $fixture);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(true, $result['status'] !== 0, "$case must fail; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^QUALITY_GRAPH_VALIDATION_FAILURE category=runner_security detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: $case must be classified before generated parity; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^QUALITY_GRAPH_VALIDATION_OK /m', $combined), "$case must not print success");
}

$root = dirname(__DIR__, 2);
$artifactParent = $root . '/.local/test-artifacts';
if (!is_dir($artifactParent) && !mkdir($artifactParent, 0700, true) && !is_dir($artifactParent)) throw new TestFailure('SETUP_FAILURE: cannot create repository-local artifact root');
$fixture = $artifactParent . '/quality-graph-runner-security-' . bin2hex(random_bytes(8));
$runners = ['.github/workflows/quality-graph.yml', '.github/workflows/quality-graph-push.yml'];

try {
    foreach ($runners as $runner) {
        $mutations = [
            'floating third-party action' => static fn(string $yaml): string => preg_replace('/uses: ([^\s@]+)@[0-9a-f]{40}/', 'uses: $1@main', $yaml, 1) ?? '',
            'persisted checkout credential' => static fn(string $yaml): string => str_replace("persist-credentials: 'false'", "persist-credentials: 'true'", $yaml),
            'top-level write permission' => static fn(string $yaml): string => preg_replace('/permissions:\n  contents: read/', "permissions:\n  contents: write", $yaml, 1) ?? '',
            'job write permission' => static fn(string $yaml): string => preg_replace('/    permissions:\n      contents: read/', "    permissions:\n      contents: read\n      checks: write", $yaml, 1) ?? '',
        ];
        foreach ($mutations as $name => $mutate) {
            qgrsCopyFixture($root, $fixture);
            $path = $fixture . '/' . $runner;
            $original = (string) file_get_contents($path);
            $changed = $mutate($original);
            assertSameValue(false, $changed === $original || $changed === '', "SETUP_FAILURE: $name mutation must change $runner");
            assertSameValue(true, file_put_contents($path, $changed) !== false, "SETUP_FAILURE: cannot write $name mutation");
            qgrsRejects($root, $fixture, "$runner $name");
            qgrsRemove($fixture);
        }
    }

    qgrsCopyFixture($root, $fixture);
    file_put_contents($fixture . '/.github/workflows/quality-graph-push.yml', "# parity drift\n", FILE_APPEND);
    $result = qgrsRun($root, $fixture);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    assertSameValue(true, $result['status'] !== 0, 'Generated runner parity drift must remain blocking');
    assertSameValue(1, preg_match_all('/^QUALITY_GRAPH_VALIDATION_FAILURE category=generated_drift detail=[^\r\n]+$/m', $combined), 'Generated runner parity must remain owned by generated_drift validation');
} finally {
    qgrsRemove($fixture);
}

echo "QUALITY-GRAPH-RUNNER-SECURITY-001 TESTS PASSED\n";
