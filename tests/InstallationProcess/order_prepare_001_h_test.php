<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/InMemoryInstallationProcessEnvironment.php';

use FMonitor2\InstallationProcess\InstallationProcess;
use FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment;

// Specification: ORDER-PREPARE-001-H.
$environment = new InMemoryInstallationProcessEnvironment();
$environment->allowSecurityAuditReadBy(7);
$environment->setNow('2026-08-27T10:30:00+03:00');

$process = new InstallationProcess($environment);
$commandResult = $process->prepareAssignmentOrder(
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
    $commandResult,
    'ORDER-PREPARE-001-H must not reveal participant validation to the denied actor.',
);

assertSameValue(
    [
        'accepted' => false,
        'violations' => [
            [
                'code' => 'FORBIDDEN',
                'message' => 'У вас нет права просматривать security-аудит.',
                'field' => null,
            ],
        ],
    ],
    $process->getSecurityAudit(installationObjectId: 4512, actorId: 91),
    'ORDER-PREPARE-001-H must not disclose security-audit content to an unauthorized actor.',
);

assertSameValue(
    [
        [
            'type' => 'assignment_order_prepare_rejected',
            'occurredAt' => '2026-08-27T10:30:00+03:00',
            'actorId' => 91,
            'payload' => [
                'reasonCodes' => ['FORBIDDEN'],
                'installerCount' => 0,
                'controlEngineerProvided' => false,
            ],
        ],
    ],
    $process->getSecurityAudit(installationObjectId: 4512, actorId: 7),
    'ORDER-PREPARE-001-H must expose the closed audit only through its authorized public seam.',
);

fwrite(STDOUT, "PASS ORDER-PREPARE-001-H security audit\n");
