<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/autoload.php';
$out=FMonitor2\RuntimeRestore\RuntimeRecovery::run($argv,getenv());echo json_encode($out['result'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";exit($out['exitCode']);
