<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/vendor/autoload.php';require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';
$c=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);$i=(int)$argv[2];
$db=new yii\db\Connection($c['connection']);
$owner=FMonitor2\YiiRuntime\InstallationProcessFactory::planning($db,$c['prefix'],static fn()=>new DateTimeImmutable('2026-09-10T09:30:00+03:00'));
file_put_contents($c['directory'].'/ready-'.$i,'ready');$deadline=microtime(true)+15;
while(!is_file($c['directory'].'/go')){if(microtime(true)>$deadline)throw new RuntimeException('fixture barrier timeout');usleep(10000);}
$result=$owner->scheduleInspection(...$c['operations'][$i]);
file_put_contents($c['directory'].'/result-'.$i.'.json',json_encode($result,JSON_THROW_ON_ERROR));
