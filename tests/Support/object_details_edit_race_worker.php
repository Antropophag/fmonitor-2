<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\InstallationProcess\{ObjectDetailsEditCommand,ProductionObjectDetailsEditFactory};
[$script,$database,$prefix,$floor,$ready,$release,$result]=array_pad($argv,7,'');
$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$database,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));$db->set_charset('utf8mb4');touch($ready);while(!is_file($release))usleep(1000);$owner=ProductionObjectDetailsEditFactory::create($db,$prefix,static fn():string=>'2026-09-21T15:43:00Z');$out=$owner->edit(new ObjectDetailsEditCommand(sprintf('22222222-2222-4222-8222-%012d',(int)$floor),4512,18,2,['floors'=>$floor]));file_put_contents($result,json_encode([$out['status'],$out['reasonCode']??null],JSON_THROW_ON_ERROR),LOCK_EX);$db->close();
