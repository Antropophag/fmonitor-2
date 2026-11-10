<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';
$c=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
$db=new yii\db\Connection(['dsn'=>$c['dsn'],'username'=>getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root','password'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local','charset'=>'utf8mb4']);
$owner=new FMonitor2\Otiz\OtizSettlementV2($db,$c['prefix'],static fn()=>'2026-10-29T19:00:00+03:00',static fn()=>'allow');
file_put_contents($c['ready'],'ready');$deadline=microtime(true)+10;while(!is_file($c['go'])){if(microtime(true)>$deadline)exit(3);usleep(10000);}
try{$r=$owner->accept($c['actor'],$c['id'],$c['revision'],$c['operation']);echo json_encode(['status'=>'accepted','id'=>$r['calculationId']]);}catch(DomainException$e){echo json_encode(['status'=>'rejected','reason'=>$e->getMessage()]);}finally{$db->close();}
