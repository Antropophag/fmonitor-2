<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-010, example A.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);
$environment->setNow('2026-08-28T17:30:00+03:00');
$initial = [
    'installationObjectId' => 4512, 'processState' => 'needs_assignment_order',
    'assignmentOrders' => [], 'assignments' => [],
    'openTasks' => [['type' => 'prepare_assignment_order', 'assigneeRole' => 'fkr']],
    'installationOpened' => false, 'checklistAvailable' => false, 'events' => [],
];
$environment->seedInstallationObjectProcess(4512, $initial);
$environment->seedInstallationObjectProcessRevision(4512, 7);
$environment->seedInstallationObjectSnapshot(4512, [
    'address' => 'Москва, ул. Примерная, д. 10', 'entrance' => '2',
    'objectRegistrationNumber' => '77-000123', 'plannedStartDate' => '2026-10-05',
    'plannedFinishDate' => '2026-12-20', 'ptoActDate' => null,
]);
$environment->seedInstallerSnapshot(1042, [
    'tabId' => 1042, 'fullName' => 'Иванов Иван Иванович', 'position' => 'Электромеханик по лифтам',
    'status' => 'employed', 'employedFrom' => '2024-02-01', 'employedTo' => null,
]);
$environment->seedEngineerSnapshot(73, [
    'userId' => 73, 'fullName' => 'Петров Пётр Петрович', 'active' => true,
    'role' => 'construction_control_engineer',
]);
$environment->setRenderedArtifacts([
    ['type' => 'order', 'filename' => 'order.pdf', 'mediaType' => 'application/pdf', 'bytes' => 'order'],
    ['type' => 'appendix', 'filename' => 'appendix.pdf', 'mediaType' => 'application/pdf', 'bytes' => 'appendix'],
]);
$environment->setNextPreparationOperationId('prep-op-a71d');
$environment->loseAcknowledgementWithoutResult();

$process = new InstallationProcess($environment);
$result = $process->prepareAssignmentOrder(4512, [1042], 73, 18);

assertSameValue([
    'accepted' => false,
    'violations' => [[
        'code' => 'ASSIGNMENT_ORDER_RESULT_UNKNOWN',
        'message' => 'Не удалось подтвердить результат формирования распоряжения. Обновите данные объекта монтажа перед дальнейшими действиями.',
        'field' => null,
    ]],
], $result, 'ORDER-PREPARE-010 must return the stable indeterminate result.');
assertSameValue($initial, $process->getInstallationObjectProcess(4512), 'Unknown result must not write process facts or rejection audit.');
assertSameValue(1, $environment->getPreparationOperationIdGenerationCount(), 'Operation id must be generated once.');
assertSameValue(1, $environment->getRenderCallCount(), 'Renderer must run once.');
assertSameValue(1, $environment->getProcessReplacementCallCount(), 'Atomic persistence must run once.');
assertSameValue(1, $environment->getPreparationReconciliationCallCount(), 'Reconciliation must run once.');
assertSameValue('prep-op-a71d', $environment->getLastPersistedPreparationOperationId(), 'Persistence must receive the generated operation id.');
assertSameValue('prep-op-a71d', $environment->getLastReconciledPreparationOperationId(), 'Reconciliation must use the same operation id.');

fwrite(STDOUT, "PASS ORDER-PREPARE-010 unresolved commit outcome\n");
