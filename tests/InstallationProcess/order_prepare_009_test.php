<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-009, example A.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->setNow('2026-08-28T17:00:00+03:00');
$initial = [
    'installationObjectId' => 4512,
    'processState' => 'needs_assignment_order',
    'assignmentOrders' => [],
    'assignments' => [],
    'openTasks' => [['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr']],
    'installationOpened' => false,
    'checklistAvailable' => false,
    'events' => [],
];
$environment->seedInstallationObjectProcess(4512, $initial);
$environment->seedInstallationObjectProcessRevision(4512, 7);
$environment->seedInstallationObjectSnapshot(4512, [
    'address' => 'Москва, ул. Примерная, д. 10',
    'entrance' => '2',
    'objectRegistrationNumber' => '77-000123',
    'plannedStartDate' => '2026-10-05',
    'plannedFinishDate' => '2026-12-20',
    'ptoActDate' => null,
]);
$environment->seedInstallerSnapshot(1042, [
    'tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик по лифтам',
    'status' => 'employed', 'employedFrom' => '2024-02-01', 'employedTo' => null,
    'source' => 'one_c_zup_via_bitrix', 'sourceUpdatedAt' => '2026-08-27T18:00:00+03:00',
]);
$environment->seedEngineerSnapshot(73, [
    'userId' => 73, 'fullName' => 'Петров Пётр Петрович', 'position' => 'Инженер строительного контроля',
    'active' => true, 'role' => 'construction_control_engineer',
]);
$environment->setRenderedArtifacts([
    ['type' => 'order', 'filename' => 'order.pdf', 'mediaType' => 'application/pdf', 'bytes' => 'order bytes'],
    ['type' => 'appendix', 'filename' => 'appendix.pdf', 'mediaType' => 'application/pdf', 'bytes' => 'appendix bytes'],
]);
$environment->setNextPreparationOperationId('prep-op-9f4c');
$environment->commitThenLoseAcknowledgement();

$process = new InstallationProcess($environment);
$result = $process->prepareAssignmentOrder(4512, [1042], 73, 18);

assertSameValue([
    'accepted' => true,
    'assignmentOrderVersion' => 1,
    'status' => 'prepared',
    'assignmentOrderDate' => '2026-08-28',
    'organizationType' => 'individual',
], $result, 'ORDER-PREPARE-009 must return the stored success after lost commit acknowledgement.');

assertSameValue([
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
            'tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик по лифтам',
            'status' => 'employed', 'employedFrom' => '2024-02-01', 'employedTo' => null,
            'source' => 'one_c_zup_via_bitrix', 'sourceUpdatedAt' => '2026-08-27T18:00:00+03:00',
        ]],
        'controlEngineer' => [
            'userId' => 73, 'fullName' => 'Петров Пётр Петрович', 'position' => 'Инженер строительного контроля',
            'active' => true, 'role' => 'construction_control_engineer',
        ],
        'artifacts' => [
            ['type' => 'order', 'filename' => 'order.pdf', 'mediaType' => 'application/pdf', 'size' => 11, 'sha256' => 'a4381ee59270f122baff4f0df43bd6787ea48787f19b8872e39508de985852f3'],
            ['type' => 'appendix', 'filename' => 'appendix.pdf', 'mediaType' => 'application/pdf', 'size' => 14, 'sha256' => '350edb2f89a0cc3e3f8424d000e2d370d737911418a1d6b9ef62d7b40ff9529c'],
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
        'occurredAt' => '2026-08-28T17:00:00+03:00',
        'actorId' => 18,
        'payload' => [
            'assignmentOrderVersion' => 1,
            'assignmentOrderDate' => '2026-08-28',
            'installerTabIds' => [1042],
            'controlEngineerUserId' => 73,
            'organizationType' => 'individual',
            'artifactSha256' => [
                'order' => 'a4381ee59270f122baff4f0df43bd6787ea48787f19b8872e39508de985852f3',
                'appendix' => '350edb2f89a0cc3e3f8424d000e2d370d737911418a1d6b9ef62d7b40ff9529c',
            ],
        ],
    ]],
], $process->getInstallationObjectProcess(4512), 'ORDER-PREPARE-009 must expose exactly one complete success without technical operation id.');
assertSameValue(1, $environment->getPreparationOperationIdGenerationCount(), 'Operation id must be generated once.');
assertSameValue(1, $environment->getRenderCallCount(), 'Renderer must run once.');
assertSameValue(1, $environment->getProcessReplacementCallCount(), 'Atomic persistence must run once.');
assertSameValue(1, $environment->getPreparationReconciliationCallCount(), 'Reconciliation must run once.');

fwrite(STDOUT, "PASS ORDER-PREPARE-009 unknown commit recovered\n");
