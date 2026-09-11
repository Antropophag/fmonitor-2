<?php
declare(strict_types=1);

namespace FMonitor2\Jobs;

use FMonitor2\Workforce\WorkerConfiguration;

/** Translates the production session manifest and worker secret for Yii Jobs commands. */
final class YiiJobsRuntimeEnvironment
{
    public static function execute(string $mode, callable $command): array
    {
        $root = getenv('FMONITOR_SESSION_STATE_ROOT');
        if (!is_string($root) || $root === '' || $root[0] !== '/') self::invalid();

        $manifests = glob($root . '/pilot-demo/*/active.json') ?: [];
        if (count($manifests) !== 1 || !is_file($manifests[0]) || !is_readable($manifests[0])) self::invalid();
        try {
            $manifest = json_decode((string) file_get_contents($manifests[0]), true, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            self::invalid();
        }
        $prefix = is_array($manifest) ? ($manifest['processPrefix'] ?? null) : null;
        if (($manifest['state'] ?? null) !== 'ready' || !is_string($prefix)
            || preg_match('/^[A-Za-z0-9_]{1,25}$/D', $prefix) !== 1) self::invalid();
        putenv('FMONITOR_PROCESS_TABLE_PREFIX=' . $prefix);

        $stagedToken = null;
        try {
            if ($mode === 'worker') {
                try {
                    $values = WorkerConfiguration::fromFile((string) getenv('FMONITOR_BITRIX_CONFIG'));
                    $stagedToken = WorkerConfiguration::stageToken($values['token']);
                } catch (\Throwable) {
                    self::invalid();
                }
                putenv('FMONITOR_BITRIX_ORIGIN=' . $values['origin']);
                putenv('FMONITOR_BITRIX_WEBHOOK_USER_ID=' . $values['webhookUserId']);
                putenv('FMONITOR_BITRIX_DEPARTMENT_IDS_JSON=' . json_encode($values['departmentIds'], JSON_THROW_ON_ERROR));
                putenv('FMONITOR_BITRIX_TOKEN_FILE=' . $stagedToken);
                unset($values);
            }
            return $command();
        } finally {
            if (is_string($stagedToken)) @unlink($stagedToken);
        }
    }

    private static function invalid(): never
    {
        throw new \InvalidArgumentException('CONFIGURATION_INVALID');
    }
}
