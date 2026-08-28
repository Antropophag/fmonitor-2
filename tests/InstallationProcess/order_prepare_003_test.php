<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-003, example A.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->setNow('2026-08-27T10:30:00+00:00');
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
$environment->markInstallerMissing(9999);
$environment->seedInstallerSnapshot(1042, [
    'tabId' => 1042,
    'fullName' => 'Иванов Иван Иванович',
    'position' => 'Электромеханик по лифтам',
    'status' => 'dismissed',
    'employedFrom' => '2024-02-01',
    'employedTo' => '2026-08-20',
    'source' => 'one_c_zup_via_bitrix',
    'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
]);
$environment->seedInstallerSnapshot(2088, [
    'tabId' => 2088,
    'fullName' => 'Сидоров Сергей Сергеевич',
    'position' => 'Электромеханик по лифтам',
    'status' => 'employed',
    'employedFrom' => '2025-05-10',
    'employedTo' => '2026-11-30',
    'source' => 'one_c_zup_via_bitrix',
    'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
]);
$environment->seedInstallerSnapshot(3001, [
    'tabId' => 3001,
    'fullName' => 'Монтажник Без Даты',
    'position' => 'Электромеханик по лифтам',
    'status' => 'employed',
    'employedFrom' => null,
    'employedTo' => null,
    'source' => 'one_c_zup_via_bitrix',
    'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
]);
$environment->seedInstallerSnapshot(3002, [
    'tabId' => 3002,
    'fullName' => 'Монтажник Будущий',
    'position' => 'Электромеханик по лифтам',
    'status' => 'employed',
    'employedFrom' => '2026-09-01',
    'employedTo' => null,
    'source' => 'one_c_zup_via_bitrix',
    'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
]);
$environment->seedInstallerSnapshot(3003, [
    'tabId' => 3003,
    'fullName' => 'Монтажник После Окончания',
    'position' => 'Электромеханик по лифтам',
    'status' => 'employed',
    'employedFrom' => '2024-01-01',
    'employedTo' => '2026-08-20',
    'source' => 'one_c_zup_via_bitrix',
    'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
]);
$environment->seedInstallerSnapshot(4001, [
    'tabId' => 4001,
    'fullName' => 'Монтажник На Границах',
    'position' => 'Электромеханик по лифтам',
    'status' => 'employed',
    'employedFrom' => '2026-08-27',
    'employedTo' => '2026-12-20',
    'source' => 'one_c_zup_via_bitrix',
    'sourceUpdatedAt' => '2026-08-26T18:00:00+03:00',
]);
$environment->forbidEngineerSnapshotReads();
$environment->forbidRendering();

$process = new InstallationProcess($environment);
$result = $process->prepareAssignmentOrder(
    installationObjectId: 4512,
    installerTabIds: [9999, 1042, 2088, 3001, 3002, 3003, 4001],
    controlEngineerUserId: 73,
    actorId: 18,
);

assertSameValue(
    [
        'accepted' => false,
        'violations' => [
            [
                'code' => 'INSTALLER_NOT_IN_CATALOG',
                'message' => 'Монтажник с табельным номером 9999 отсутствует в актуальном кадровом каталоге.',
                'field' => 'installerTabIds[0]',
            ],
            [
                'code' => 'INSTALLER_NOT_EMPLOYED',
                'message' => 'Монтажник с табельным номером 1042 не имеет подтверждённого периода трудоустройства на требуемый срок работ.',
                'field' => 'installerTabIds[1]',
            ],
            [
                'code' => 'INSTALLER_NOT_EMPLOYED',
                'message' => 'Монтажник с табельным номером 2088 не имеет подтверждённого периода трудоустройства на требуемый срок работ.',
                'field' => 'installerTabIds[2]',
            ],
            [
                'code' => 'INSTALLER_NOT_EMPLOYED',
                'message' => 'Монтажник с табельным номером 3001 не имеет подтверждённого периода трудоустройства на требуемый срок работ.',
                'field' => 'installerTabIds[3]',
            ],
            [
                'code' => 'INSTALLER_NOT_EMPLOYED',
                'message' => 'Монтажник с табельным номером 3002 не имеет подтверждённого периода трудоустройства на требуемый срок работ.',
                'field' => 'installerTabIds[4]',
            ],
            [
                'code' => 'INSTALLER_NOT_EMPLOYED',
                'message' => 'Монтажник с табельным номером 3003 не имеет подтверждённого периода трудоустройства на требуемый срок работ.',
                'field' => 'installerTabIds[5]',
            ],
        ],
    ],
    $result,
    'ORDER-PREPARE-003 must report every ineligible installer in input order.',
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
                'occurredAt' => '2026-08-27T10:30:00+00:00',
                'actorId' => 18,
                'payload' => [
                    'reasonCodes' => ['INSTALLER_NOT_IN_CATALOG', 'INSTALLER_NOT_EMPLOYED'],
                    'installerCount' => 7,
                    'invalidInstallerCount' => 6,
                    'controlEngineerProvided' => true,
                ],
            ],
        ],
    ],
    $process->getInstallationObjectProcess(4512),
    'ORDER-PREPARE-003 must append one non-PII rejection event without partial changes.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-003 workforce eligibility\n");
