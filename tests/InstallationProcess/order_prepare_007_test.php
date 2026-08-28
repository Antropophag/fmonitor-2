<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-007, example A.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->setNow('2026-08-28T15:20:00+03:00');
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
$environment->seedInstallationObjectProcessRevision(4512, 3);
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
    'sourceUpdatedAt' => '2026-08-27T18:00:00+03:00',
]);
$environment->seedEngineerSnapshot(73, [
    'userId' => 73,
    'fullName' => 'Петров Пётр Петрович',
    'position' => 'Инженер строительного контроля',
    'active' => true,
    'role' => 'construction_control_engineer',
]);
$environment->failRendering();
$environment->forbidProcessReplacement();

$process = new InstallationProcess($environment);
$result = $process->prepareAssignmentOrder(4512, [1042], 73, 18);

assertSameValue(
    [
        'accepted' => false,
        'violations' => [[
            'code' => 'ASSIGNMENT_ORDER_RENDER_FAILED',
            'message' => 'Не удалось сформировать документы распоряжения. Повторите действие позже.',
            'field' => null,
        ]],
    ],
    $result,
    'ORDER-PREPARE-007 must return a stable non-disclosing renderer failure.',
);

assertSameValue(
    [
        'installationObjectId' => 4512,
        'processState' => 'needs_assignment_order',
        'assignmentOrders' => [],
        'assignments' => [],
        'openTasks' => [['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr']],
        'installationOpened' => false,
        'checklistAvailable' => false,
        'events' => [[
            'type' => 'assignment_order_prepare_rejected',
            'occurredAt' => '2026-08-28T15:20:00+03:00',
            'actorId' => 18,
            'payload' => [
                'reasonCodes' => ['ASSIGNMENT_ORDER_RENDER_FAILED'],
                'installerCount' => 1,
                'controlEngineerProvided' => true,
            ],
        ]],
    ],
    $process->getInstallationObjectProcess(4512),
    'ORDER-PREPARE-007 must append only safe audit after renderer failure.',
);

assertSameValue(
    1,
    $environment->getRenderCallCount(),
    'ORDER-PREPARE-007 must not retry renderer inside the failed command.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-007 renderer failure\n");
