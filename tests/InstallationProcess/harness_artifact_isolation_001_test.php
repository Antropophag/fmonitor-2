<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Tests\Support\HttpReadOnlyFilesystemGuard;

/** HARNESS-ARTIFACT-ISOLATION-001 v0.4 */

function haiRemoveOwnedRoot(
    string $approvedParent,
    string $root,
    string $expectedBasename,
): void
{
    if ($root === '') {
        return;
    }

    $parentState = lstat($approvedParent);
    $rootState = lstat($root);
    $canonicalParent = realpath($approvedParent);
    $canonicalRoot = realpath($root);
    if (
        $parentState === false
        || $rootState === false
        || is_link($approvedParent)
        || is_link($root)
        || ($parentState['mode'] & 0170000) !== 0040000
        || ($rootState['mode'] & 0170000) !== 0040000
        || $parentState['uid'] !== posix_geteuid()
        || $rootState['uid'] !== posix_geteuid()
        || ($parentState['mode'] & 0022) !== 0
        || $canonicalParent !== $approvedParent
        || basename($canonicalParent) !== '.test-artifacts'
        || $canonicalRoot !== $root
        || dirname($canonicalRoot) !== $canonicalParent
        || basename($canonicalRoot) !== $expectedBasename
        || !str_starts_with($canonicalRoot, $canonicalParent . DIRECTORY_SEPARATOR)
    ) {
        throw new TestFailure('Refusing to clean an untrusted test-artifact root.');
    }

    $entries = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($canonicalRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($entries as $entry) {
        $path = $entry->getPathname();
        if (!str_starts_with($path, $canonicalRoot . DIRECTORY_SEPARATOR) || lstat($path) === false) {
            throw new TestFailure('Refusing to clean outside the owned test-artifact root.');
        }
        $entry->isDir() && !$entry->isLink() ? rmdir($path) : unlink($path);
    }
    rmdir($canonicalRoot);
}

function haiWaitForFile(string $path, int $deadlineNanoseconds, string $message): void
{
    while (!is_file($path) && hrtime(true) < $deadlineNanoseconds) {
        usleep(1_000);
    }
    if (!is_file($path)) {
        throw new TestFailure($message);
    }
}

function haiStopSibling(mixed $process, array $pipes): array
{
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $stdout, $stderr];
}

$repositoryRoot = dirname(__DIR__, 2);
$sharedParent = $repositoryRoot . '/.test-artifacts';
$approvedSharedParent = '';
$ownerRoot = '';
$siblingRoot = '';
$ownerBasename = '';
$siblingBasename = '';
$siblingProcess = null;
$siblingPipes = [];
$previousCssEnvironment = getenv('FMONITOR_SHLZ_CSS_PATH');
$cssEnvironmentConfigured = false;
$productionCss = '';
$productionCssState = null;
$canonicalCssFixture = '';
$canonicalCssBytes = null;
$sharedParentPreservedState = null;

try {
    if (!is_dir($sharedParent)) {
        if (!mkdir($sharedParent, 0700)) {
            throw new TestFailure('Shared test-artifact parent must be creatable.');
        }
    }
    $parentState = lstat($sharedParent);
    assertSameValue(
        true,
        $parentState !== false
            && !is_link($sharedParent)
            && ($parentState['mode'] & 0170000) === 0040000
            && $parentState['uid'] === posix_geteuid()
            && ($parentState['mode'] & 0022) === 0
            && str_starts_with((string) realpath($sharedParent), dirname($repositoryRoot) . DIRECTORY_SEPARATOR),
        'Shared test-artifact parent must be a trusted owner-controlled directory beneath home.',
    );
    $approvedSharedParent = (string) realpath($sharedParent);
    $sharedParentPreservedState = [
        'canonical' => $approvedSharedParent,
        'mode' => $parentState['mode'],
        'uid' => $parentState['uid'],
        'gid' => $parentState['gid'],
    ];

    $token = bin2hex(random_bytes(12));
    $ownerBasename = 'hai-owner-' . $token;
    $siblingBasename = 'hai-sibling-' . $token;
    $ownerCandidate = $approvedSharedParent . '/' . $ownerBasename;
    $siblingCandidate = $approvedSharedParent . '/' . $siblingBasename;
    assertSameValue(true, mkdir($ownerCandidate, 0700), 'Tracer owner root must be created.');
    $ownerRoot = (string) realpath($ownerCandidate);
    assertSameValue(true, mkdir($siblingCandidate, 0700), 'Sibling owner root must be created.');
    $siblingRoot = (string) realpath($siblingCandidate);

    $mutableRoot = $ownerRoot . '/mutable';
    $protectedRoot = $ownerRoot . '/protected-artifact-store';
    assertSameValue(true, mkdir($mutableRoot, 0700), 'Mutable root must be created.');
    assertSameValue(true, mkdir($protectedRoot, 0700), 'Protected artifact-store root must be created.');
    $protectedSentinel = $protectedRoot . '/sentinel';
    file_put_contents($protectedSentinel, 'immutable-production-artifact');
    $productionCssCandidate = $repositoryRoot . '/../shlz-ui/packages/styles/dist/shlz.css';
    $productionCss = (string) realpath($productionCssCandidate);
    $productionCssStat = lstat($productionCssCandidate);
    assertSameValue(
        true,
        $productionCss !== ''
            && $productionCssStat !== false
            && ($productionCssStat['mode'] & 0170000) === 0100000
            && !is_link($productionCss),
        'Production shlz-ui CSS export must be an exact canonical regular file.',
    );
    $canonicalCssBytes = file_get_contents($productionCss);
    assertSameValue(true, is_string($canonicalCssBytes), 'Production shlz-ui CSS export must be readable.');
    $productionCssState = [
        'mode' => $productionCssStat['mode'] & 07777,
        'size' => $productionCssStat['size'],
        'sha256' => hash('sha256', $canonicalCssBytes),
    ];
    $cssFixture = $mutableRoot . '/shlz.css';
    assertSameValue(
        strlen($canonicalCssBytes),
        file_put_contents($cssFixture, $canonicalCssBytes, LOCK_EX),
        'Task-owned configured CSS fixture must copy every production export byte.',
    );
    $canonicalCssFixture = realpath($cssFixture);
    assertSameValue(
        true,
        $canonicalCssFixture !== false && is_file($canonicalCssFixture) && !is_link($canonicalCssFixture),
        'Task-owned configured CSS fixture must be an exact canonical regular file.',
    );
    assertSameValue(
        $productionCssState['sha256'],
        hash_file('sha256', $canonicalCssFixture),
        'Task-owned configured CSS fixture must be byte-exact with the production export.',
    );
    assertSameValue(
        true,
        putenv('FMONITOR_SHLZ_CSS_PATH=' . $canonicalCssFixture),
        'Configured CSS public environment seam must be set for the tracer.',
    );
    $cssEnvironmentConfigured = true;

    $ready = $siblingRoot . '/ready';
    $release = $siblingRoot . '/release';
    $done = $siblingRoot . '/done';
    $siblingSentinel = $siblingRoot . '/sentinel';
    $script = <<<'PHP'
$ready=$argv[1];$release=$argv[2];$done=$argv[3];$sentinel=$argv[4];
file_put_contents($ready,"ready\n",LOCK_EX);
$deadline=hrtime(true)+3_000_000_000;
while(!is_file($release)&&hrtime(true)<$deadline){usleep(1000);}
if(!is_file($release)){fwrite(STDERR,"release timeout\n");exit(71);}
file_put_contents($sentinel,'other-owner-write',LOCK_EX);
file_put_contents($sentinel,'other-owner-write-updated',LOCK_EX);
unlink($sentinel);
file_put_contents($done,"done\n",LOCK_EX);
PHP;
    $siblingProcess = proc_open(
        [PHP_BINARY, '-r', $script, $ready, $release, $done, $siblingSentinel],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $siblingPipes,
        $repositoryRoot,
    );
    if (!is_resource($siblingProcess)) {
        throw new TestFailure('Sibling writer process must start.');
    }
    fclose($siblingPipes[0]);
    haiWaitForFile(
        $ready,
        hrtime(true) + 5_000_000_000,
        'Sibling writer must publish ready marker before guard observation.',
    );

    $supportFile = dirname(__DIR__) . '/Support/HttpReadOnlyFilesystemGuard.php';
    if (is_file($supportFile)) {
        require_once $supportFile;
    }
    if (!class_exists(HttpReadOnlyFilesystemGuard::class)) {
        throw new TestFailure('HttpReadOnlyFilesystemGuard public seam is missing.');
    }

    $configuredCssEnvironment = getenv('FMONITOR_SHLZ_CSS_PATH');
    $configuredCss = is_string($configuredCssEnvironment)
        ? (string) realpath($configuredCssEnvironment)
        : '';
    assertSameValue(
        true,
        $configuredCssEnvironment === $canonicalCssFixture
            && $configuredCss === $configuredCssEnvironment
            && is_file($configuredCss)
            && !is_link($configuredCss),
        'Exact canonical configured CSS file must come from FMONITOR_SHLZ_CSS_PATH.',
    );

    $missingPath = $ownerRoot . '/missing-protected-path';
    $protectedSymlink = $mutableRoot . '/protected-link';
    assertSameValue(true, symlink($protectedRoot, $protectedSymlink), 'Symlink rejection fixture must be created.');
    $unsupportedPath = $mutableRoot . '/unsupported.fifo';
    assertSameValue(true, posix_mkfifo($unsupportedPath, 0600), 'Unsupported-type rejection fixture must be created.');
    $invalidPathCases = [
        'missing protected path' => [[$missingPath], []],
        'symlink protected path' => [[$protectedSymlink], []],
        'unsupported protected type' => [[$unsupportedPath], []],
        'path outside approved boundaries' => [['/etc/hosts'], []],
        'duplicate protected path' => [[$protectedRoot, $protectedRoot], []],
        'protected ancestor overlap' => [[$protectedRoot, $protectedSentinel], []],
        'protected and mutable overlap' => [[$protectedRoot], [$protectedRoot]],
    ];
    foreach ($invalidPathCases as $case => [$protectedPaths, $ownedMutableRoots]) {
        $callbackInvoked = false;
        $observedValidationFailure = null;
        try {
            HttpReadOnlyFilesystemGuard::observe(
                static function () use (&$callbackInvoked): string {
                    $callbackInvoked = true;
                    return 'must-not-run';
                },
                $protectedPaths,
                $ownedMutableRoots,
            );
        } catch (TestFailure $failure) {
            $observedValidationFailure = $failure;
        }
        assertSameValue(
            true,
            $observedValidationFailure instanceof TestFailure,
            $case . ' must fail closed with TestFailure.',
        );
        assertSameValue(false, $callbackInvoked, $case . ' must be rejected before callback invocation.');
    }

    $unchangedCallbackFailure = new RuntimeException('fixed callback failure');
    $observedCallbackFailure = null;
    try {
        HttpReadOnlyFilesystemGuard::observe(
            static function () use ($unchangedCallbackFailure): never {
                throw $unchangedCallbackFailure;
            },
            [$protectedRoot, $configuredCss],
            [$mutableRoot],
        );
        throw new TestFailure('An unchanged exceptional callback must throw.');
    } catch (RuntimeException $failure) {
        $observedCallbackFailure = $failure;
    }
    assertSameValue(
        true,
        $observedCallbackFailure === $unchangedCallbackFailure,
        'Callback exception identity must be preserved when protected paths remain unchanged.',
    );

    try {
        HttpReadOnlyFilesystemGuard::observe(
            static function () use ($protectedSentinel): never {
                file_put_contents($protectedSentinel, 'forbidden-write');
                throw new RuntimeException('masked callback failure');
            },
            [$protectedRoot, $configuredCss],
            [$mutableRoot],
        );
        throw new TestFailure('Protected mutation must take precedence over a callback exception.');
    } catch (TestFailure $failure) {
        assertSameValue(
            'Protected HTTP read-only path changed.',
            $failure->getMessage(),
            'Post-snapshot mutation verdict must take precedence over a callback exception.',
        );
    } finally {
        file_put_contents($protectedSentinel, 'immutable-production-artifact');
    }

    $result = HttpReadOnlyFilesystemGuard::observe(
        static function () use ($release, $done): string {
            file_put_contents($release, "release\n", LOCK_EX);
            $doneDeadline = hrtime(true) + 5_000_000_000;
            haiWaitForFile(
                $done,
                $doneDeadline,
                'Sibling writer must complete within five seconds of release during observed callback.',
            );
            return 'observed-result';
        },
        [$protectedRoot, $configuredCss],
        [$mutableRoot],
    );
    assertSameValue('observed-result', $result, 'Sibling-owned writes must not change the read-only verdict.');
    assertSameValue([0, '', ''], haiStopSibling($siblingProcess, $siblingPipes), 'Sibling writer must finish cleanly.');
    $siblingProcess = null;
    $siblingPipes = [];

    try {
        HttpReadOnlyFilesystemGuard::observe(
            static function () use ($protectedSentinel): string {
                file_put_contents($protectedSentinel, 'forbidden-write');
                return 'unobservable-result';
            },
            [$protectedRoot, $configuredCss],
            [$mutableRoot],
        );
        throw new TestFailure('Protected artifact mutation must be detected.');
    } catch (TestFailure $failure) {
        assertSameValue(
            'Protected HTTP read-only path changed.',
            $failure->getMessage(),
            'Protected artifact mutation must have the exact redacted verdict.',
        );
    } finally {
        file_put_contents($protectedSentinel, 'immutable-production-artifact');
    }

    try {
        HttpReadOnlyFilesystemGuard::observe(
            static function () use ($configuredCss): string {
                file_put_contents($configuredCss, "forbidden-configured-css-write\n");
                return 'unobservable-result';
            },
            [$protectedRoot, $configuredCss],
            [$mutableRoot],
        );
        throw new TestFailure('Exact configured CSS mutation must be detected inside a mutable root.');
    } catch (TestFailure $failure) {
        assertSameValue(
            'Protected HTTP read-only path changed.',
            $failure->getMessage(),
            'Exact configured CSS mutation must have the exact redacted verdict.',
        );
    } finally {
        file_put_contents($configuredCss, $canonicalCssBytes, LOCK_EX);
        assertSameValue(
            $productionCssState['sha256'],
            hash_file('sha256', $configuredCss),
            'Configured CSS copy must be restored exactly before cleanup.',
        );
    }

    try {
        HttpReadOnlyFilesystemGuard::observe(
            static function () use ($configuredCss): string {
                unlink($configuredCss);
                return 'unobservable-result';
            },
            [$protectedRoot, $configuredCss],
            [$mutableRoot],
        );
        throw new TestFailure('Deleted exact protected file must be detected after a valid before-snapshot.');
    } catch (TestFailure $failure) {
        assertSameValue(
            'Protected HTTP read-only path changed.',
            $failure->getMessage(),
            'Deleted exact protected file must have the exact redacted mutation verdict.',
        );
    } finally {
        if (is_dir($configuredCss)) {
            rmdir($configuredCss);
        }
        file_put_contents($configuredCss, $canonicalCssBytes, LOCK_EX);
    }

    try {
        HttpReadOnlyFilesystemGuard::observe(
            static function () use ($configuredCss): string {
                unlink($configuredCss);
                mkdir($configuredCss, 0700);
                return 'unobservable-result';
            },
            [$protectedRoot, $configuredCss],
            [$mutableRoot],
        );
        throw new TestFailure('Exact protected file type replacement must be detected after a valid before-snapshot.');
    } catch (TestFailure $failure) {
        assertSameValue(
            'Protected HTTP read-only path changed.',
            $failure->getMessage(),
            'Exact protected file type replacement must have the exact redacted mutation verdict.',
        );
    } finally {
        if (is_dir($configuredCss)) {
            rmdir($configuredCss);
        } elseif (is_file($configuredCss) || is_link($configuredCss)) {
            unlink($configuredCss);
        }
        file_put_contents($configuredCss, $canonicalCssBytes, LOCK_EX);
    }
} finally {
    if (is_resource($siblingProcess)) {
        proc_terminate($siblingProcess);
        foreach ([1, 2] as $descriptor) {
            if (isset($siblingPipes[$descriptor]) && is_resource($siblingPipes[$descriptor])) {
                fclose($siblingPipes[$descriptor]);
            }
        }
        proc_close($siblingProcess);
    }
    if ($cssEnvironmentConfigured) {
        $previousCssEnvironment === false
            ? putenv('FMONITOR_SHLZ_CSS_PATH')
            : putenv('FMONITOR_SHLZ_CSS_PATH=' . $previousCssEnvironment);
    }
    if ($canonicalCssFixture !== '' && is_file($canonicalCssFixture) && is_string($canonicalCssBytes)) {
        file_put_contents($canonicalCssFixture, $canonicalCssBytes, LOCK_EX);
    }
    haiRemoveOwnedRoot($approvedSharedParent, $ownerRoot, $ownerBasename);
    haiRemoveOwnedRoot($approvedSharedParent, $siblingRoot, $siblingBasename);
    if ($productionCss !== '' && is_array($productionCssState)) {
        $productionCssAfter = lstat($productionCss);
        assertSameValue(
            $productionCssState,
            $productionCssAfter === false
                ? null
                : [
                    'mode' => $productionCssAfter['mode'] & 07777,
                    'size' => $productionCssAfter['size'],
                    'sha256' => hash_file('sha256', $productionCss),
                ],
            'Production shlz-ui CSS export must remain byte-and-mode exact.',
        );
    }
}

assertSameValue(false, file_exists($ownerRoot), 'Cleanup must remove only the tracer owner root.');
assertSameValue(false, file_exists($siblingRoot), 'Cleanup must remove only the sibling owner root.');
$sharedParentAfter = lstat($sharedParent);
assertSameValue(
    $sharedParentPreservedState,
    $sharedParentAfter === false ? null : [
        'canonical' => realpath($sharedParent),
        'mode' => $sharedParentAfter['mode'],
        'uid' => $sharedParentAfter['uid'],
        'gid' => $sharedParentAfter['gid'],
    ],
    'Cleanup must preserve the shared trusted parent path, type, permissions and ownership exactly.',
);

echo "HARNESS-ARTIFACT-ISOLATION-001 PASS\n";
