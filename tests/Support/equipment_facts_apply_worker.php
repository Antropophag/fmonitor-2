<?php
declare(strict_types=1);require __DIR__.'/EquipmentFactsFixture.php';
use FMonitor2\InstallationProcess\EquipmentFactsApplication;
[$script,$dbName,$prefix,$runId,$release]=$argv;$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$pass=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';while(!is_file($release))usleep(1000);$db=new mysqli($host,$user,$pass,$dbName,$port);$owner=new EquipmentFactsApplication($db,$prefix,EquipmentFactsFixture::HMAC_KEY);$result=$owner->execute(equipmentCommand($runId,'2026-09-15T17:00:00.000000Z',[equipmentRecord('RACE','2026-09-10',null,null)]));echo json_encode($result,JSON_THROW_ON_ERROR);$db->close();
