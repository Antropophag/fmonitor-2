<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/autoload.php';
use FMonitor2\Jobs\{JobHandlerClaim,JobHandlerRuntime,JobsRuntimeConfiguration};
try{$input=stream_get_contents(STDIN,65536);if(!is_string($input)||strlen($input)>65535)throw new InvalidArgumentException();$job=JobHandlerClaim::decode($input);$config=JobsRuntimeConfiguration::fromEnvironment();$result=JobHandlerRuntime::handle($job,$config);echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),"\n";$exit=0;}
catch(InvalidArgumentException|JsonException){echo "{\"ok\":false,\"error\":\"CONFIGURATION_INVALID\"}\n";$exit=64;}
catch(Throwable){echo "{\"status\":\"retryable\",\"failureCode\":\"JOB_HANDLER_FAILED\"}\n";$exit=0;}
exit($exit);
