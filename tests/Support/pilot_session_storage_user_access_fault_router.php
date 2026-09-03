<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$shlzCss = dirname($root) . '/shlz-ui/packages/styles/dist/shlz.css';
putenv('FMONITOR_SHLZ_CSS_PATH=' . $shlzCss);
putenv('FMONITOR_PILOT_CSS_PATH=' . $root . '/app/PilotHttp/pilot.css');
putenv('FMONITOR_ARTIFACT_STORAGE_ROOT=' . (string) getenv('FMONITOR_SESSION_STATE_ROOT') . '/artifacts');
putenv('FMONITOR_NOW=2026-09-03T12:00:00+03:00');
putenv('FMONITOR_LEGACY_TABLE_PREFIX=');
putenv('FMONITOR_PROCESS_TABLE_PREFIX=');
spl_autoload_register(static function (string $class) use ($root): void {
    foreach (['FMonitor2\\' => $root . '/app/', 'FMonitor\\IdentityAccess\\' => $root . '/app/IdentityAccess/'] as $prefix => $base) {
        if (!str_starts_with($class, $prefix)) continue;
        $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) require_once $file;
        return;
    }
});
foreach (['PilotHttp', 'PilotView', 'PilotShellView', 'ObjectListView', 'ConstructionControlView', 'ObjectCardView', 'ChecklistView', 'PrepareFormView', 'InstallerDirectoryView', 'UserDirectoryView', 'PilotE2ECoordinator', 'ProductionPilotHttpEntrypointFactory'] as $file) require_once "$root/app/PilotHttp/$file.php";
require_once __DIR__ . '/PilotSessionStoragePublicApiFixture.php';
$trace = (string) getenv('FMONITOR_SESSION_STATE_ROOT') . '/user-access-fault-trace.jsonl';
$observer = new class($trace) implements FMonitor\IdentityAccess\PilotSessionLifecycleObserver {
    public function __construct(private readonly string $trace) {}
    public function observe(FMonitor\IdentityAccess\PilotSessionFilesystemEvent $event): void
    {
        if ($event->phase() !== FMonitor\IdentityAccess\PilotSessionFilesystemPhase::AFTER) return;
        file_put_contents($this->trace, json_encode([
            'operation' => $event->operation()->value,
            'artifact' => $event->artifact()->value,
            'ordinal' => $event->ordinal(),
            'outcome' => $event->outcome() === FMonitor\IdentityAccess\PilotSessionPrimitiveOutcome::NATIVE_FALSE
                ? 'native_false'
                : $event->outcome()?->value,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
    }
};
$entry = FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies(
    new FMonitor2\PilotHttp\ProcessEnvironmentSource(),
    new FMonitor2\Tests\Support\NativePilotSessionFilesystem('rename', 'committed', 1, 'false'),
    new FMonitor2\Tests\Support\FixedPilotSessionClock(1_788_200_000, 1_000),
    new FMonitor2\Tests\Support\LengthQueuedPilotSessionEntropy([16 => [str_repeat("\x72", 16)], 12 => [str_repeat("\x73", 12)]]),
    $observer,
);
$response = $entry->handle($_SERVER);
http_response_code($response->status);
header_remove('Server');
header_remove('X-Powered-By');
foreach ($response->headers as $name => $value) header("$name: $value");
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo $response->body;
