<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationWorkerBootstrap;
$path = $argv[1] ?? '';
$fds = array_slice($argv, 2);
if ($fds === []) $fds = ['3', '4', '5', '6'];
// This verifier entry only transports PHP integers. Validation of range,
// stdio, openness, socket type and identity belongs to the reviewed worker
// bootstrap itself rather than being duplicated in test support.
exit(AssignmentOrderOriginalVerificationWorkerBootstrap::run($path, ...(array_map('intval', $fds))));
