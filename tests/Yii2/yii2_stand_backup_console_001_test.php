<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__,2);
assertSameValue(true,is_file($root.'/app/YiiRuntime/Commands/StandBackupController.php'),'INTENTIONAL_RED: Yii2 stand-backup controller exists');
assertSameValue(true,is_file($root.'/app/RuntimeRestore/StandBackupApplication.php'),'INTENTIONAL_RED: PHP backup owner exists');
function ysbcRun(array$args,string$root,array$env=[]):array{$command=['/usr/bin/env','-i','PATH='.(getenv('PATH')?:'/usr/bin:/bin'),...array_map(static fn($k,$v)=>$k.'='.$v,array_keys($env),$env),PHP_BINARY,$root.'/bin/yii',...$args];$pipes=[];$p=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);if(!is_resource($p))throw new TestFailure('SETUP_FAILURE process');$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return[proc_close($p),$out,$err];}
$invalid=[64,"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",''];
foreach([[],['stand-backup/create'],['stand-backup/create','--interactive=0'],['stand-backup/verify','--interactive=0'],['stand-backup/create','--manifest=x','--operation-id=x','--interactive=1']]as$args)assertSameValue($invalid,ysbcRun($args,$root),'closed argv '.implode(' ',$args));
$env=['FMONITOR_STAND_BACKUP_TEST_MODE'=>'1'];
$missing=ysbcRun(['stand-backup/create','--manifest=/missing','--operation-id=11111111-1111-4111-8111-111111111111','--interactive=0'],$root,$env);
assertSameValue([64,"{\"ok\":false,\"reason\":\"TARGET_INVALID\"}\n",''],$missing,'real Yii2 command reaches PHP exact-target owner');
foreach(['/private/manifest','11111111-1111-4111-8111-111111111111']as$secret)assertSameValue(false,str_contains($missing[1].$missing[2],$secret),'safe output');
echo "PASS: YII2-STAND-BACKUP-CONSOLE-001 transport\n";
