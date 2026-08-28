<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-004, examples A-C.

/** @param array<string, mixed>|null $engineerSnapshot */
function assertEngineerRejected(?array $engineerSnapshot, string $scenario): void
{
    $environment = new InMemoryInstallationProcessEnvironment();
    $environment->allowPreparationBy(18);
    $environment->setNow('2026-08-27T14:00:00+03:00');
    $environment->seedInstallationObjectProcess(4512, [
        'installationObjectId' => 4512,
        'processState' => 'needs_assignment_order',
        'assignmentOrders' => [],
        'assignments' => [],
        'openTasks' => [
            ['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr'],
        ],
        'installationOpened' => false,
        'checklistAvailable' => false,
        'events' => [],
    ]);
    $environment->seedInstallationObjectSnapshot(4512, [
        'address' => 'Москва, ул. Примерная, д. 10',
        'entrance' => '2',
        'objectRegistrationNumber' => '77-000123',
        'plannedStartDate' => '2026-10-05',
        'plannedFinishDate' => '2026-12-20',
        'ptoActDate' => null,
    ]);
    $environment->seedInstallerSnapshot(1042, [
        'tabId' => 1042,
        'fullName' => 'Иванов Иван Иванович',
        'position' => 'Электромеханик по лифтам',
        'status' => 'employed',
        'employedFrom' => '2024-02-01',
        'employedTo' => null,
        'source' => 'one_c_zup_via_bitrix',
        'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
    ]);
    if ($engineerSnapshot === null) {
        $environment->markEngineerMissing(73);
    } else {
        $environment->seedEngineerSnapshot(73, $engineerSnapshot);
    }
    $environment->forbidRendering();

    $process = new InstallationProcess($environment);
    $result = $process->prepareAssignmentOrder(
        installationObjectId: 4512,
        installerTabIds: [1042],
        controlEngineerUserId: 73,
        actorId: 18,
    );

    assertSameValue(
        [
            'accepted' => false,
            'violations' => [
                [
                    'code' => 'CONTROL_ENGINEER_NOT_ELIGIBLE',
                    'message' => 'Выбранный пользователь не является активным инженером строительного контроля.',
                    'field' => 'controlEngineerUserId',
                ],
            ],
        ],
        $result,
        "ORDER-PREPARE-004 must reject {$scenario}.",
    );

    assertSameValue(
        [
            'installationObjectId' => 4512,
            'processState' => 'needs_assignment_order',
            'assignmentOrders' => [],
            'assignments' => [],
            'openTasks' => [
                ['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr'],
            ],
            'installationOpened' => false,
            'checklistAvailable' => false,
            'events' => [
                [
                    'type' => 'assignment_order_prepare_rejected',
                    'occurredAt' => '2026-08-27T14:00:00+03:00',
                    'actorId' => 18,
                    'payload' => [
                        'reasonCodes' => ['CONTROL_ENGINEER_NOT_ELIGIBLE'],
                        'installerCount' => 1,
                        'controlEngineerProvided' => true,
                        'controlEngineerEligible' => false,
                    ],
                ],
            ],
        ],
        $process->getInstallationObjectProcess(4512),
        "ORDER-PREPARE-004 must reject {$scenario} without partial changes or identity in audit.",
    );
}

assertEngineerRejected(
    [
        'userId' => 73,
        'fullName' => 'Петров Пётр Петрович',
        'position' => 'Инженер строительного контроля',
        'active' => false,
        'role' => 'construction_control_engineer',
    ],
    'an inactive control engineer',
);

assertEngineerRejected(
    [
        'userId' => 73,
        'fullName' => 'Петров Пётр Петрович',
        'position' => 'Сотрудник ФКР',
        'active' => true,
        'role' => 'fkr',
    ],
    'an active user with the wrong role',
);

assertEngineerRejected(null, 'a user absent from the directory');

fwrite(STDOUT, "PASS ORDER-PREPARE-004 control engineer eligibility\n");
