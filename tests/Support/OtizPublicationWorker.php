<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/autoload.php';
use FMonitor2\Otiz\SnapshotPublication;
use FMonitor2\Otiz\MariaDbSnapshotStore;
$name=(string)getenv('OTIZ_TEST_DATABASE');
if(!preg_match('/^otiz_publication_[a-f0-9]{12}$/D',$name))throw new RuntimeException('Not owned test DB');
$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$name,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
$rows=json_decode((string)getenv('OTIZ_TEST_INPUTS'),true,flags:JSON_THROW_ON_ERROR);
$app=new SnapshotPublication(new MariaDbSnapshotStore($db,'op_'),static function(string$date)use($rows):array{usleep(200000);return$rows;},static fn():string=>'2026-09-08T15:00:00+03:00');
try{
    if($argv[1]==='build')echo $app->buildAndPublish(1,'2026-09-08',$argv[2]);
    else{$app->accept(1,(int)$argv[2]);echo 'OK';}
}catch(DomainException $e){echo $e->getMessage();}
