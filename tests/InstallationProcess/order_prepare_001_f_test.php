<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-001-F.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->seedInstallationObjectProcess(4512, [
    'installationObjectId' => 4512,
    'processState' => 'needs_assignment_order',
    'assignmentOrders' => [],
    'assignments' => [],
    'openTasks' => [
        ['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr'],
    ],
    'events' => [],
]);
$environment->setNow('2026-08-27T10:20:00+03:00');

$process = new InstallationProcess($environment);
$process->prepareAssignmentOrder(
    installationObjectId: 4512,
    installerTabIds: [1042],
    controlEngineerUserId: null,
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
                'occurredAt' => '2026-08-27T10:20:00+03:00',
                'actorId' => 18,
                'payload' => [
                    'reasonCodes' => ['CONTROL_ENGINEER_REQUIRED'],
                    'installerCount' => 1,
                    'controlEngineerProvided' => false,
                ],
            ],
        ],
    ],
    $process->getInstallationObjectProcess(4512),
    'ORDER-PREPARE-001-F must audit the missing control engineer without changing process state.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-001-F audit projection\n");
