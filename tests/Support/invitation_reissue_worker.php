<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor2\IdentityAccess\MariaDbReissueUserInvitation;

$db = new mysqli(
    (string) getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
    (string) getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
    (string) getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
    (string) ($argv[1] ?? ''),
    (int) ((string) getenv('FMONITOR_TEST_DB_PORT') ?: '23306'),
);
$db->set_charset('utf8mb4');
try {
    $result = (new MariaDbReissueUserInvitation($db, (string) ($argv[2] ?? '')))->reissue((int) ($argv[3] ?? 0), (int) ($argv[4] ?? 0));
    echo json_encode($result, JSON_THROW_ON_ERROR) . "\n";
} finally {
    $db->close();
}
