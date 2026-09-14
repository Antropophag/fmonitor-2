<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
use FMonitor2\RuntimeRestore\RecordingStandRestoreDriver;
use FMonitor2\RuntimeRestore\StandRestoreApplication;
if(!class_exists(RecordingStandRestoreDriver::class)){echo "{\"ok\":false,\"reason\":\"IMPLEMENTATION_MISSING\"}\n";exit(78);}
$driver=new RecordingStandRestoreDriver($argv[6]??'', $argv[7]??'');
$out=(new StandRestoreApplication($driver))->run($argv[1]??'', $argv[2]??'', $argv[3]??'', null, $argv[4]??'');
echo json_encode($out['result'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";exit($out['exitCode']);
