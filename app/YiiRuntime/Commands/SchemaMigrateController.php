<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Commands;

use FMonitor2\YiiRuntime\CanonicalMigrationConsole;
use yii\console\Controller;

class SchemaMigrationCommand extends Controller
{
    public function actionRun(): int
    {
        if (array_slice($_SERVER['argv'] ?? [], 1) !== ['schema-migrate/run', '--interactive=0']) {
            return $this->finish(['ok' => false, 'reason' => 'CONFIGURATION_INVALID'], 64);
        }
        $outcome = CanonicalMigrationConsole::run();
        return $this->finish($outcome['result'], $outcome['exitCode']);
    }

    /** @param array<string,mixed> $result */
    private function finish(array $result, int $exitCode): int
    {
        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
        return $exitCode;
    }
}
