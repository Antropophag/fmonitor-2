<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment;

// Specification: PERSISTENCE-PREPARE-001 v0.3, MariaDB tracer bullet.

function persistenceConnection(): mysqli
{
    $connection = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_USER') ?: 'fmonitor2_demo',
        getenv('FMONITOR_TEST_DB_PASSWORD') ?: 'fmonitor2_demo_local',
        getenv('FMONITOR_TEST_DB_NAME') ?: 'fmonitor2_demo',
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $connection->set_charset('utf8mb4');

    return $connection;
}

function executeStatements(mysqli $connection, string $sql): void
{
    $connection->multi_query($sql);
    do {
        if ($result = $connection->store_result()) {
            $result->free();
        }
    } while ($connection->more_results() && $connection->next_result());
}

$tablePrefix = 't_pp001_' . bin2hex(random_bytes(6)) . '_';
$connection = persistenceConnection();

executeStatements($connection, <<<SQL
DROP TABLE IF EXISTS {$tablePrefix}fm2_process_events;
DROP TABLE IF EXISTS {$tablePrefix}fm2_process_tasks;
DROP TABLE IF EXISTS {$tablePrefix}fm2_order_artifacts;
DROP TABLE IF EXISTS {$tablePrefix}fm2_order_installers;
DROP TABLE IF EXISTS {$tablePrefix}fm2_assignment_orders;
DROP TABLE IF EXISTS {$tablePrefix}fm2_installation_cases;

CREATE TABLE {$tablePrefix}fm2_installation_cases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    legacy_installation_object_id BIGINT UNSIGNED NOT NULL,
    process_state VARCHAR(80) NOT NULL,
    actual_start_date DATE NULL,
    opened_at VARCHAR(40) NULL,
    opened_by_user_id BIGINT UNSIGNED NULL,
    created_at VARCHAR(40) NOT NULL,
    updated_at VARCHAR(40) NOT NULL,
    lock_version INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_case_object (legacy_installation_object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {$tablePrefix}fm2_assignment_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    version_no SMALLINT UNSIGNED NOT NULL,
    kind VARCHAR(40) NOT NULL,
    status VARCHAR(40) NOT NULL,
    registration_number VARCHAR(120) NULL,
    order_date DATE NOT NULL,
    registered_at VARCHAR(40) NULL,
    registration_actor_type VARCHAR(40) NULL,
    registration_actor_id VARCHAR(120) NULL,
    registration_source VARCHAR(40) NULL,
    external_registration_id VARCHAR(120) NULL,
    control_engineer_user_id BIGINT UNSIGNED NOT NULL,
    control_engineer_fio_snapshot VARCHAR(300) NOT NULL,
    control_engineer_position_snapshot VARCHAR(300) NOT NULL,
    organization_form VARCHAR(40) NOT NULL,
    previous_assignment_order_id BIGINT UNSIGNED NULL,
    object_address_snapshot VARCHAR(500) NOT NULL,
    entrance_snapshot VARCHAR(80) NOT NULL,
    object_registration_number_snapshot VARCHAR(120) NOT NULL,
    planned_start_date_snapshot DATE NOT NULL,
    planned_finish_date_snapshot DATE NOT NULL,
    pto_act_date_snapshot DATE NULL,
    prepared_at VARCHAR(40) NOT NULL,
    prepared_by_user_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_case_version (installation_case_id, version_no),
    KEY ix_case_status (installation_case_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {$tablePrefix}fm2_order_installers (
    assignment_order_id BIGINT UNSIGNED NOT NULL,
    installer_tab_id BIGINT UNSIGNED NOT NULL,
    fio_snapshot VARCHAR(300) NOT NULL,
    position_snapshot VARCHAR(300) NOT NULL,
    employment_status_snapshot VARCHAR(40) NOT NULL,
    employed_from_snapshot DATE NOT NULL,
    employed_to_snapshot DATE NULL,
    workforce_source_snapshot VARCHAR(80) NOT NULL,
    workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NULL,
    change_action VARCHAR(40) NOT NULL,
    PRIMARY KEY (assignment_order_id, installer_tab_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {$tablePrefix}fm2_order_artifacts (
    assignment_order_id BIGINT UNSIGNED NOT NULL,
    artifact_type VARCHAR(40) NOT NULL,
    filename VARCHAR(500) NOT NULL,
    media_type VARCHAR(120) NOT NULL,
    byte_size BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    PRIMARY KEY (assignment_order_id, artifact_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {$tablePrefix}fm2_process_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    task_type VARCHAR(80) NOT NULL,
    assignee_user_id BIGINT UNSIGNED NULL,
    assignee_role VARCHAR(80) NULL,
    due_date DATE NULL,
    status VARCHAR(40) NOT NULL,
    completed_at VARCHAR(40) NULL,
    completed_by_user_id BIGINT UNSIGNED NULL,
    created_at VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE {$tablePrefix}fm2_process_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    occurred_at VARCHAR(40) NOT NULL,
    actor_user_id BIGINT UNSIGNED NOT NULL,
    payload_json JSON NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

try {
    executeStatements($connection, <<<SQL
INSERT INTO {$tablePrefix}fm2_installation_cases
    (legacy_installation_object_id, process_state, created_at, updated_at, lock_version)
VALUES (4512, 'needs_assignment_order', '2026-08-20T09:00:00+03:00', '2026-08-20T09:00:00+03:00', 1);
SQL);

    $externalFacts = new class {
        public function actorCanPrepareAssignmentOrder(int $actorId): bool
        {
            return $actorId === 18;
        }

        /** @return array<string, mixed> */
        public function getInstallationObjectSnapshot(int $installationObjectId): array
        {
            return [
                'address' => 'Москва, ул. Примерная, д. 10',
                'entrance' => '2',
                'objectRegistrationNumber' => '77-000123',
                'plannedStartDate' => '2026-10-05',
                'plannedFinishDate' => '2026-12-20',
                'ptoActDate' => null,
            ];
        }

        /** @return array<string, mixed>|null */
        public function findInstallerSnapshot(int|string $tabId): ?array
        {
            return $tabId === 1042 ? [
                'tabId' => 1042,
                'fullName' => 'Иванов Иван Иванович',
                'position' => 'Электромеханик по лифтам',
                'status' => 'employed',
                'employedFrom' => '2024-02-01',
                'employedTo' => null,
                'source' => 'one_c_zup_via_bitrix',
                'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
            ] : null;
        }

        /** @return array<string, mixed>|null */
        public function findEngineerSnapshot(int $userId): ?array
        {
            return $userId === 73 ? [
                'userId' => 73,
                'fullName' => 'Петров Пётр Петрович',
                'position' => 'Инженер строительного контроля',
                'active' => true,
                'role' => 'construction_control_engineer',
            ] : null;
        }

        /** @return list<array{type: string, filename: string, mediaType: string, bytes: string}> */
        public function renderAssignmentOrder(array $documentInput): array
        {
            return [
                [
                    'type' => 'order',
                    'filename' => 'assignment-order-4512-v1.pdf',
                    'mediaType' => 'application/pdf',
                    'bytes' => 'ORDER-PREPARE-002 example A order document',
                ],
                [
                    'type' => 'appendix',
                    'filename' => 'assignment-order-4512-v1-appendix.pdf',
                    'mediaType' => 'application/pdf',
                    'bytes' => 'ORDER-PREPARE-002 example A appendix',
                ],
            ];
        }

        public function now(): string
        {
            return '2026-08-26T21:30:00+00:00';
        }
    };

    $environment = new MariaDbInstallationProcessEnvironment($connection, $externalFacts, $tablePrefix);
    $process = new InstallationProcess($environment);
    $result = $process->prepareAssignmentOrder(4512, [1042], 73, 18);

    assertSameValue(
        [
            'accepted' => true,
            'assignmentOrderVersion' => 1,
            'status' => 'prepared',
            'assignmentOrderDate' => '2026-08-27',
            'organizationType' => 'individual',
        ],
        $result,
        'PERSISTENCE-PREPARE-001 must return the stored prepared version.',
    );

    $connection->close();
    unset($process, $environment);

    $reloadedConnection = persistenceConnection();
    $externalFactsForbiddenOnReload = new class {
        public function __call(string $name, array $arguments): never
        {
            throw new LogicException("External fact {$name} must not be read while reloading persisted process facts.");
        }
    };
    $reloadedEnvironment = new MariaDbInstallationProcessEnvironment(
        $reloadedConnection,
        $externalFactsForbiddenOnReload,
        $tablePrefix,
    );
    $reloadedProcess = new InstallationProcess($reloadedEnvironment);

    assertSameValue(
        [
            'installationObjectId' => 4512,
            'processState' => 'assignment_order_prepared',
            'assignmentOrders' => [[
                'version' => 1,
                'status' => 'prepared',
                'registrationNumber' => null,
                'assignmentOrderDate' => '2026-08-27',
                'organizationType' => 'individual',
                'installationObjectSnapshot' => [
                    'address' => 'Москва, ул. Примерная, д. 10',
                    'entrance' => '2',
                    'objectRegistrationNumber' => '77-000123',
                    'plannedStartDate' => '2026-10-05',
                    'plannedFinishDate' => '2026-12-20',
                    'ptoActDate' => null,
                ],
                'installers' => [[
                    'tabId' => 1042,
                    'fullName' => 'Иванов Иван Иванович',
                    'position' => 'Электромеханик по лифтам',
                    'status' => 'employed',
                    'employedFrom' => '2024-02-01',
                    'employedTo' => null,
                    'source' => 'one_c_zup_via_bitrix',
                    'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
                ]],
                'controlEngineer' => [
                    'userId' => 73,
                    'fullName' => 'Петров Пётр Петрович',
                    'position' => 'Инженер строительного контроля',
                    'active' => true,
                    'role' => 'construction_control_engineer',
                ],
                'artifacts' => [
                    [
                        'type' => 'order',
                        'filename' => 'assignment-order-4512-v1.pdf',
                        'mediaType' => 'application/pdf',
                        'size' => 42,
                        'sha256' => '71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4',
                    ],
                    [
                        'type' => 'appendix',
                        'filename' => 'assignment-order-4512-v1-appendix.pdf',
                        'mediaType' => 'application/pdf',
                        'size' => 36,
                        'sha256' => '6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7',
                    ],
                ],
            ]],
            'assignments' => [
                ['role' => 'installer', 'tabId' => 1042, 'assignmentOrderVersion' => 1, 'status' => 'preliminary'],
                ['role' => 'control_engineer', 'userId' => 73, 'assignmentOrderVersion' => 1, 'status' => 'preliminary'],
            ],
            'openTasks' => [],
            'installationOpened' => false,
            'checklistAvailable' => false,
            'events' => [[
                'type' => 'assignment_order_prepared',
                'occurredAt' => '2026-08-26T21:30:00+00:00',
                'actorId' => 18,
                'payload' => [
                    'assignmentOrderVersion' => 1,
                    'assignmentOrderDate' => '2026-08-27',
                    'installerTabIds' => [1042],
                    'controlEngineerUserId' => 73,
                    'organizationType' => 'individual',
                    'artifactSha256' => [
                        'order' => '71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4',
                        'appendix' => '6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7',
                    ],
                ],
            ]],
        ],
        $reloadedProcess->getInstallationObjectProcess(4512),
        'PERSISTENCE-PREPARE-001 must survive a new module instance and DB connection.',
    );

    $reloadedConnection->close();
    fwrite(STDOUT, "PASS PERSISTENCE-PREPARE-001 MariaDB tracer bullet\n");
} finally {
    $cleanup = persistenceConnection();
    executeStatements($cleanup, <<<SQL
DROP TABLE IF EXISTS {$tablePrefix}fm2_process_events;
DROP TABLE IF EXISTS {$tablePrefix}fm2_process_tasks;
DROP TABLE IF EXISTS {$tablePrefix}fm2_order_artifacts;
DROP TABLE IF EXISTS {$tablePrefix}fm2_order_installers;
DROP TABLE IF EXISTS {$tablePrefix}fm2_assignment_orders;
DROP TABLE IF EXISTS {$tablePrefix}fm2_installation_cases;
SQL);
    $cleanup->close();
}
