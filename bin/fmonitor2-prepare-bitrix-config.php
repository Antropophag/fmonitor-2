<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/autoload.php';

use FMonitor2\Workforce\BitrixWorkforceDeliveryConfig;
use FMonitor2\Workforce\BitrixWorkforceDeliveryFactory;
use FMonitor2\Workforce\WorkerConfiguration;

// Operator adapter: read only literal Bitrix fields, never execute/source .env.
$temporary = null;
$reason = 'Проверьте FMONITOR_BITRIX_WEBHOOK_URL и FMONITOR_BITRIX_DEPARTMENT_IDS_JSON в .env.';
try {
    if ($argc !== 3 || !is_file($argv[1]) || !is_readable($argv[1])) {
        throw new RuntimeException('CONFIGURATION_INVALID');
    }
    $source = @file_get_contents($argv[1]);
    if (!is_string($source)) throw new RuntimeException('CONFIGURATION_INVALID');
    $keys = ['FMONITOR_BITRIX_WEBHOOK_URL', 'FMONITOR_BITRIX_DEPARTMENT_IDS_JSON'];
    $values = [];
    foreach (preg_split('/\r\n|\n|\r/', $source) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=(.*)$/D', $line, $assignment) !== 1) {
            foreach ($keys as $key) {
                if (str_contains($line, $key)) throw new RuntimeException('CONFIGURATION_INVALID');
            }
            continue;
        }
        $key = $assignment[1];
        if (!in_array($key, $keys, true)) continue;
        if (array_key_exists($key, $values)) throw new RuntimeException('CONFIGURATION_INVALID');
        $value = trim($assignment[2]);
        if ($value !== '' && ($value[0] === "'" || $value[0] === '"')) {
            if (strlen($value) < 2 || substr($value, -1) !== $value[0]) {
                throw new RuntimeException('CONFIGURATION_INVALID');
            }
            $value = substr($value, 1, -1);
        }
        if (str_contains($value, '\\')) throw new RuntimeException('CONFIGURATION_INVALID');
        $values[$key] = $value;
    }
    if (count($values) !== count($keys)) throw new RuntimeException('CONFIGURATION_INVALID');
    $departments = json_decode($values[$keys[1]], false, 8, JSON_THROW_ON_ERROR);
    if (!is_array($departments)) throw new RuntimeException('CONFIGURATION_INVALID');
    $document = json_encode(['baseUrl' => $values[$keys[0]], 'departments' => $departments], JSON_THROW_ON_ERROR);

    $directory = dirname($argv[2]);
    if ((!is_dir($directory) && !@mkdir($directory, 0700, true)) || !@chmod($directory, 0700)) {
        throw new RuntimeException('CONFIGURATION_WRITE_FAILED');
    }
    $directory = realpath($directory);
    if (!is_string($directory)) throw new RuntimeException('CONFIGURATION_WRITE_FAILED');
    $temporary = @tempnam($directory, '.bitrix-');
    if (!is_string($temporary) || dirname($temporary) !== $directory || !@chmod($temporary, 0600)
        || @file_put_contents($temporary, $document, LOCK_EX) !== strlen($document)) {
        throw new RuntimeException('CONFIGURATION_WRITE_FAILED');
    }
    $parsed = WorkerConfiguration::fromFile($temporary);
    // Factory construction validates native client limits, but performs no fetch.
    BitrixWorkforceDeliveryFactory::create(new BitrixWorkforceDeliveryConfig(
        $parsed['origin'], $parsed['webhookUserId'], $temporary, $parsed['departmentIds'],
    ));
    if (!@rename($temporary, $argv[2])) throw new RuntimeException('CONFIGURATION_WRITE_FAILED');
    $temporary = null;
    echo "Bitrix: конфигурация подготовлена.\n";
    $exit = 0;
} catch (Throwable $error) {
    if ($error->getMessage() === 'CONFIGURATION_WRITE_FAILED') {
        $reason = 'Проверьте права записи приватного каталога .local для настройки Bitrix из .env.';
    }
    fwrite(STDERR, "Bitrix: {$reason}\n");
    $exit = 1;
} finally {
    if (is_string($temporary)) @unlink($temporary);
}
exit($exit);
