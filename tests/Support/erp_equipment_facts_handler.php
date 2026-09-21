<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\InstallationProcess\EquipmentFactsApplication;
use FMonitor2\Jobs\{JobHandlerClaim, JobHandlerRuntime, JobsRuntimeConfiguration};

try {
    $job = JobHandlerClaim::decode((string) stream_get_contents(STDIN, 65536));
    $config = JobsRuntimeConfiguration::fromEnvironment();
    $result = JobHandlerRuntime::handle($job, $config, static function () use ($config): array {
        $db = new mysqli($config->value('FMONITOR_DB_HOST'), $config->value('FMONITOR_DB_USER'),
            $config->value('FMONITOR_DB_PASSWORD'), $config->value('FMONITOR_DB_NAME'), $config->port());
        try {
            $owner = new EquipmentFactsApplication($db, $config->prefix(),
                $config->value('FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY'));
            $mode = $config->value('FMONITOR_TEST_ERP_HANDLER_MODE');
            $command = [
                'actor' => ['type' => 'system', 'id' => 'erp-equipment-facts-hourly-v1'],
                'kind' => $mode === 'completed' ? 'complete' : 'failed',
                'runId' => $mode === 'completed'
                    ? '41414141-4141-4141-8141-414141414141'
                    : '42424242-4242-4242-8242-424242424242',
                'observedAtUtc' => '2026-09-21T09:00:00.000000Z',
            ];
            $command += $mode === 'completed' ? ['records' => []] : ['reason' => 'SOURCE_UNAVAILABLE'];
            return $owner->execute($command);
        } finally { $db->close(); }
    });
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
    exit(0);
} catch (Throwable) {
    echo "{\"status\":\"retryable\",\"failureCode\":\"JOB_HANDLER_FAILED\"}\n";
    exit(0);
}
