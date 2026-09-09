<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/autoload.php';

try { [$exit,$result]=FMonitor2\Jobs\JobsRuntimeCommand::execute(array_slice($argv,1)); }
catch (InvalidArgumentException) { $exit=64;$result=['ok'=>false,'error'=>'CONFIGURATION_INVALID']; }
catch (Throwable) { $exit=70;$result=['ok'=>false,'error'=>'JOBS_UNAVAILABLE']; }
echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),"\n";
exit($exit);
