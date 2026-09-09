<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/autoload.php';
use FMonitor2\Jobs\{MariaDbWorkforceScheduler};
[$database,$prefix,$worker,$arrival,$release,$token]=array_slice($argv,1);
$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$database,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
file_put_contents($arrival,"ready\n",LOCK_EX);$deadline=microtime(true)+5;
while(!is_file($release)){if(microtime(true)>=$deadline)exit(70);usleep(1000);}
try{$scheduler=new MariaDbWorkforceScheduler($db,$prefix);echo json_encode($scheduler->tick('2026-09-09T12:07:00.000000Z'),JSON_THROW_ON_ERROR),"\n";}finally{$db->close();}
