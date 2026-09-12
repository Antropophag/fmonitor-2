<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Commands;

use FMonitor2\YiiRuntime\WorkforceSyncConsole;
use yii\console\Controller;

final class WorkforceSyncController extends Controller
{
    public function actionRun(): int
    {
        if (array_slice($GLOBALS['FMONITOR2_RAW_ARGV'] ?? [], 1) !== ['workforce-sync/run', '--interactive=0']) {
            return $this->finish(['ok' => false, 'reason' => 'CONFIGURATION_INVALID'], 64);
        }
        $outcome = WorkforceSyncConsole::run();
        return $this->finish($outcome['result'], $outcome['exitCode']);
    }

    private function finish(array $result, int $exitCode): int
    {
        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
        return $exitCode;
    }
}
