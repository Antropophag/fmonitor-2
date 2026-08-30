<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/LegacyObjectProfiler.php';

try {
    foreach (['FMONITOR_SOURCE_USER','FMONITOR_SOURCE_PASSWORD'] as $name) {
        if (!is_string(getenv($name)) || getenv($name) === '') throw new InvalidArgumentException('CONFIGURATION_INVALID');
    }
    $port = filter_var(getenv('FMONITOR_SOURCE_PORT') ?: '13306', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    $pageSize = filter_var(getenv('LEGACY_PROFILE_PAGE_SIZE') ?: '500', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5000]]);
    if ($port === false || $pageSize === false) throw new InvalidArgumentException('CONFIGURATION_INVALID');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli((string)(getenv('FMONITOR_SOURCE_HOST') ?: '127.0.0.1'), (string)getenv('FMONITOR_SOURCE_USER'), (string)getenv('FMONITOR_SOURCE_PASSWORD'), (string)(getenv('FMONITOR_SOURCE_NAME') ?: 'c1_fmonitor'), $port);
    $db->set_charset('utf8mb4');
    $result = (new LegacyObjectMySqlProfiler($db, $pageSize))->profile();
    $db->close();
    echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
} catch (InvalidArgumentException $error) {
    fwrite(STDOUT, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n"); exit(64);
} catch (Throwable) {
    fwrite(STDOUT, "{\"ok\":false,\"reason\":\"LEGACY_PROFILE_UNAVAILABLE\"}\n"); exit(69);
}
