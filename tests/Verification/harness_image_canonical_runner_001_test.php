<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** HARNESS-IMAGE-CANONICAL-RUNNER-001 v0.1 */

/** @return array{status: int|null, stdout: string, stderr: string} */
function hicrRun(array $command, string $root): array
{
    $process = proc_open(
        $command,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
    );
    if (!is_resource($process)) {
        throw new RuntimeException('SETUP_FAILURE: unable to start ' . implode(' ', $command));
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['status' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function hicrCapture(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("SETUP_FAILURE: unable to write build capture $path");
    }
}

/** @return array{source: string, destination: string}|null */
function hicrCanonicalCopyArguments(string $instruction): ?array
{
    if (preg_match('/^COPY\s+(.+)$/i', $instruction, $match) !== 1) {
        return null;
    }

    $arguments = trim($match[1]);
    if (preg_match('/^--chown=(?:"[^"]+"|\'[^\']+\'|\S+)\s+/', $arguments, $option) === 1) {
        $arguments = ltrim(substr($arguments, strlen($option[0])));
    }
    if (str_starts_with($arguments, '--')) {
        return null;
    }

    if (str_starts_with($arguments, '[')) {
        $decoded = json_decode($arguments, true);
        if (!is_array($decoded) || count($decoded) !== 2 || !is_string($decoded[0]) || !is_string($decoded[1])) {
            return null;
        }

        return ['source' => $decoded[0], 'destination' => $decoded[1]];
    }

    $parts = preg_split('/\s+/', $arguments) ?: [];
    if (count($parts) !== 2) {
        return null;
    }

    return ['source' => trim($parts[0], "\"'"), 'destination' => trim($parts[1], "\"'")];
}

function hicrCanonicalBinPath(string $path, bool $destination): bool
{
    $normalized = rtrim(str_replace('\\\\', '/', $path), '/');

    return $destination ? $normalized === './bin' : $normalized === 'bin';
}

/** @return list<array{offset: int, length: int}> */
function hicrCanonicalBinCopyRanges(string $dockerfile): array
{
    preg_match_all('/^(?:[ \t]*)([^\r\n]*)(?:\r?\n|\z)/m', $dockerfile, $lines, PREG_OFFSET_CAPTURE);
    $ranges = [];
    foreach ($lines[0] ?? [] as $index => [$physicalLine, $offset]) {
        $instruction = trim($lines[1][$index][0]);
        if ($instruction === '' || str_ends_with($instruction, '\\')) {
            continue;
        }
        $copy = hicrCanonicalCopyArguments($instruction);
        if ($copy !== null
            && hicrCanonicalBinPath($copy['source'], false)
            && hicrCanonicalBinPath($copy['destination'], true)
        ) {
            $ranges[] = ['offset' => $offset, 'length' => strlen($physicalLine)];
        }
    }

    return $ranges;
}

function hicrDockerInfrastructureStatus(int|null $status): bool
{
    return $status === null || in_array($status, [125, 126, 127], true);
}

/** @param array{status: int|null, stdout: string, stderr: string} $result */
function hicrRequireRunnableContainer(array $result, string $operation): void
{
    if (hicrDockerInfrastructureStatus($result['status'])) {
        throw new RuntimeException(
            "SETUP_FAILURE: Docker could not run the $operation probe\n" . $result['stdout'] . $result['stderr'],
        );
    }
}

function hicrCleanupOwnedContainer(string $cidFile, string $labelKey, string $labelValue, string $root): void
{
    if (!is_file($cidFile)) {
        return;
    }

    $containerId = trim((string) file_get_contents($cidFile));
    if (preg_match('/^[a-f0-9]{12,64}$/D', $containerId) === 1) {
        $inspection = hicrRun([
            'docker', 'inspect', '--format', '{{ index .Config.Labels "' . $labelKey . '" }}', $containerId,
        ], $root);
        if ($inspection['status'] === 0 && trim($inspection['stdout']) === $labelValue) {
            hicrRun(['docker', 'rm', '--force', $containerId], $root);
        }
    }
    @unlink($cidFile);
}

function hicrRemoveTree(string $path): void
{
    if (!is_dir($path) || is_link($path)) {
        return;
    }
    $entries = scandir($path);
    if ($entries === false) {
        return;
    }
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $ownedPath = $path . '/' . $entry;
        if (is_dir($ownedPath) && !is_link($ownedPath)) {
            hicrRemoveTree($ownedPath);
        } else {
            @unlink($ownedPath);
        }
    }
    @rmdir($path);
}

$root = dirname(__DIR__, 2);
$dockerfilePath = $root . '/Dockerfile';
$dockerfile = file_get_contents($dockerfilePath);
if ($dockerfile === false) {
    fwrite(STDERR, "SETUP_FAILURE: unable to read repository Dockerfile\n");
    exit(2);
}

try {
    $canonicalCopyRanges = hicrCanonicalBinCopyRanges($dockerfile);
    assertSameValue(
        1,
        count($canonicalCopyRanges),
        'RED_ASSERTION: Dockerfile must contain exactly one canonical COPY from repository bin to ./bin',
    );
    $copyRange = $canonicalCopyRanges[0];
    $dockerfileWithoutCanonicalCopy = substr($dockerfile, 0, $copyRange['offset'])
        . substr($dockerfile, $copyRange['offset'] + $copyRange['length']);
    assertSameValue(
        '6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702',
        hash('sha256', $dockerfileWithoutCanonicalCopy),
        'RED_ASSERTION: removing the single canonical bin COPY must recover the approved packaging-only Dockerfile baseline',
    );
    assertSameValue(
        1,
        preg_match('/^USER[ \t]+fmonitor[ \t]*$/m', $dockerfile),
        'RED_ASSERTION: runtime image must retain USER fmonitor',
    );
    assertSameValue(
        1,
        preg_match('/^ENTRYPOINT[ \t]+\["rapid-pilot\/docker-entrypoint\.sh"\][ \t]*$/m', $dockerfile),
        'RED_ASSERTION: runtime image must retain the rapid-pilot entrypoint',
    );
    $entrypoint = file_get_contents($root . '/rapid-pilot/docker-entrypoint.sh');
    if ($entrypoint === false) {
        throw new RuntimeException('SETUP_FAILURE: unable to read rapid-pilot/docker-entrypoint.sh');
    }
    assertSameValue(
        'd23b3563c1921c9b54abe0d60134eaabb0a4aad367a2141e3203934757ebb10a',
        hash('sha256', $entrypoint),
        'RED_ASSERTION: runtime entrypoint bytes must retain the reviewed completion-v10 canonical startup baseline',
    );
    $bootstrap = file_get_contents($root . '/rapid-pilot/docker-bootstrap.php');
    if ($bootstrap === false) {
        throw new RuntimeException('SETUP_FAILURE: unable to read rapid-pilot/docker-bootstrap.php');
    }
    assertSameValue(
        '700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f',
        hash('sha256', $bootstrap),
        'RED_ASSERTION: runtime bootstrap bytes must retain the reviewed completion-v10 fail-closed baseline',
    );
} catch (Throwable $failure) {
    $message = $failure->getMessage();
    fwrite(STDERR, $message . "\n");
    exit(str_starts_with($message, 'SETUP_FAILURE:') ? 2 : 1);
}

$docker = hicrRun(['docker', 'info', '--format', '{{.ServerVersion}}'], $root);
if ($docker['status'] !== 0 || trim($docker['stdout']) === '') {
    fwrite(STDERR, "SETUP_FAILURE: Docker daemon is unavailable\n" . $docker['stderr']);
    exit(2);
}

$artifactParent = $root . '/.local/test-artifacts';
if ((!is_dir($artifactParent) && !mkdir($artifactParent, 0700, true)) || !is_writable($artifactParent)) {
    fwrite(STDERR, "SETUP_FAILURE: repository-local test artifact directory is unavailable\n");
    exit(2);
}

$token = bin2hex(random_bytes(8));
$artifactRoot = $artifactParent . '/harness-image-canonical-runner-' . $token;
if (!mkdir($artifactRoot, 0700)) {
    fwrite(STDERR, "SETUP_FAILURE: unable to allocate repository-local image artifacts\n");
    exit(2);
}

$image = 'fmonitor2-hicr:' . $token;
$labelKey = 'com.fmonitor2.test.harness-image-canonical-runner';
$readabilityCidFile = $artifactRoot . '/readability.cid';
$runnerCidFile = $artifactRoot . '/runner.cid';
$exitStatus = 0;
$failureMessage = '';

try {
    $build = hicrRun(['docker', 'build', '--tag', $image, $root], $root);
    hicrCapture($artifactRoot . '/build.stdout', $build['stdout']);
    hicrCapture($artifactRoot . '/build.stderr', $build['stderr']);
    if ($build['status'] !== 0) {
        throw new RuntimeException(
            "SETUP_FAILURE: fresh runtime image build failed\n"
            . $build['stdout'] . $build['stderr'],
        );
    }

    $readability = hicrRun([
        'docker', 'run', '--rm', '--label', $labelKey . '=' . $token,
        '--cidfile', $readabilityCidFile,
        '--entrypoint', 'sh', $image, '-c',
        'test "$(id -u)" = 10001'
        . ' && test "$(id -un)" = fmonitor'
        . ' && test -r /workspace/fmonitor-2/bin/fmonitor2-migrate.php'
        . ' && ! find /workspace/fmonitor-2/app/InstallationProcess -type f ! -readable -print -quit | grep -q .'
        . ' && for path in tests reviews specs docs tools .git .local; do test ! -e "/workspace/fmonitor-2/$path" || exit 41; done'
        . ' && ! find /workspace/fmonitor-2 -path /workspace/fmonitor-2/vendor -prune -o '
        . '\\( -name ".env*" -o -name "*.dump" -o -name "*.sql.gz" -o -name "*.bak" '
        . '-o -name "*.key" -o -name "*.pem" -o -name "*.p12" -o -name "*.pfx" '
        . '-o -name "*.pk8" -o -name "*.crt" -o -name "*.cer" -o -name "*.der" '
        . '-o -name "*.p7b" -o -name "*.p7c" -o -name "*.msg" \\) -print -quit | grep -q .',
    ], $root);
    hicrRequireRunnableContainer($readability, 'readability');
    if ($readability['status'] !== 0) {
        throw new TestFailure(
            'RED_ASSERTION: canonical runner and InstallationProcess dependencies must be readable by non-root user fmonitor; evidence=' .
            json_encode($readability, JSON_UNESCAPED_SLASHES),
        );
    }

    $runner = hicrRun([
        'docker', 'run', '--rm', '--label', $labelKey . '=' . $token,
        '--cidfile', $runnerCidFile,
        '--entrypoint', 'php', $image, 'bin/fmonitor2-migrate.php',
    ], $root);
    hicrRequireRunnableContainer($runner, 'canonical runner');
    assertSameValue(64, $runner['status'], 'RED_ASSERTION: built-image canonical runner must reject absent migration configuration with exit 64');
    assertSameValue("{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", $runner['stdout'], 'RED_ASSERTION: built-image canonical runner must emit exact configuration-invalid JSON');
    assertSameValue('', $runner['stderr'], 'RED_ASSERTION: built-image canonical runner must keep stderr empty for configuration-invalid');
} catch (Throwable $failure) {
    $failureMessage = $failure->getMessage();
    $exitStatus = str_starts_with($failureMessage, 'SETUP_FAILURE:') ? 2 : 1;
} finally {
    hicrCleanupOwnedContainer($readabilityCidFile, $labelKey, $token, $root);
    hicrCleanupOwnedContainer($runnerCidFile, $labelKey, $token, $root);
    hicrRun(['docker', 'image', 'rm', $image], $root);
    hicrRemoveTree($artifactRoot);
}

if ($failureMessage !== '') {
    fwrite(STDERR, $failureMessage, "\n");
    exit($exitStatus);
}

echo "HARNESS-IMAGE-CANONICAL-RUNNER-001 passed\n";
