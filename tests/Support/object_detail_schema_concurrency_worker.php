<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigration;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaMigrationVerification;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaObserver;
use FMonitor2\InstallationProcess\ObjectDetailSnapshotSchemaPhase;

$database = $argv[1] ?? '';
$prefix = $argv[2] ?? '';
$token = $argv[3] ?? '';
$mode = $argv[4] ?? '';
if (count($argv) !== 5 || !preg_match('/^fm2_ods_concurrent_[a-f0-9]{12}$/D', $database)
    || !in_array($prefix, ['race_', 'other_'], true)
    || !preg_match('/^[a-f0-9]{12}$/D', $token)
    || !in_array($mode, ['hold', 'normal'], true)) exit(70);
$db = null;
$exitCode = 0;
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        $database, (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $db->set_charset('utf8mb4');
    fwrite(STDOUT, "START {$token} {$db->thread_id}\n");
    fflush(STDOUT);
    if ($mode === 'hold') {
        $observer = new class($token) implements ObjectDetailSnapshotSchemaObserver {
            public function __construct(private string $token) {}
            public function observe(ObjectDetailSnapshotSchemaPhase $phase): void
            {
                if ($phase !== ObjectDetailSnapshotSchemaPhase::LOCK_ACQUIRED) return;
                fwrite(STDOUT, "READY {$this->token}\n");
                fflush(STDOUT);
                stream_set_blocking(STDIN, false);
                $deadline = hrtime(true) + 45_000_000_000;
                $line = '';
                while (!str_contains($line, "\n")) {
                    $chunk = fread(STDIN, 64);
                    if ($chunk === false || ($chunk === '' && feof(STDIN))) throw new RuntimeException('release unavailable');
                    $line .= $chunk;
                    if (strlen($line) > 64 || hrtime(true) >= $deadline) throw new RuntimeException('release unavailable');
                    usleep(1000);
                }
                if ($line !== "RELEASE {$this->token}\n") throw new RuntimeException('release invalid');
            }
        };
        $result = ObjectDetailSnapshotSchemaMigrationVerification::apply($db, $prefix, $observer);
    } else {
        $result = ObjectDetailSnapshotSchemaMigration::apply($db, $prefix);
    }
    fwrite(STDOUT, 'RESULT ' . json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
    fflush(STDOUT);
} catch (Throwable) {
    fwrite(STDERR, "SCHEMA_WORKER_UNAVAILABLE\n");
    $exitCode = 70;
} finally {
    try { if ($db instanceof mysqli) $db->close(); }
    catch (Throwable) { $exitCode = 70; }
}
exit($exitCode);
