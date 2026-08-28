<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-002-C, example A.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->setNow('2026-08-27T12:05:00+03:00');
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
    'entrance' => '   ',
    'objectRegistrationNumber' => '77-000123',
    'plannedStartDate' => '2026-10-05',
    'plannedFinishDate' => '2026-12-20',
    'ptoActDate' => null,
]);
$environment->forbidInstallerSnapshotReads();
$environment->forbidEngineerSnapshotReads();
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
                'code' => 'INSTALLATION_OBJECT_REQUIRED_DATA_MISSING',
                'message' => 'В объекте монтажа не заполнен подъезд или секция объекта.',
                'field' => 'entrance',
            ],
        ],
    ],
    $result,
    'ORDER-PREPARE-002-C must reject preparation when entrance is blank.',
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
                'occurredAt' => '2026-08-27T12:05:00+03:00',
                'actorId' => 18,
                'payload' => [
                    'reasonCodes' => ['INSTALLATION_OBJECT_REQUIRED_DATA_MISSING'],
                    'missingFields' => ['entrance'],
                    'installerCount' => 1,
                    'controlEngineerProvided' => true,
                ],
            ],
        ],
    ],
    $process->getInstallationObjectProcess(4512),
    'ORDER-PREPARE-002-C must append one rejection event without partial process changes.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-002-C blank entrance\n");
