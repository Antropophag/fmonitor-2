<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-001-E.
$initialProcess = [
    'installationObjectId' => 4512,
    'processState' => 'needs_assignment_order',
    'assignmentOrders' => [],
    'assignments' => [],
    'openTasks' => [
        ['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr'],
    ],
    'events' => [],
];

$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->seedInstallationObjectProcess(4512, $initialProcess);
$environment->setNow('2026-08-27T10:15:30+03:00');

$process = new InstallationProcess($environment);
$process->prepareAssignmentOrder(
    installationObjectId: 4512,
    installerTabIds: [],
    controlEngineerUserId: 73,
    actorId: 18,
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
        'events' => [
            [
                'type' => 'assignment_order_prepare_rejected',
                'occurredAt' => '2026-08-27T10:15:30+03:00',
                'actorId' => 18,
                'payload' => [
                    'reasonCodes' => ['INSTALLER_REQUIRED'],
                    'installerCount' => 0,
                    'controlEngineerProvided' => true,
                ],
            ],
        ],
    ],
    $process->getInstallationObjectProcess(4512),
    'ORDER-PREPARE-001-E must append rejection audit without changing process state.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-001-E audit projection\n");
