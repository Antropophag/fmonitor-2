<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\Jobs\{JobWorkerProcess, MariaDbJobQueue, MariaDbWorkerHeartbeat};

$host = getenv('FMONITOR_DB_HOST');
$port = (int) getenv('FMONITOR_DB_PORT');
$name = getenv('FMONITOR_DB_NAME');
$user = getenv('FMONITOR_DB_USER');
$password = getenv('FMONITOR_DB_PASSWORD');
$prefix = getenv('FMONITOR_PROCESS_TABLE_PREFIX');
$db = new mysqli($host, $user, $password, $name, $port);
try {
    $queue = new MariaDbJobQueue($db, $prefix, null, null, ['erp.equipment-facts.sync' => [1]]);
    $command = static fn(array $job): array => [PHP_BINARY, __DIR__ . '/erp_equipment_facts_handler.php'];
    $worker = new JobWorkerProcess($queue, new MariaDbWorkerHeartbeat($db, $prefix),
        ['erp.equipment-facts.sync' => [1 => $command]],
        static fn(): string => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'),
        'worker:erp-test', 1, 20_000, 2);
    $result = $worker->run();
    echo json_encode($result, JSON_THROW_ON_ERROR), "\n";
} finally { $db->close(); }
