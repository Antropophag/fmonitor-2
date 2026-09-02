<?php
declare(strict_types=1);
$root=(string)getenv('FMONITOR_CONTROL_QUEUE_VERIFY_ARTIFACT_ROOT');$token=(string)getenv('FMONITOR_CONTROL_QUEUE_VERIFY_RUN_TOKEN');$child=$root.'/construction-control-queue-'.$token;$protocol=$child.'/protocol';mkdir($protocol,0700,true);mkdir($child.'/sessions',0700);
file_put_contents($child.'/fixture-ready',"ready\n",LOCK_EX);
$phase=$protocol.'/00-response-sensitivity';file_put_contents($phase.'.ready.json',json_encode(['worker_pids'=>[getmypid()]],JSON_THROW_ON_ERROR),LOCK_EX);
$deadline=microtime(true)+8;while(!is_file($phase.'.dispatch')&&microtime(true)<$deadline)usleep(20000);
file_put_contents($phase.'.response',"HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\nSTATIC RESPONSE",LOCK_EX);
$deadline=microtime(true)+8;while(!is_file($phase.'.teardown')&&microtime(true)<$deadline)usleep(20000);
