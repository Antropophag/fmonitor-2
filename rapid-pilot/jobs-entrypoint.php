<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/autoload.php';

use FMonitor2\Jobs\JobsRuntimeCommand;
use FMonitor2\Workforce\WorkerConfiguration;

// Pilot deployment translation only. Jobs owns scheduling, execution and health.
$stagedToken = null;
try {
    $arguments = array_slice($argv, 1);
    if (count($arguments) !== 1 || !in_array($arguments[0], ['worker', 'scheduler', 'health'], true)) {
        throw new InvalidArgumentException();
    }
    $stateRoot = getenv('FMONITOR_SESSION_STATE_ROOT');
    if (!is_string($stateRoot) || $stateRoot === '' || $stateRoot[0] !== '/') {
        throw new InvalidArgumentException();
    }
    $manifests = glob($stateRoot . '/pilot-demo/*/active.json') ?: [];
    if (count($manifests) !== 1 || !is_readable($manifests[0])) {
        throw new InvalidArgumentException();
    }
    $manifest = json_decode((string) file_get_contents($manifests[0]), true, 32, JSON_THROW_ON_ERROR);
    $prefix = $manifest['processPrefix'] ?? null;
    if (($manifest['state'] ?? null) !== 'ready' || !is_string($prefix)
        || preg_match('/^[A-Za-z0-9_]{1,25}$/D', $prefix) !== 1) {
        throw new InvalidArgumentException();
    }
    putenv('FMONITOR_PROCESS_TABLE_PREFIX=' . $prefix);

    if ($arguments[0] === 'worker') {
        try {
            $values = WorkerConfiguration::fromFile((string) getenv('FMONITOR_BITRIX_CONFIG'));
            $stagedToken = WorkerConfiguration::stageToken($values['token']);
        } catch (Throwable) {
            throw new InvalidArgumentException();
        }
        putenv('FMONITOR_BITRIX_ORIGIN=' . $values['origin']);
        putenv('FMONITOR_BITRIX_WEBHOOK_USER_ID=' . $values['webhookUserId']);
        putenv('FMONITOR_BITRIX_DEPARTMENT_IDS_JSON=' . json_encode($values['departmentIds'], JSON_THROW_ON_ERROR));
        putenv('FMONITOR_BITRIX_TOKEN_FILE=' . $stagedToken);
        unset($values);
    }
    [$exit, $result] = JobsRuntimeCommand::execute($arguments);
} catch (InvalidArgumentException | JsonException) {
    $exit = 64;
    $result = ['ok' => false, 'error' => 'CONFIGURATION_INVALID'];
} catch (Throwable) {
    $exit = 70;
    $result = ['ok' => false, 'error' => 'JOBS_UNAVAILABLE'];
} finally {
    if (is_string($stagedToken)) {
        @unlink($stagedToken);
    }
}
echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
exit($exit);
