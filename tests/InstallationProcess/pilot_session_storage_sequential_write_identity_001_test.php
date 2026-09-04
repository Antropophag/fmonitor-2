<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor\IdentityAccess\PilotSessionFilesystemPhase;
use FMonitor\IdentityAccess\PilotSessionLogicalArtifact;
use FMonitor\IdentityAccess\PilotSessionOperationStatus;
use FMonitor\IdentityAccess\PilotSessionPrimitiveOutcome;
use FMonitor2\PilotHttp\PilotCommandSession;
use FMonitor2\Tests\Support\FixedPilotSessionClock;
use FMonitor2\Tests\Support\FixedPilotSessionEntropy;
use FMonitor2\Tests\Support\NativePilotSessionFilesystem;
use FMonitor2\Tests\Support\RecordingPilotSessionObserver;

spl_autoload_register(static function (string $class): void {
    $prefix = 'FMonitor\\IdentityAccess\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = dirname(__DIR__, 2) . '/app/IdentityAccess/'
        . substr($class, strlen($prefix)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require dirname(__DIR__) . '/Support/PilotSessionStoragePublicApiFixture.php';

// PILOT-SESSION-STORAGE-001 v10 sections 3, 4, 6, 8 and 11. This invokes the
// real owner factory and PilotCommandSession. Cookie headers, material files,
// exact bytes and a subsequent real start are independent public-seam oracles.
$parent = sys_get_temp_dir() . '/fmonitor2-session-storage-tests';
$root = $parent . '/task-sequential-' . bin2hex(random_bytes(12));
$sentinel = $parent . '/foreign-sequential-' . bin2hex(random_bytes(8));
$owner = null;

$remove = static function (string $path) use (&$remove): void {
    if (is_file($path) || is_link($path)) {
        unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            $remove($path . '/' . $entry);
        }
    }
    rmdir($path);
};

if (!is_dir($parent) && !mkdir($parent, 0700)) {
    throw new RuntimeException('SETUP_FAILURE: session test parent mkdir');
}
if (!mkdir($root, 0700)) {
    throw new RuntimeException('SETUP_FAILURE: session test root mkdir');
}
file_put_contents($sentinel, "foreign-sequential\0");
$sentinelHash = hash_file('sha256', $sentinel);

try {
    $entropy = new FixedPilotSessionEntropy([
        str_repeat("\x11", 32), // anonymous session ID
        str_repeat("\x22", 16), // first committed stage
        str_repeat("\x33", 32), // exposed only by the current identity-rotation defect
        str_repeat("\x44", 16), // second committed stage
    ]);
    $observer = new RecordingPilotSessionObserver();
    $owner = (new FMonitor\IdentityAccess\PilotSessionStorageFactory())->create(
        new FMonitor\IdentityAccess\PilotSessionStorageConfig($root, 'sequential_v10'),
        new NativePilotSessionFilesystem(),
        new FixedPilotSessionClock(1_788_200_000, 10_000),
        $entropy,
        $observer,
    );

    $session = new PilotCommandSession($owner);
    assertSameValue(true, $session->open(null, 'fm2auth', false, 4242, true), 'new command session opens');
    $firstHeaders = $session->headers();
    assertSameValue(true, isset($firstHeaders['Set-Cookie']), 'first commit publishes a cookie header');
    assertSameValue(
        1,
        preg_match('/^fm2auth=([A-Za-z0-9,-]{16,128}); HttpOnly; SameSite=Strict; Path=\/pilot$/D', $firstHeaders['Set-Cookie'], $cookieMatch),
        'first commit publishes the exact accepted cookie grammar',
    );
    $cookieId = $cookieMatch[1];

    $state = $session->state();
    $state['tokens']['user-access-save'] = 'fixed-token-value';
    $session->replace($state, true);
    assertSameValue($firstHeaders, $session->headers(), 'second commit retains the accepted cookie header');

    $directory = $root . '/sessions/sequential_v10';
    $committedPaths = glob($directory . '/s-*.session') ?: [];
    sort($committedPaths, SORT_STRING);
    $committedIds = array_map(
        static fn (string $path): string => substr(basename($path), 2, -8),
        $committedPaths,
    );
    $expectedPayload = serialize($state);
    $reopened = $owner->start($cookieId);
    $committedHash = hash('sha256', $cookieId);
    $committedEventsRetainCookie = true;
    foreach (array_chunk($observer->events, 2) as $index => $pair) {
        assertSameValue(2, count($pair), 'every primitive event is paired');
        [$before, $after] = $pair;
        assertSameValue($index * 2 + 1, $before->sequence(), 'deterministic before sequence');
        assertSameValue($index * 2 + 2, $after->sequence(), 'deterministic after sequence');
        assertSameValue(PilotSessionFilesystemPhase::BEFORE, $before->phase(), 'before phase');
        assertSameValue(PilotSessionFilesystemPhase::AFTER, $after->phase(), 'after phase');
        assertSameValue($before->operation(), $after->operation(), 'paired operation');
        assertSameValue($before->artifact(), $after->artifact(), 'paired artifact');
        assertSameValue($before->sessionIdSha256(), $after->sessionIdSha256(), 'paired opaque identity');
        assertSameValue(null, $before->outcome(), 'before outcome is absent');
        assertSameValue(true, $after->outcome() instanceof PilotSessionPrimitiveOutcome, 'after outcome is typed');
        if ($before->artifact() === PilotSessionLogicalArtifact::COMMITTED) {
            $committedEventsRetainCookie = $committedEventsRetainCookie
                && $before->sessionIdSha256() === $committedHash;
        }
    }

    assertSameValue(PilotSessionOperationStatus::OK, $owner->close()->status(), 'owner closes');
    $owner = null;
    assertSameValue(
        [
            'soleCommittedCookieIdentity' => true,
            'cookieMaterialHasUpdatedToken' => true,
            'cookieReopens' => true,
            'reopenedIdentityIsCookie' => true,
            'reopenedPayloadHasUpdatedToken' => true,
            'entropyLengths' => [32, 16, 16],
            'committedEventsRetainCookie' => true,
        ],
        [
            'soleCommittedCookieIdentity' => $committedIds === [$cookieId],
            'cookieMaterialHasUpdatedToken' => is_file($directory . '/s-' . $cookieId . '.session')
                && file_get_contents($directory . '/s-' . $cookieId . '.session') === $expectedPayload,
            'cookieReopens' => $reopened->status() === PilotSessionOperationStatus::OK,
            'reopenedIdentityIsCookie' => $reopened->currentSessionId() === $cookieId,
            'reopenedPayloadHasUpdatedToken' => $reopened->sessionPayload() === $expectedPayload,
            'entropyLengths' => $entropy->requestedLengths,
            'committedEventsRetainCookie' => $committedEventsRetainCookie,
        ],
        'INTENTIONAL_RED: sequential write retains accepted cookie/current identity and exact updated state',
    );
    echo "PASS: PILOT-SESSION-STORAGE-001 v10 sequential write identity\n";
} finally {
    if ($owner !== null) {
        $owner->close();
    }
    $remove($root);
    assertSameValue($sentinelHash, hash_file('sha256', $sentinel), 'foreign sentinel preserved');
    unlink($sentinel);
    if (is_dir($parent) && count(scandir($parent) ?: []) === 2) {
        rmdir($parent);
    }
}
