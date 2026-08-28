<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-001, example A.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowPreparationBy(18);

$process = new InstallationProcess($environment);
$result = $process->prepareAssignmentOrder(
    installationObjectId: 4512,
    installerTabIds: [],
    controlEngineerUserId: 73,
    actorId: 18,
);

assertSameValue(
    [
        'accepted' => false,
        'violations' => [
            [
                'code' => 'INSTALLER_REQUIRED',
                'message' => 'Выберите хотя бы одного монтажника.',
                'field' => 'installerTabIds',
            ],
        ],
    ],
    $result,
    'ORDER-PREPARE-001 must reject preparation without an installer.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-001 example A\n");
