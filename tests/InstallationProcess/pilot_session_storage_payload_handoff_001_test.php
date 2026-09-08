<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Tests\Support\FixedPilotSessionClock;
use FMonitor2\Tests\Support\LengthQueuedPilotSessionEntropy;
use FMonitor2\Tests\Support\NativePilotSessionFilesystem;
use FMonitor2\Tests\Support\RecordingPilotSessionObserver;

spl_autoload_register(static function (string $class): void {
    $prefix = 'FMonitor\\IdentityAccess\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = dirname(__DIR__, 2) . '/app/IdentityAccess/' . substr($class, strlen($prefix)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// PILOT-SESSION-STORAGE-001 v10 §§3,8. The real owner writes and reads the
// independently fixed whole-array payload. The assertion uses only its public
// immutable result; no test factory fabricates owner evidence.
assertSameValue(
    true,
    class_exists(FMonitor\IdentityAccess\PilotSessionStorageFactory::class),
    'INTENTIONAL_RED: approved session owner exists',
);
require dirname(__DIR__) . '/Support/PilotSessionStoragePublicApiFixture.php';

$parent = sys_get_temp_dir() . '/fmonitor2-session-storage-tests';
$root = $parent . '/task-' . bin2hex(random_bytes(12));
if (!is_dir($parent) && !mkdir($parent, 0700)) {
    throw new RuntimeException('SETUP_FAILURE: parent mkdir');
}
if (!mkdir($root, 0700)) {
    throw new RuntimeException('SETUP_FAILURE: root mkdir');
}

$remove = static function (string $path) use (&$remove): void {
    if (is_link($path) || is_file($path)) {
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

try {
    $payload = 'a:1:{s:12:"auth_user_id";i:17;}';
    $owner = (new FMonitor\IdentityAccess\PilotSessionStorageFactory())->create(
        new FMonitor\IdentityAccess\PilotSessionStorageConfig($root, 'payload_v10'),
        new NativePilotSessionFilesystem(),
        new FixedPilotSessionClock(1_788_200_000, 1_000),
        new LengthQueuedPilotSessionEntropy([
            32 => [str_repeat("\x11", 32)],
            16 => [str_repeat("\x22", 16)],
        ]),
        new RecordingPilotSessionObserver(),
    );

    $created = $owner->start(null);
    assertSameValue('OK', $created->status()->name, 'anonymous session starts');
    $sessionId = $created->currentSessionId();
    assertSameValue(true, is_string($sessionId), 'owner generated session id');
    assertSameValue('OK', $owner->writeCommit($sessionId, $payload)->status()->name, 'fixture payload committed');

    $started = $owner->start($sessionId);
    assertSameValue('OK', $started->status()->name, 'committed session starts');
    assertSameValue($sessionId, $started->currentSessionId(), 'accepted id is retained');
    assertSameValue(
        true,
        method_exists($started, 'sessionPayload'),
        'INTENTIONAL_RED: successful start exposes owner-read payload',
    );
    assertSameValue($payload, $started->sessionPayload(), 'exact committed bytes handed off');
    assertSameValue('OK', $owner->close()->status()->name, 'owner closes');

    echo "PASS: PILOT-SESSION-STORAGE-001 v10 owner payload handoff\n";
} finally {
    $remove($root);
    if (is_dir($parent) && count(scandir($parent) ?: []) === 2) {
        rmdir($parent);
    }
}
