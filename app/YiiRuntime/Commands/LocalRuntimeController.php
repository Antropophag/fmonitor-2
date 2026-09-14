<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Commands;

use FMonitor2\InstallationProcess\MariaDbLocalRuntimeAccountSchemaMigration;
use yii\console\Controller;

final class LocalRuntimeController extends Controller
{
    public function actionProvisionDatabase(): int
    {
        if (array_slice($_SERVER['argv'] ?? [], 1) !== ['local-runtime/provision-database', '--interactive=0']) {
            return $this->finish('LOCAL_CONFIG_INVALID', 64);
        }

        $outcome = MariaDbLocalRuntimeAccountSchemaMigration::execute();
        return $this->finish($outcome['reason'], $outcome['exitCode']);
    }

    private function finish(string $reason, int $exitCode): int
    {
        echo $reason, "\n";
        return $exitCode;
    }
}
