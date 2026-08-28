<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-001, example B.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);

$process = new InstallationProcess($environment);
$result = $process->prepareAssignmentOrder(
    installationObjectId: 4512,
    installerTabIds: [1042],
    controlEngineerUserId: null,
    actorId: 18,
);

assertSameValue(
    [
        'accepted' => false,
        'violations' => [
            [
                'code' => 'CONTROL_ENGINEER_REQUIRED',
                'message' => 'Выберите инженера строительного контроля.',
                'field' => 'controlEngineerUserId',
            ],
        ],
    ],
    $result,
    'ORDER-PREPARE-001 example B must reject preparation without a control engineer.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-001 example B\n");
