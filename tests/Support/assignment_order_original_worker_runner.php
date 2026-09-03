<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

if ($argc !== 2 || !class_exists(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationWorkerBootstrap::class)) {
    fwrite(STDERR, "WORKER_BOOTSTRAP_UNAVAILABLE\n");
    exit(70);
}

exit(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationWorkerBootstrap::run($argv[1], 3, 4, 5, 6));
