<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** QUALITY-GRAPH-GOVERNANCE-001 v0.6, current-line Gate 2 RED. */

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

    $readMetadata = static function (string $repository, string $path): array {
        $contents = (string) file_get_contents($repository . '/' . $path);
        assertSameValue(1, preg_match('/\A```delivery-metadata\R([^\r\n]+)/', $contents, $match), "SETUP_FAILURE: metadata missing from $path");
        return [$contents, $match[1], json_decode($match[1], true, 32, JSON_THROW_ON_ERROR)];
    };
    $writeMetadata = static function (string $repository, string $path, string $contents, string $oldJson, array $value): void {
        $new = str_replace($oldJson, json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $contents);
        assertSameValue(true, file_put_contents($repository . '/' . $path, $new) !== false, "SETUP_FAILURE: metadata write $path");
    };
    $mutation = static function (string $name, Closure $change, string $category) use ($root, $lineage, $readMetadata, $writeMetadata): void {
        $copy = sys_get_temp_dir() . '/fmonitor-qgg-current-' . $name . '-' . bin2hex(random_bytes(8));
        try {
            $setup = qggRun(['git', 'clone', '--quiet', $lineage, $copy], $root);
            assertSameValue(0, $setup['status'], "SETUP_FAILURE: $name clone");
            foreach ([['git', 'config', 'user.email', 'current-mutation@example.invalid'], ['git', 'config', 'user.name', 'Current Mutation']] as $command) {
                $setup = qggRun($command, $copy); assertSameValue(0, $setup['status'], "SETUP_FAILURE: $name git config");
            }
            $change($copy, $readMetadata, $writeMetadata);
            foreach ([['git', 'add', '.'], ['git', 'commit', '--quiet', '-m', $name]] as $command) {
                $setup = qggRun($command, $copy); assertSameValue(0, $setup['status'], "SETUP_FAILURE: $name commit");
            }
            $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $copy], $copy);
            $combined = $result['stdout'] . "\n" . $result['stderr']; $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            assertSameValue(true, $result['status'] !== 0, "$name must fail; evidence=$evidence");
            assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=' . preg_quote($category, '/') . ' receipt=delivery\/evidence\/LINEAGE-001(?:\/[^ ]+)? detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: $name must emit exactly one $category; evidence=$evidence");
            assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "$name one failure");
            assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "$name no success");
        } finally { qggRemoveFixture($copy); }
    };

    foreach ([[$testReviewPath, 'agent:/test', 'test reviewer equals test author'], [$codeReviewPath, 'agent:/implementation', 'code reviewer equals implementation author']] as [$reviewPath, $author, $label]) {
        $mutation(str_replace(' ', '-', $label), static function (string $copy, Closure $read, Closure $write) use ($reviewPath, $author): void {
            [$contents, $json, $data] = $read($copy, $reviewPath); $data['reviewer'] = $author; $write($copy, $reviewPath, $contents, $json, $data);
            $receiptPath = $copy . '/delivery/evidence/LINEAGE-001/lineage-v1.json'; $receipt = json_decode((string) file_get_contents($receiptPath), true, 32, JSON_THROW_ON_ERROR);
            $key = str_contains($reviewPath, '/tests/') ? 'testReview' : 'codeReview'; $receipt['artifacts'][$key]['reviewer'] = $author; $receipt['artifacts'][$key]['sha256'] = hash_file('sha256', $copy . '/' . $reviewPath);
            file_put_contents($receiptPath, json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
        }, 'non_independent_review');
    }

    $mutation('invalid-gate-ancestry', static function (string $copy, Closure $read, Closure $write) use ($redPath): void {
        $redCommit = trim((string) shell_exec('git -C ' . escapeshellarg($copy) . ' log --format=%H --diff-filter=A -- ' . escapeshellarg($redPath) . ' | tail -1'));
        [$contents, $json, $data] = $read($copy, $redPath); $data['baseCommit'] = $redCommit; $write($copy, $redPath, $contents, $json, $data);
        $receiptPath = $copy . '/delivery/evidence/LINEAGE-001/lineage-v1.json'; $receipt = json_decode((string) file_get_contents($receiptPath), true, 32, JSON_THROW_ON_ERROR);
        $receipt['baseCommit'] = $redCommit; $receipt['artifacts']['red']['sha256'] = hash_file('sha256', $copy . '/' . $redPath);
        file_put_contents($receiptPath, json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    }, 'gate_order');

    $mutation('multiple-supersession-leaves', static function (string $copy): void {
        $source = json_decode((string) file_get_contents($copy . '/delivery/evidence/LINEAGE-001/lineage-v1.json'), true, 32, JSON_THROW_ON_ERROR);
        foreach (['lineage-v2', 'lineage-v3'] as $id) { $next = $source; $next['receiptId'] = $id; $next['supersedes'] = 'lineage-v1'; file_put_contents($copy . "/delivery/evidence/LINEAGE-001/$id.json", json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"); }
    }, 'invalid_history');

    $completenessCase = static function (string $kind) use ($root): void {
        $copy = sys_get_temp_dir() . '/fmonitor-qgg-completeness-' . $kind . '-' . bin2hex(random_bytes(8));
        if (!mkdir($copy, 0700, true)) throw new TestFailure("SETUP_FAILURE: $kind fixture root");
        try {
            $git = static function (array $args) use ($copy): string { $r = qggRun(['git', ...$args], $copy); assertSameValue(0, $r['status'], 'SETUP_FAILURE: completeness git ' . json_encode($r)); return trim($r['stdout']); };
            $write = static function (string $path, string $bytes) use ($copy): void { $target=$copy.'/'.$path;if(!is_dir(dirname($target))&&!mkdir(dirname($target),0700,true))throw new TestFailure('SETUP_FAILURE: completeness directory');file_put_contents($target,$bytes); };
            $meta = static fn(array $data):string => "```delivery-metadata\n".json_encode($data,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n```\nfixture\n";
            $git(['init','--quiet']);$git(['config','user.email','complete@example.invalid']);$git(['config','user.name','Completeness Fixture']);$write('README.md',"base\n");$git(['add','.']);$git(['commit','--quiet','-m','base']);$base=$git(['rev-parse','HEAD']);
            $specPath='specs/COMPLETE-001.md';$testPath='tests/declared.php';$extraTest='tests/omitted.php';$redPath='docs/red.md';$testReviewPath='reviews/tests/COMPLETE-001.md';$greenPath='docs/green.md';$implementationPath='src/declared.txt';$extraImplementation='src/omitted.txt';$codeReviewPath='reviews/code/COMPLETE-001.md';
            $spec=$meta(['schemaVersion'=>1,'kind'=>'spec','sliceId'=>'COMPLETE-001','author'=>'agent:/spec']);$write($specPath,$spec);$write($testPath,$kind==='exact-bytes'?"\x00 leading test\ntrailing-no-lf ":"<?php echo 'declared';\n");if($kind==='test')$write($extraTest,"<?php echo 'omitted';\n");$specHash=hash('sha256',$spec);$tests=[['path'=>$testPath,'status'=>'A','sha256'=>hash_file('sha256',$copy.'/'.$testPath)]];
            $write($redPath,$meta(['schemaVersion'=>1,'kind'=>'red','sliceId'=>'COMPLETE-001','author'=>'agent:/test','specPath'=>$specPath,'specSha256'=>$specHash,'baseCommit'=>$base,'tests'=>$tests,'command'=>'php tests/declared.php','observedFailure'=>'fixture red','recordedAt'=>'2026-09-08T00:00:00Z']));$git(['add','.']);$git(['commit','--quiet','-m','red']);$redCommit=$git(['rev-parse','HEAD']);
            $write($testReviewPath,$meta(['schemaVersion'=>1,'kind'=>'test-review','sliceId'=>'COMPLETE-001','reviewer'=>'agent:/test-reviewer','verdict'=>'APPROVED','specSha256'=>$specHash,'tests'=>$tests,'redCommit'=>$redCommit,'recordedAt'=>'2026-09-08T00:01:00Z']));$git(['add','.']);$git(['commit','--quiet','-m','test review']);
            $write($implementationPath,$kind==='exact-bytes'?"\xff leading implementation\ntrailing-no-lf ":"declared implementation\n");if($kind==='implementation')$write($extraImplementation,"omitted implementation\n");$implementationFiles=[['path'=>$implementationPath,'status'=>'A','sha256'=>hash_file('sha256',$copy.'/'.$implementationPath)]];
            $write($greenPath,$meta(['schemaVersion'=>1,'kind'=>'green','sliceId'=>'COMPLETE-001','author'=>'agent:/implementation','specSha256'=>$specHash,'tests'=>$tests,'testReviewRecordPath'=>$testReviewPath,'implementationFiles'=>$implementationFiles,'commands'=>['php tests/declared.php'],'recordedAt'=>'2026-09-08T00:02:00Z']));$git(['add','.']);$git(['commit','--quiet','-m','green']);$implementationCommit=$git(['rev-parse','HEAD']);
            $write($codeReviewPath,$meta(['schemaVersion'=>1,'kind'=>'code-review','sliceId'=>'COMPLETE-001','reviewer'=>'agent:/code-reviewer','verdict'=>'APPROVED','specSha256'=>$specHash,'tests'=>$tests,'implementationCommit'=>$implementationCommit,'implementationFiles'=>$implementationFiles,'recordedAt'=>'2026-09-08T00:03:00Z']));$git(['add','.']);$git(['commit','--quiet','-m','code review']);
            $receipt=['schemaVersion'=>1,'sliceId'=>'COMPLETE-001','change'=>'complete-fixture','receiptId'=>'complete-v1','supersedes'=>null,'baseCommit'=>$base,'authors'=>['spec'=>'agent:/spec','test'=>'agent:/test','implementation'=>'agent:/implementation'],'artifacts'=>['spec'=>['path'=>$specPath,'sha256'=>$specHash],'tests'=>$tests,'red'=>['path'=>$redPath,'sha256'=>hash_file('sha256',$copy.'/'.$redPath)],'testReview'=>['path'=>$testReviewPath,'sha256'=>hash_file('sha256',$copy.'/'.$testReviewPath),'reviewer'=>'agent:/test-reviewer','verdict'=>'APPROVED','specSha256'=>$specHash],'green'=>['path'=>$greenPath,'sha256'=>hash_file('sha256',$copy.'/'.$greenPath)],'codeReview'=>['path'=>$codeReviewPath,'sha256'=>hash_file('sha256',$copy.'/'.$codeReviewPath),'reviewer'=>'agent:/code-reviewer','verdict'=>'APPROVED','specSha256'=>$specHash,'reviewedCommit'=>$implementationCommit]]];$write('delivery/evidence/COMPLETE-001/complete-v1.json',json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");$git(['add','.']);$git(['commit','--quiet','-m','receipt']);
            $result=qggRun(['php',$root.'/tools/delivery/check-evidence.php','--repo',$copy],$copy);$combined=$result['stdout']."\n".$result['stderr'];$evidence=json_encode($result,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            if($kind==='exact-bytes') { assertSameValue(0,$result['status'],"RED_ASSERTION: binary and whitespace artifact bytes must hash exactly; evidence=$evidence");assertSameValue(1,preg_match_all('/^DELIVERY_EVIDENCE_OK receipts=1 head=[0-9a-f]{40}$/m',$combined),"Exact-byte lineage success marker; evidence=$evidence");assertSameValue(0,preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m',$combined),"Exact-byte lineage no failure"); }
            else { assertSameValue(true,$result['status']!==0,"Omitted Git-derived $kind path must fail; evidence=$evidence");assertSameValue(1,preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=metadata_mismatch receipt=delivery\/evidence\/COMPLETE-001\/complete-v1\.json detail=[^\r\n]+$/m',$combined),"RED_ASSERTION: omitted Git-derived $kind path must produce exactly one metadata_mismatch; evidence=$evidence");assertSameValue(1,preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m',$combined),"Omitted $kind one failure");assertSameValue(0,preg_match_all('/^DELIVERY_EVIDENCE_OK /m',$combined),"Omitted $kind no success"); }
        } finally { qggRemoveFixture($copy); }
    };
    $completenessCase('test');
    $completenessCase('implementation');
    $completenessCase('exact-bytes');

    $mutation('modified-receipt-leaf', static function (string $copy): void {
        $path=$copy.'/delivery/evidence/LINEAGE-001/lineage-v1.json';$bytes=(string)file_get_contents($path);assertSameValue(true,file_put_contents($path,rtrim($bytes,"\n")." \n")!==false,'SETUP_FAILURE: receipt leaf byte edit');
    }, 'invalid_history');

    $aggregate=sys_get_temp_dir().'/fmonitor-qgg-aggregate-'.bin2hex(random_bytes(8));
    if(!mkdir($aggregate,0700,true))throw new TestFailure('SETUP_FAILURE: aggregate root');
    try{
        foreach([['git','init','--quiet'],['git','config','user.email','aggregate@example.invalid'],['git','config','user.name','Aggregate Fixture']]as$command){$setup=qggRun($command,$aggregate);assertSameValue(0,$setup['status'],'SETUP_FAILURE: aggregate git');}
        foreach(['AAA-001/a.json','ZZZ-001/z.json']as$relative){$target=$aggregate.'/delivery/evidence/'.$relative;if(!is_dir(dirname($target))&&!mkdir(dirname($target),0700,true))throw new TestFailure('SETUP_FAILURE: aggregate directory');file_put_contents($target,"{malformed\n");}
        foreach([['git','add','.'],['git','commit','--quiet','-m','two malformed receipts']]as$command){$setup=qggRun($command,$aggregate);assertSameValue(0,$setup['status'],'SETUP_FAILURE: aggregate commit');}
        $result=qggRun(['php',$root.'/tools/delivery/check-evidence.php','--repo',$aggregate],$aggregate);$combined=$result['stdout']."\n".$result['stderr'];$evidence=json_encode($result,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        assertSameValue(true,$result['status']!==0,"Malformed receipt aggregation must fail; evidence=$evidence");assertSameValue(1,preg_match_all('/DELIVERY_EVIDENCE_FAILURE category=invalid_schema receipt=delivery\/evidence\/AAA-001\/a\.json[^\r\n]*\nDELIVERY_EVIDENCE_FAILURE category=invalid_schema receipt=delivery\/evidence\/ZZZ-001\/z\.json/m',$combined),"RED_ASSERTION: all malformed receipts must be reported once in bytewise path order; evidence=$evidence");assertSameValue(2,preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m',$combined),"Two malformed receipts produce two failures");assertSameValue(0,preg_match_all('/^DELIVERY_EVIDENCE_OK /m',$combined),"Aggregate failure no success");
    }finally{qggRemoveFixture($aggregate);}

    $bindingCases = [
        ['name' => 'spec schemaVersion', 'artifact' => 'spec', 'field' => 'schemaVersion', 'value' => 2, 'category' => 'invalid_schema'],
        ['name' => 'spec sliceId', 'artifact' => 'spec', 'field' => 'sliceId', 'value' => 'OTHER-SLICE-001', 'category' => 'metadata_mismatch'],
        ['name' => 'red schemaVersion', 'artifact' => 'red', 'field' => 'schemaVersion', 'value' => 2, 'category' => 'invalid_schema'],
        ['name' => 'red sliceId', 'artifact' => 'red', 'field' => 'sliceId', 'value' => 'OTHER-SLICE-001', 'category' => 'metadata_mismatch'],
        ['name' => 'test review schemaVersion', 'artifact' => 'testReview', 'field' => 'schemaVersion', 'value' => 2, 'category' => 'invalid_schema'],
        ['name' => 'test review sliceId', 'artifact' => 'testReview', 'field' => 'sliceId', 'value' => 'OTHER-SLICE-001', 'category' => 'metadata_mismatch'],
        ['name' => 'green schemaVersion', 'artifact' => 'green', 'field' => 'schemaVersion', 'value' => 2, 'category' => 'invalid_schema'],
        ['name' => 'green sliceId', 'artifact' => 'green', 'field' => 'sliceId', 'value' => 'OTHER-SLICE-001', 'category' => 'metadata_mismatch'],
        ['name' => 'code review schemaVersion', 'artifact' => 'codeReview', 'field' => 'schemaVersion', 'value' => 2, 'category' => 'invalid_schema'],
        ['name' => 'code review sliceId', 'artifact' => 'codeReview', 'field' => 'sliceId', 'value' => 'OTHER-SLICE-001', 'category' => 'metadata_mismatch'],
        ['name' => 'RED specPath', 'artifact' => 'red', 'field' => 'specPath', 'value' => 'specs/OTHER-SLICE-001.md', 'category' => 'metadata_mismatch'],
        ['name' => 'RED baseCommit', 'artifact' => 'red', 'field' => 'baseCommit', 'value' => 'ffffffffffffffffffffffffffffffffffffffff', 'category' => 'metadata_mismatch'],
        ['name' => 'GREEN testReviewRecordPath', 'artifact' => 'green', 'field' => 'testReviewRecordPath', 'value' => 'reviews/tests/OTHER-SLICE-001.md', 'category' => 'metadata_mismatch'],
        ['name' => 'test review specSha256', 'artifact' => 'testReview', 'field' => 'specSha256', 'value' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'category' => 'stale_spec'],
        ['name' => 'code review specSha256', 'artifact' => 'codeReview', 'field' => 'specSha256', 'value' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'category' => 'stale_spec'],
        ['name' => 'receipt test review specSha256', 'artifact' => null, 'receiptArtifact' => 'testReview', 'field' => 'specSha256', 'value' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'category' => 'metadata_mismatch'],
        ['name' => 'receipt code review specSha256', 'artifact' => null, 'receiptArtifact' => 'codeReview', 'field' => 'specSha256', 'value' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 'category' => 'metadata_mismatch'],
    ];
    foreach ($bindingCases as $caseIndex => $case) {
        $bindingFixture = sys_get_temp_dir() . '/fmonitor-qgg-binding-' . $caseIndex . '-' . bin2hex(random_bytes(8));
        try {
            $setup = qggRun(['git', 'clone', '--quiet', $lineage, $bindingFixture], $root);
            assertSameValue(0, $setup['status'], "SETUP_FAILURE: {$case['name']} clone failed");
            foreach ([['git', 'config', 'user.email', 'binding@example.invalid'], ['git', 'config', 'user.name', 'Binding Fixture']] as $command) {
                $setup = qggRun($command, $bindingFixture);
                assertSameValue(0, $setup['status'], "SETUP_FAILURE: {$case['name']} Git configuration failed");
            }
            $receiptPath = $bindingFixture . '/delivery/evidence/LINEAGE-001/lineage-v1.json';
            $mutatedReceipt = json_decode((string) file_get_contents($receiptPath), true, 32, JSON_THROW_ON_ERROR);
            if ($case['artifact'] !== null) {
                $artifactKey = $case['artifact'];
                $artifactPath = $mutatedReceipt['artifacts'][$artifactKey]['path'];
                $artifactContents = (string) file_get_contents($bindingFixture . '/' . $artifactPath);
                assertSameValue(1, preg_match('/\A```delivery-metadata\R([^\r\n]+)/', $artifactContents, $artifactMatch), "SETUP_FAILURE: {$case['name']} metadata block missing");
                $artifactMetadata = json_decode($artifactMatch[1], true, 32, JSON_THROW_ON_ERROR);
                $artifactMetadata[$case['field']] = $case['value'];
                $artifactContents = str_replace($artifactMatch[1], json_encode($artifactMetadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $artifactContents);
                assertSameValue(true, file_put_contents($bindingFixture . '/' . $artifactPath, $artifactContents) !== false, "SETUP_FAILURE: cannot write {$case['name']} mutation");
                $mutatedReceipt['artifacts'][$artifactKey]['sha256'] = hash('sha256', $artifactContents);
            } else {
                $mutatedReceipt['artifacts'][$case['receiptArtifact']][$case['field']] = $case['value'];
            }
            assertSameValue(true, file_put_contents($receiptPath, json_encode($mutatedReceipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n") !== false, "SETUP_FAILURE: cannot update {$case['name']} receipt");
            foreach ([['git', 'add', '.'], ['git', 'commit', '--quiet', '-m', 'isolated metadata binding mutation ' . $caseIndex]] as $command) {
                $setup = qggRun($command, $bindingFixture);
                assertSameValue(0, $setup['status'], "SETUP_FAILURE: {$case['name']} commit failed");
            }
            $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $bindingFixture], $bindingFixture);
            $combined = $result['stdout'] . "\n" . $result['stderr'];
            $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            assertSameValue(true, $result['status'] !== 0, "{$case['name']} mismatch must fail; evidence=$evidence");
            assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=' . preg_quote($case['category'], '/') . ' receipt=delivery\/evidence\/LINEAGE-001\/lineage-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: {$case['name']} must produce exactly one {$case['category']}; evidence=$evidence");
            assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "{$case['name']} must emit exactly one failure; evidence=$evidence");
            assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "{$case['name']} must not print success; evidence=$evidence");
        } finally {
            qggRemoveFixture($bindingFixture);
        }
    }

    $metadataFixture = sys_get_temp_dir() . '/fmonitor-qgg-metadata-' . bin2hex(random_bytes(8));
    try {
        $setup = qggRun(['git', 'clone', '--quiet', $lineage, $metadataFixture], $root);
        assertSameValue(0, $setup['status'], 'SETUP_FAILURE: metadata mutation clone failed');
        foreach ([['git', 'config', 'user.email', 'metadata@example.invalid'], ['git', 'config', 'user.name', 'Metadata Fixture']] as $command) {
            $setup = qggRun($command, $metadataFixture);
            assertSameValue(0, $setup['status'], 'SETUP_FAILURE: metadata fixture Git configuration failed');
        }
        $redContents = (string) file_get_contents($metadataFixture . '/' . $redPath);
        assertSameValue(1, preg_match('/\A```delivery-metadata\R([^\r\n]+)/', $redContents, $redMatch), 'SETUP_FAILURE: RED metadata source block missing');
        $redMetadata = json_decode($redMatch[1], true, 32, JSON_THROW_ON_ERROR);
        $redMetadata['unexpected'] = true;
        $redContents = str_replace($redMatch[1], json_encode($redMetadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $redContents);
        assertSameValue(true, file_put_contents($metadataFixture . '/' . $redPath, $redContents) !== false, 'SETUP_FAILURE: cannot write mutated RED metadata');
        $receiptPath = $metadataFixture . '/delivery/evidence/LINEAGE-001/lineage-v1.json';
        $mutatedReceipt = json_decode((string) file_get_contents($receiptPath), true, 32, JSON_THROW_ON_ERROR);
        $mutatedReceipt['artifacts']['red']['sha256'] = hash('sha256', $redContents);
        assertSameValue(true, file_put_contents($receiptPath, json_encode($mutatedReceipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n") !== false, 'SETUP_FAILURE: cannot update mutated receipt');
        foreach ([['git', 'add', '.'], ['git', 'commit', '--quiet', '-m', 'unknown metadata field']] as $command) {
            $setup = qggRun($command, $metadataFixture);
            assertSameValue(0, $setup['status'], 'SETUP_FAILURE: metadata mutation commit failed');
        }
        $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $metadataFixture], $metadataFixture);
        $combined = $result['stdout'] . "\n" . $result['stderr'];
        $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        assertSameValue(true, $result['status'] !== 0, "Unknown canonical metadata field must fail; evidence=$evidence");
        assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=invalid_schema receipt=delivery\/evidence\/LINEAGE-001\/lineage-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: unknown RED metadata field must fail before lineage traversal; evidence=$evidence");
        assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "Unknown metadata must emit exactly one terminal failure; evidence=$evidence");
        assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Unknown metadata field must not print success; evidence=$evidence");
    } finally {
        qggRemoveFixture($metadataFixture);
    }

    $bindingFixture = sys_get_temp_dir() . '/fmonitor-qgg-binding-' . bin2hex(random_bytes(8));
    try {
        $setup = qggRun(['git', 'clone', '--quiet', $lineage, $bindingFixture], $root);
        assertSameValue(0, $setup['status'], 'SETUP_FAILURE: metadata binding mutation clone failed');
        foreach ([['git', 'config', 'user.email', 'binding@example.invalid'], ['git', 'config', 'user.name', 'Binding Fixture']] as $command) {
            $setup = qggRun($command, $bindingFixture);
            assertSameValue(0, $setup['status'], 'SETUP_FAILURE: metadata binding fixture Git configuration failed');
        }
        $redContents = (string) file_get_contents($bindingFixture . '/' . $redPath);
        assertSameValue(1, preg_match('/\A```delivery-metadata\R([^\r\n]+)/', $redContents, $redMatch), 'SETUP_FAILURE: binding RED metadata source block missing');
        $redMetadata = json_decode($redMatch[1], true, 32, JSON_THROW_ON_ERROR);
        $redMetadata['sliceId'] = 'OTHER-SLICE-001';
        $redContents = str_replace($redMatch[1], json_encode($redMetadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $redContents);
        assertSameValue(true, file_put_contents($bindingFixture . '/' . $redPath, $redContents) !== false, 'SETUP_FAILURE: cannot write mismatched RED metadata');
        $receiptPath = $bindingFixture . '/delivery/evidence/LINEAGE-001/lineage-v1.json';
        $mutatedReceipt = json_decode((string) file_get_contents($receiptPath), true, 32, JSON_THROW_ON_ERROR);
        $mutatedReceipt['artifacts']['red']['sha256'] = hash('sha256', $redContents);
        assertSameValue(true, file_put_contents($receiptPath, json_encode($mutatedReceipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n") !== false, 'SETUP_FAILURE: cannot update binding receipt');
        foreach ([['git', 'add', '.'], ['git', 'commit', '--quiet', '-m', 'mismatched metadata identity']] as $command) {
            $setup = qggRun($command, $bindingFixture);
            assertSameValue(0, $setup['status'], 'SETUP_FAILURE: metadata binding mutation commit failed');
        }
        $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $bindingFixture], $bindingFixture);
        $combined = $result['stdout'] . "\n" . $result['stderr'];
        $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        assertSameValue(true, $result['status'] !== 0, "Mismatched authoritative metadata identity must fail; evidence=$evidence");
        assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=metadata_mismatch receipt=delivery\/evidence\/LINEAGE-001\/lineage-v1\.json detail=[^\r\n]+$/m', $combined), "RED_ASSERTION: receipt and authoritative RED slice identity must match; evidence=$evidence");
        assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "Metadata binding mismatch must emit exactly one terminal failure; evidence=$evidence");
        assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Metadata binding mismatch must not print success; evidence=$evidence");
    } finally {
        qggRemoveFixture($bindingFixture);
    }

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

    $allowedOperationsEvidence = [
        'docs/operations/quality-graph-governance-final-verification-2026-09-08.md',
        'docs/operations/quality-graph-representative-pr-phase-a-2026-09-08.md',
        'docs/operations/quality-graph-publisher-phase-b-2026-09-08.md',
    ];
    foreach ($allowedOperationsEvidence as $path) $write($path, "current governed evidence $path\n");
    $git(['add', ...$allowedOperationsEvidence]);
    $git(['commit', '--quiet', '-m', 'approved post-review evidence envelope']);
    $allowedEvidenceHead = $git(['rev-parse', 'HEAD']);
    $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $lineage], $lineage);
    $combined = $result['stdout'] . "\n" . $result['stderr'];
    $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    assertSameValue(0, $result['status'], "RED_ASSERTION: exact current phase A, final-verification and phase B evidence paths must remain allowed; evidence=$evidence");
    assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_OK receipts=1 head=' . preg_quote($allowedEvidenceHead, '/') . '$/m', $combined), "Approved evidence envelope must emit exact success; evidence=$evidence");
    assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "Approved evidence envelope must emit no failure; evidence=$evidence");

    foreach ([
        ['docs/operations/unrelated-note.md', "unrelated post-review record\n", 'unapproved operations evidence'],
        [$implementationPath, "post-review implementation mutation\n", 'implementation source'],
        ['tests/lineage_test.php', "<?php echo 'changed';\n", 'approved test'],
        ['specs/LINEAGE-001.md', "changed specification\n", 'executable specification'],
    ] as [$changedPath, $bytes, $label]) {
        $git(['reset', '--hard', $allowedEvidenceHead]);
        $write($changedPath, $bytes);
        $git(['add', $changedPath]);
        $git(['commit', '--quiet', '-m', 'forbidden post-review ' . $label]);
        $result = qggRun(['php', $root . '/tools/delivery/check-evidence.php', '--repo', $lineage], $lineage);
        $combined = $result['stdout'] . "\n" . $result['stderr'];
        $evidence = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        assertSameValue(true, $result['status'] !== 0, "Post-review $label drift must fail; evidence=$evidence");
        assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE category=commit_mismatch receipt=delivery\/evidence\/LINEAGE-001\/lineage-v2\.json detail=governed path changed after review: ' . preg_quote($changedPath, '/') . '$/m', $combined), "RED_ASSERTION: post-review $label must be rejected against the unique current leaf; evidence=$evidence");
        assertSameValue(1, preg_match_all('/^DELIVERY_EVIDENCE_FAILURE /m', $combined), "Post-review $label drift must emit exactly one deterministic failure; evidence=$evidence");
        assertSameValue(0, preg_match_all('/^DELIVERY_EVIDENCE_OK /m', $combined), "Post-review $label drift must not print success; evidence=$evidence");
    }
} finally {
    qggRemoveFixture($lineage);
}

echo "QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED\n";
