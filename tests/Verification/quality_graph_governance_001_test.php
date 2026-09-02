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
    foreach ([['git', 'add', 'specs/missing.md'], ['git', 'commit', '--quiet', '-m', 'add governed spec']] as $command) {
        $setup = qggRun($command, $fixture);
        assertSameValue(0, $setup['status'], 'SETUP_FAILURE: governed spec fixture commit failed');
    }
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $fixture], $fixture);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(true, $result['status'] !== 0, "A hash mismatch must exit nonzero; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=hash_mismatch receipt=delivery\/evidence\/unsafe\/unsafe-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: present artifact with wrong SHA-256 must be classified; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Hash mismatch must never print success; evidence=$evidence");
} finally {
    qggRemoveFixture($fixture);
}

$lineage = sys_get_temp_dir() . '/fmonitor-qgg-lineage-' . bin2hex(random_bytes(8));
if (!mkdir($lineage, 0700, true)) {
    throw new TestFailure('SETUP_FAILURE: cannot create lineage fixture');
}
try {
    $git = static function (array $arguments) use ($lineage): string {
        $result = qggRun(array_merge(['git'], $arguments), $lineage);
        assertSameValue(0, $result['status'], 'SETUP_FAILURE: git fixture command failed: ' . json_encode($result));
        return trim($result['stdout']);
    };
    $write = static function (string $path, string $contents) use ($lineage): void {
        $target = $lineage . '/' . $path;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0700, true) && !is_dir(dirname($target))) {
            throw new TestFailure("SETUP_FAILURE: cannot create fixture directory for $path");
        }
        file_put_contents($target, $contents);
    };
    $metadata = static fn (array $value): string => "```delivery-metadata\n"
        . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        . "\n```\n\nfixture\n";
    $git(['init', '--quiet']);
    $git(['config', 'user.email', 'lineage@example.invalid']);
    $git(['config', 'user.name', 'Lineage Fixture']);
    $write('README.md', "base\n");
    $git(['add', '.']);
    $git(['commit', '--quiet', '-m', 'base']);
    $base = $git(['rev-parse', 'HEAD']);

    $specPath = 'specs/LINEAGE-001.md';
    $testPath = 'tests/lineage_test.php';
    $redPath = 'docs/red.md';
    $testReviewPath = 'reviews/tests/LINEAGE-001.md';
    $greenPath = 'docs/green.md';
    $implementationPath = 'tools/delivery/lineage-fixture.txt';
    $codeReviewPath = 'reviews/code/LINEAGE-001.md';
    $spec = $metadata(['schemaVersion' => 1, 'kind' => 'spec', 'sliceId' => 'LINEAGE-001', 'author' => 'agent:/spec']);
    $write($specPath, $spec);
    $write($testPath, "<?php echo 'fixture';\n");
    $specHash = hash('sha256', $spec);
    $testHash = hash_file('sha256', $lineage . '/' . $testPath);
    $tests = [['path' => $testPath, 'status' => 'A', 'sha256' => $testHash]];
    $write($redPath, $metadata([
        'schemaVersion' => 1, 'kind' => 'red', 'sliceId' => 'LINEAGE-001', 'author' => 'agent:/test',
        'specPath' => $specPath, 'specSha256' => $specHash, 'baseCommit' => $base, 'tests' => $tests,
        'command' => 'php tests/lineage_test.php', 'observedFailure' => 'fixture red', 'recordedAt' => '2026-09-03T00:00:00Z',
    ]));
    $git(['add', '.']);
    $git(['commit', '--quiet', '-m', 'red']);
    $redCommit = $git(['rev-parse', 'HEAD']);

    $write($testReviewPath, $metadata([
        'schemaVersion' => 1, 'kind' => 'test-review', 'sliceId' => 'LINEAGE-001', 'reviewer' => 'agent:/test-reviewer',
        'verdict' => 'APPROVED', 'specSha256' => $specHash, 'tests' => $tests, 'redCommit' => $redCommit,
        'recordedAt' => '2026-09-03T00:01:00Z',
    ]));
    $git(['add', '.']);
    $git(['commit', '--quiet', '-m', 'test review']);

    $write($implementationPath, "implementation\n");
    $implementationFiles = [['path' => $implementationPath, 'status' => 'A', 'sha256' => hash_file('sha256', $lineage . '/' . $implementationPath)]];
    $write($greenPath, $metadata([
        'schemaVersion' => 1, 'kind' => 'green', 'sliceId' => 'LINEAGE-001', 'author' => 'agent:/implementation',
        'specSha256' => $specHash, 'tests' => $tests, 'testReviewRecordPath' => $testReviewPath,
        'implementationFiles' => $implementationFiles, 'commands' => ['php tests/lineage_test.php'], 'recordedAt' => '2026-09-03T00:02:00Z',
    ]));
    $git(['add', '.']);
    $git(['commit', '--quiet', '-m', 'green']);
    $implementationCommit = $git(['rev-parse', 'HEAD']);

    $write($codeReviewPath, $metadata([
        'schemaVersion' => 1, 'kind' => 'code-review', 'sliceId' => 'LINEAGE-001', 'reviewer' => 'agent:/code-reviewer',
        'verdict' => 'APPROVED', 'specSha256' => $specHash, 'tests' => $tests,
        'implementationCommit' => $implementationCommit, 'implementationFiles' => $implementationFiles,
        'recordedAt' => '2026-09-03T00:03:00Z',
    ]));
    $git(['add', '.']);
    $git(['commit', '--quiet', '-m', 'code review']);

    $receipt = [
        'schemaVersion' => 1, 'sliceId' => 'LINEAGE-001', 'change' => 'lineage-fixture', 'receiptId' => 'lineage-v1',
        'supersedes' => null, 'baseCommit' => $base,
        'authors' => ['spec' => 'agent:/spec', 'test' => 'agent:/test', 'implementation' => 'agent:/implementation'],
        'artifacts' => [
            'spec' => ['path' => $specPath, 'sha256' => $specHash], 'tests' => $tests,
            'red' => ['path' => $redPath, 'sha256' => hash_file('sha256', $lineage . '/' . $redPath)],
            'testReview' => ['path' => $testReviewPath, 'sha256' => hash_file('sha256', $lineage . '/' . $testReviewPath), 'reviewer' => 'agent:/test-reviewer', 'verdict' => 'APPROVED', 'specSha256' => $specHash],
            'green' => ['path' => $greenPath, 'sha256' => hash_file('sha256', $lineage . '/' . $greenPath)],
            'codeReview' => ['path' => $codeReviewPath, 'sha256' => hash_file('sha256', $lineage . '/' . $codeReviewPath), 'reviewer' => 'agent:/code-reviewer', 'verdict' => 'APPROVED', 'specSha256' => $specHash, 'reviewedCommit' => $implementationCommit],
        ],
    ];
    $write('delivery/evidence/LINEAGE-001/lineage-v1.json', json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    $git(['add', '.']);
    $git(['commit', '--quiet', '-m', 'receipt']);
    $lineageHead = $git(['rev-parse', 'HEAD']);

    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $lineage], $lineage);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(0, $result['status'], "RED_ASSERTION: complete independently reviewed lineage must pass; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_OK receipts=1 head=' . preg_quote($lineageHead, '/') . '$/m', $combined), "Valid lineage must emit exact success; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "Valid lineage must emit no failure; evidence=$evidence");
    $stdoutLines = array_values(array_filter(explode("\n", trim($result['stdout'])), static fn (string $line): bool => $line !== ''));
    assertSameValue('DELIVERY_EVIDENCE_OK receipts=1 head=' . $lineageHead, $stdoutLines[array_key_last($stdoutLines)] ?? null, "Success must be the terminal nonempty stdout line; evidence=$evidence");

    $receipt['receiptId'] = 'duplicate-v1';
    $write('delivery/evidence/ZZZ-DUPLICATE-001/duplicate-v1.json', json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $lineage], $lineage);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(true, $result['status'] !== 0, "Duplicate slice identity must fail; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=duplicate_slice receipt=delivery\/evidence\/ZZZ-DUPLICATE-001\/duplicate-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: later duplicate slice claimant must be classified in bytewise discovery order; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Duplicate slice failure must not print success; evidence=$evidence");

    unlink($lineage . '/delivery/evidence/ZZZ-DUPLICATE-001/duplicate-v1.json');
    rmdir($lineage . '/delivery/evidence/ZZZ-DUPLICATE-001');
    $receipt['receiptId'] = 'lineage-v2';
    $receipt['supersedes'] = 'lineage-v1';
    $write('delivery/evidence/LINEAGE-001/lineage-v2.json', json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    $git(['add', '.']);
    $git(['commit', '--quiet', '-m', 'superseding receipt']);
    $supersessionHead = $git(['rev-parse', 'HEAD']);
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $lineage], $lineage);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(0, $result['status'], "RED_ASSERTION: one immutable supersession chain must be accepted; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_OK receipts=1 head=' . preg_quote($supersessionHead, '/') . '$/m', $combined), "Only the current receipt leaf must be counted; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "Valid supersession must emit no failure; evidence=$evidence");
    $stdoutLines = array_values(array_filter(explode("\n", trim($result['stdout'])), static fn (string $line): bool => $line !== ''));
    assertSameValue('DELIVERY_EVIDENCE_OK receipts=1 head=' . $supersessionHead, $stdoutLines[array_key_last($stdoutLines)] ?? null, "Supersession success must be terminal stdout; evidence=$evidence");

    $write($implementationPath, "post-review mutation\n");
    $git(['add', $implementationPath]);
    $git(['commit', '--quiet', '-m', 'forbidden post-review implementation drift']);
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $lineage], $lineage);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(true, $result['status'] !== 0, "Post-review implementation drift must fail; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=commit_mismatch receipt=delivery\/evidence\/LINEAGE-001\/lineage-v2\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: governed file changed after reviewed implementation commit must be rejected against the unique current leaf; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "Post-review drift must emit exactly one deterministic failure; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Post-review drift must not print success; evidence=$evidence");
} finally {
    qggRemoveFixture($lineage);
}

echo "QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED\n";
