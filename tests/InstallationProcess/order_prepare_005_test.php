<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-005, examples A-B.

function assertRepeatedFirstPreparationRejected(string $status): void
{
    $registrationNumber = $status === 'registered' ? '12-Р' : null;
    $processState = $status === 'registered' ? 'ready_to_open' : 'assignment_order_prepared';
    $assignmentStatus = $status === 'registered' ? 'active' : 'preliminary';
    $existingEventType = $status === 'registered'
        ? 'assignment_order_registration_confirmed'
        : 'assignment_order_prepared';

    $existingProcess = [
        'installationObjectId' => 4512,
        'processState' => $processState,
        'assignmentOrders' => [
            [
                'version' => 1,
                'status' => $status,
                'registrationNumber' => $registrationNumber,
                'assignmentOrderDate' => '2026-08-20',
                'organizationType' => 'individual',
                'installationObjectSnapshot' => [
                    'address' => 'Москва, ул. Сохранённая, д. 7',
                    'entrance' => '1',
                    'objectRegistrationNumber' => '77-0004512',
                    'plannedStartDate' => '2026-10-05',
                    'plannedFinishDate' => '2026-12-20',
                    'ptoActDate' => null,
                ],
                'installers' => [
                    ['tabId' => 1042, 'fullName' => 'Иванов Иван Иванович'],
                ],
                'controlEngineer' => ['userId' => 73, 'fullName' => 'Петров Пётр Петрович'],
                'artifacts' => [
                    ['type' => 'order', 'filename' => 'assignment-order-4512-v1.pdf', 'sha256' => 'saved-order-hash'],
                    ['type' => 'appendix', 'filename' => 'assignment-order-4512-v1-appendix.pdf', 'sha256' => 'saved-appendix-hash'],
                ],
            ],
        ],
        'assignments' => [
            ['role' => 'installer', 'tabId' => 1042, 'assignmentOrderVersion' => 1, 'status' => $assignmentStatus],
            ['role' => 'control_engineer', 'userId' => 73, 'assignmentOrderVersion' => 1, 'status' => $assignmentStatus],
        ],
        'openTasks' => $status === 'registered'
            ? [['type' => 'open_installation', 'assigneeRole' => 'fkr']]
            : [],
        'installationOpened' => false,
        'checklistAvailable' => false,
        'events' => [
            [
                'type' => $existingEventType,
                'occurredAt' => '2026-08-20T10:00:00+03:00',
                'actorId' => 17,
                'payload' => ['assignmentOrderVersion' => 1],
            ],
        ],
    ];

    $environment = new InMemoryInstallationProcessEnvironment();
    $environment->allowPreparationBy(18);
    $environment->setNow('2026-08-28T12:15:00+03:00');
    $environment->seedInstallationObjectProcess(4512, $existingProcess);
    $environment->forbidInstallationObjectSnapshotReads();
    $environment->forbidInstallerSnapshotReads();
    $environment->forbidEngineerSnapshotReads();
    $environment->forbidRendering();

    $process = new InstallationProcess($environment);
    $result = $process->prepareAssignmentOrder(4512, [2088], 74, 18);

    assertSameValue(
        [
            'accepted' => false,
            'violations' => [
                [
                    'code' => 'ASSIGNMENT_ORDER_ALREADY_PREPARED',
                    'message' => 'По объекту монтажа уже существует актуальное распоряжение. Для изменения состава подготовьте изменяющее распоряжение.',
                    'field' => null,
                ],
            ],
        ],
        $result,
        "ORDER-PREPARE-005 must reject repeated first preparation for {$status} version.",
    );

    $expectedProcess = $existingProcess;
    $expectedProcess['events'][] = [
        'type' => 'assignment_order_prepare_rejected',
        'occurredAt' => '2026-08-28T12:15:00+03:00',
        'actorId' => 18,
        'payload' => [
            'reasonCodes' => ['ASSIGNMENT_ORDER_ALREADY_PREPARED'],
            'installerCount' => 1,
            'controlEngineerProvided' => true,
            'currentOrderVersion' => 1,
        ],
    ];
    assertSameValue(
        $expectedProcess,
        $process->getInstallationObjectProcess(4512),
        "ORDER-PREPARE-005 must preserve the {$status} version and append only its rejection audit.",
    );
}

assertRepeatedFirstPreparationRejected('prepared');
assertRepeatedFirstPreparationRejected('registered');

fwrite(STDOUT, "PASS ORDER-PREPARE-005 repeated first preparation\n");
