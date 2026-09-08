<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAcceptedCommit;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalCommitStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMariaDbCompositionReader;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMariaDbRepository;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;

// Gate 2 correction for Gate 5 findings 4-5; approved contract v54.
class_exists(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory::class);
require_once dirname(__DIR__, 2) . '/app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php';
require_once dirname(__DIR__, 2) . '/app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_aoou_gate5_' . bin2hex(random_bytes(6));
assertSameValue(1, preg_match('/^t_aoou_gate5_[0-9a-f]{12}$/D', $database), 'Cleanup database is independently bounded.');
$admin = new mysqli($host, $user, $password, '', $port);
$quote = static fn (string $name): string => '`' . str_replace('`', '``', $name) . '`';

try {
    $admin->query('CREATE DATABASE ' . $quote($database) . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db = new mysqli($host, $user, $password, $database, $port);
    $db->set_charset('utf8mb4');
    $db->query('CREATE TABLE fm2_assignment_orders (id BIGINT UNSIGNED PRIMARY KEY, installation_case_id BIGINT UNSIGNED NOT NULL, version_no INT UNSIGNED NOT NULL, control_engineer_user_id BIGINT UNSIGNED NOT NULL, order_date DATE NOT NULL) ENGINE=InnoDB');
    // No uniqueness constraints: malformed legacy/member facts must be rejected
    // by the production composition reader, not hidden by this fixture.
    $db->query('CREATE TABLE fm2_order_installers (assignment_order_id BIGINT UNSIGNED NOT NULL, installer_tab_id BIGINT NOT NULL, change_action VARCHAR(20) NOT NULL, valid_from DATE NULL, valid_to DATE NULL) ENGINE=InnoDB');
    $db->query("INSERT INTO fm2_assignment_orders VALUES(81,4512,1,31,'2026-09-01')");
    $reader = new AssignmentOrderOriginalMariaDbCompositionReader($db, '');
    $invalidRows = [
        "(81,7001,'assign','2026-08-01',NULL),(81,7002,'release','2026-08-01','2026-08-31'),(81,7002,'release','2026-08-01','2026-08-31')",
        "(81,7001,'assign','2026-08-01',NULL),(81,7002,'release','2026-08-01',NULL)",
        "(81,7001,'assign','2026-08-01',NULL),(81,7002,'release','2026-09-02','2026-09-01')",
        "(81,7001,'assign','2026-08-01',NULL),(81,7002,'release','2026-08-01','2026-09-02')",
    ];
    foreach ($invalidRows as $index => $values) {
        $db->query('DELETE FROM fm2_order_installers');
        $db->query('INSERT INTO fm2_order_installers VALUES' . $values);
        $snapshot = $reader->find(4512, 81);
        assertSameValue(null, $snapshot->identity, "Malformed all-row composition case {$index} is invalid, not filtered into a valid snapshot.");
        assertSameValue([], $snapshot->installerIds, "Malformed all-row composition case {$index} exposes no partial members.");
    }

    // A transport/schema error is not a compare-and-swap business conflict.
    $repository = new AssignmentOrderOriginalMariaDbRepository($db, 'missing_', null);
    $commit = new AssignmentOrderOriginalAcceptedCommit(
        '00000000-0000-4000-8000-000000000551', 'dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d', AssignmentOrderOriginalMode::INITIAL,
        4512, 81, 18, 'original-0051', 'revision-0051', 1, null, null,
        'composition-81-v1', '388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5', '2026-09-01', '2026-09-02T09:15:30Z',
        '4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784', 327, 'content-sha256-4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784', null, 'assignment_order_original_accepted',
    );
    assertSameValue(AssignmentOrderOriginalCommitStatus::ROLLED_BACK, $repository->commitAccepted($commit), 'MariaDB technical failure is distinct from CAS CONFLICT.');
    $db->close();
} finally {
    $admin->query('DROP DATABASE IF EXISTS ' . $quote($database));
    $admin->close();
}

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_GATE5_MARIADB_RED_001_OK\n");
