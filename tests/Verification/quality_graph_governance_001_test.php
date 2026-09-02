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

function qggRemoveFixture(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($items as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}

$root = dirname(__DIR__, 2);
$head = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse HEAD'));
assertSameValue(1, preg_match('/^[0-9a-f]{40}$/', $head), 'SETUP_FAILURE: test requires a Git checkout');

$fixture = sys_get_temp_dir() . '/fmonitor-qgg-' . bin2hex(random_bytes(8));
if (!mkdir($fixture, 0700, true)) {
    throw new TestFailure('SETUP_FAILURE: cannot create isolated Git fixture');
}
try {
    foreach ([
        ['git', 'init', '--quiet'],
        ['git', 'config', 'user.email', 'qgg-fixture@example.invalid'],
        ['git', 'config', 'user.name', 'QGG Fixture'],
    ] as $command) {
        $setup = qggRun($command, $fixture);
        assertSameValue(0, $setup['status'], 'SETUP_FAILURE: isolated Git fixture initialization failed');
    }
    file_put_contents($fixture . '/README.md', "fixture\n");
    foreach ([['git', 'add', 'README.md'], ['git', 'commit', '--quiet', '-m', 'fixture base']] as $command) {
        $setup = qggRun($command, $fixture);
        assertSameValue(0, $setup['status'], 'SETUP_FAILURE: isolated Git fixture commit failed');
    }

    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $fixture], $fixture);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    assertSameValue(true, $result['status'] !== 0, "RED_ASSERTION: missing receipt inventory must fail closed; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=missing_receipt receipt=delivery\/evidence detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: isolated test seam must classify the absent opt-in receipt root; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "A failed governance run must never print success; evidence=$evidence");

    mkdir($fixture . '/delivery/evidence/unsafe', 0700, true);
    $unsafeReceipt = [
        'schemaVersion' => 1,
        'sliceId' => 'UNSAFE-001',
        'change' => 'unsafe-fixture',
        'receiptId' => 'unsafe-v1',
        'supersedes' => null,
        'baseCommit' => $head,
        'authors' => ['spec' => 'agent:/spec', 'test' => 'agent:/test', 'implementation' => 'agent:/implementation'],
        'artifacts' => [
            'spec' => ['path' => '../outside.md', 'sha256' => str_repeat('a', 64)],
            'tests' => [],
            'red' => ['path' => 'red.md', 'sha256' => str_repeat('b', 64)],
            'testReview' => ['path' => 'test-review.md', 'sha256' => str_repeat('c', 64), 'reviewer' => 'agent:/review-test', 'verdict' => 'APPROVED', 'specSha256' => str_repeat('a', 64)],
            'green' => ['path' => 'green.md', 'sha256' => str_repeat('d', 64)],
            'codeReview' => ['path' => 'code-review.md', 'sha256' => str_repeat('e', 64), 'reviewer' => 'agent:/review-code', 'verdict' => 'APPROVED', 'specSha256' => str_repeat('a', 64), 'reviewedCommit' => $head],
        ],
    ];
    file_put_contents(
        $fixture . '/delivery/evidence/unsafe/unsafe-v1.json',
        json_encode($unsafeReceipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
    );
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $fixture], $fixture);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(true, $result['status'] !== 0, "An unsafe artifact path must exit nonzero; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=unsafe_path receipt=delivery\/evidence\/unsafe\/unsafe-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: escaping artifact path must be rejected before artifact access; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Unsafe path rejection must never print success; evidence=$evidence");

    $unsafeReceipt['artifacts']['spec']['path'] = 'specs/missing.md';
    file_put_contents(
        $fixture . '/delivery/evidence/unsafe/unsafe-v1.json',
        json_encode($unsafeReceipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
    );
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $fixture], $fixture);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(true, $result['status'] !== 0, "A missing artifact must exit nonzero; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=missing_artifact receipt=delivery\/evidence\/unsafe\/unsafe-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: missing safe artifact path must be classified; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Missing artifact rejection must never print success; evidence=$evidence");

    mkdir($fixture . '/specs');
    file_put_contents($fixture . '/specs/missing.md', "present but changed\n");
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $fixture], $fixture);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(true, $result['status'] !== 0, "A hash mismatch must exit nonzero; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=hash_mismatch receipt=delivery\/evidence\/unsafe\/unsafe-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: present artifact with wrong SHA-256 must be classified; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Hash mismatch must never print success; evidence=$evidence");
} finally {
    qggRemoveFixture($fixture);
}

echo "QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED\n";
