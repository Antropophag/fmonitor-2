<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Commands;

use FMonitor2\Runtime\RuntimeConfiguration;
use FMonitor2\Runtime\RuntimeReadiness;
use yii\console\Controller;
use yii\console\ExitCode;

final class HealthController extends Controller
{
    public function actionLive(): int
    {
        $this->stdout("{\"ok\":true}\n");
        return ExitCode::OK;
    }

    public function actionReady(): int
    {
        try {
            RuntimeReadiness::assertReady(RuntimeConfiguration::fromEnvironment(getenv()));
            return $this->actionLive();
        } catch (\Throwable) {
            $this->stdout("{\"ok\":false,\"reason\":\"SERVICE_UNAVAILABLE\"}\n");
            return ExitCode::UNAVAILABLE;
        }
    }
}
