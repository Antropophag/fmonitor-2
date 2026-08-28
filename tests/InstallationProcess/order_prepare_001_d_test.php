<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-001, example D.
$environment = new InMemoryInstallationProcessEnvironment();
$process = new InstallationProcess($environment);

$result = $process->prepareAssignmentOrder(
    installationObjectId: 4512,
    installerTabIds: [],
    controlEngineerUserId: null,
    actorId: 91,
);

assertSameValue(
    [
        'accepted' => false,
        'violations' => [
            [
                'code' => 'FORBIDDEN',
                'message' => 'У вас нет права формировать распоряжение.',
                'field' => null,
            ],
        ],
    ],
    $result,
    'ORDER-PREPARE-001 example D must reject before revealing participant violations.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-001 example D\n");
