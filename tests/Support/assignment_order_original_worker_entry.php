<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationWorkerBootstrap;
$path=$argv[1]??'';exit(AssignmentOrderOriginalVerificationWorkerBootstrap::run($path,3,4,5,6));
