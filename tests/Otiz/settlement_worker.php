<?php
declare(strict_types=1);

require dirname(__DIR__,2).'/vendor/autoload.php';
require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';

use FMonitor2\Otiz\OtizSettlement;

if(count($argv)!==7||!in_array($argv[3],['complete','reverse'],true))exit(64);
[$script,$database,$prefix,$action,$actorRaw,$targetRaw,$operation]=$argv;
$actor=filter_var($actorRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$target=filter_var($targetRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if($actor===false||$target===false)exit(64);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=getenv('FMONITOR_TEST_DB_PORT')?:'23306';$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$db=new yii\db\Connection(['dsn'=>"mysql:host={$host};port={$port};dbname={$database}",'username'=>$user,'password'=>$password,'charset'=>'utf8mb4']);$db->open();
$ready=fgets(STDIN);if($ready!=="GO\n")exit(65);
$settlement=new OtizSettlement($db,$prefix,static fn():string=>'2026-10-03T12:00:00+03:00');
try{$result=$action==='complete'?$settlement->completeSnapshotPayments((int)$actor,(int)$target,$operation):$settlement->reverse((int)$actor,(int)$target,'Concurrent reversal',$operation);echo json_encode(['ok'=>true,'result'=>$result],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";}
catch(DomainException$e){echo json_encode(['ok'=>false,'reason'=>$e->getMessage()],JSON_THROW_ON_ERROR)."\n";}
catch(Throwable){echo json_encode(['ok'=>false,'reason'=>'INFRASTRUCTURE_FAILURE'],JSON_THROW_ON_ERROR)."\n";}
finally{$db->close();}
