<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-006, example A.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->setNow('2026-08-28T12:15:00+03:00');
$environment->seedInstallationObjectProcess(4512, [
    'installationObjectId' => 4512,
    'processState' => 'needs_assignment_order',
    'assignmentOrders' => [],
    'assignments' => [],
    'openTasks' => [['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr']],
    'installationOpened' => false,
    'checklistAvailable' => false,
    'events' => [],
]);
$environment->seedInstallationObjectProcessRevision(4512, 7);
$environment->seedInstallationObjectSnapshot(4512, [
    'address' => 'Москва, ул. Примерная, д. 10',
    'entrance' => '2',
    'objectRegistrationNumber' => '77-000123',
    'plannedStartDate' => '2026-10-05',
    'plannedFinishDate' => '2026-12-20',
    'ptoActDate' => null,
]);
$environment->seedInstallerSnapshot(2088, [
    'tabId' => 2088,
    'fullName' => 'Сидоров Сидор Сидорович',
    'status' => 'employed',
    'employedFrom' => '2025-01-01',
    'employedTo' => null,
]);
$environment->seedEngineerSnapshot(74, [
    'userId' => 74,
    'fullName' => 'Смирнова Анна Сергеевна',
    'active' => true,
    'role' => 'construction_control_engineer',
]);
$environment->setRenderedArtifacts([
    ['type' => 'order', 'filename' => 'losing-order.pdf', 'mediaType' => 'application/pdf', 'bytes' => 'losing order'],
    ['type' => 'appendix', 'filename' => 'losing-appendix.pdf', 'mediaType' => 'application/pdf', 'bytes' => 'losing appendix'],
]);
$environment->forbidRepeatedRendering();

$winnerProcess = [
    'installationObjectId' => 4512,
    'processState' => 'assignment_order_prepared',
    'assignmentOrders' => [[
        'version' => 1,
        'status' => 'prepared',
        'registrationNumber' => null,
        'assignmentOrderDate' => '2026-08-28',
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
            'sourceUpdatedAt' => '2026-08-27T18:00:00+03:00',
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
        'occurredAt' => '2026-08-28T12:14:59+03:00',
        'actorId' => 17,
        'payload' => [
            'assignmentOrderVersion' => 1,
            'assignmentOrderDate' => '2026-08-28',
            'installerTabIds' => [1042],
            'controlEngineerUserId' => 73,
            'organizationType' => 'individual',
            'artifactSha256' => [
                'order' => '71656f4e5ff9503697a22a0cbbe44f7ddf626aa6293bc646eb0fb601216337c4',
                'appendix' => '6b662f08f20c3e5aab3c12ffa19dbccf7dfd38e3c2aaaea481be4fb077282fe7',
            ],
        ],
    ]],
];
$environment->simulateConcurrentProcessReplacement(4512, $winnerProcess, 8);
$concurrentAuditEvent = [
    'type' => 'installation_object_note_added',
    'occurredAt' => '2026-08-28T12:14:59+03:00',
    'actorId' => 19,
    'payload' => ['noteId' => 31],
];
$environment->simulateConcurrentAuditAppend(4512, $concurrentAuditEvent, 9);

$process = new InstallationProcess($environment);
$result = $process->prepareAssignmentOrder(4512, [2088], 74, 18);

assertSameValue(
    [
        'accepted' => false,
        'violations' => [[
            'code' => 'CONCURRENT_MODIFICATION',
            'message' => 'Объект монтажа изменился во время подготовки распоряжения. Обновите данные и повторите действие при необходимости.',
            'field' => null,
        ]],
    ],
    $result,
    'ORDER-PREPARE-006 must reject the second simultaneous preparation.',
);

$winnerProcess['events'][] = $concurrentAuditEvent;
$winnerProcess['events'][] = [
    'type' => 'assignment_order_prepare_rejected',
    'occurredAt' => '2026-08-28T12:15:00+03:00',
    'actorId' => 18,
    'payload' => [
        'reasonCodes' => ['CONCURRENT_MODIFICATION'],
        'installerCount' => 1,
        'controlEngineerProvided' => true,
        'observedProcessRevision' => 7,
        'currentProcessRevision' => 8,
    ],
];
assertSameValue(
    $winnerProcess,
    $process->getInstallationObjectProcess(4512),
    'ORDER-PREPARE-006 must preserve the first saved order and append only the second rejection audit.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-006 concurrent first preparation\n");
