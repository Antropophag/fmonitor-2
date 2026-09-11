<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Commands;

use FMonitor2\Jobs\JobsRuntimeCommand;
use FMonitor2\Jobs\YiiJobsRuntimeEnvironment;
use yii\console\Controller;

final class JobsController extends Controller
{
    public function actionWorker(): int { return $this->execute('worker'); }
    public function actionScheduler(): int { return $this->execute('scheduler'); }
    public function actionHealth(): int { return $this->execute('health'); }

    private function execute(string $mode): int
    {
        try {
            [$exit, $result] = YiiJobsRuntimeEnvironment::execute(
                $mode,
                static fn(): array => JobsRuntimeCommand::execute([$mode]),
            );
        } catch (\InvalidArgumentException | \JsonException) {
            $exit = 64;
            $result = ['ok' => false, 'error' => 'CONFIGURATION_INVALID'];
        } catch (\Throwable) {
            $exit = 70;
            $result = ['ok' => false, 'error' => 'JOBS_UNAVAILABLE'];
        }
        $this->stdout(json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
        return $exit;
    }
}
