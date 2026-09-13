<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';$root=dirname(__DIR__,2);
if(!is_file($root.'/app/YiiRuntime/Commands/StandRestoreController.php'))echo "INTENDED_RED: Yii2 stand-restore controller missing\n";
assertSameValue(true,is_file($root.'/app/YiiRuntime/Commands/StandRestoreController.php'),'Yii2 stand-restore controller exists');
function runRestore(array $args,string $root):array{$p=proc_open([PHP_BINARY,$root.'/bin/yii',...$args],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);if(!is_resource($p))throw new TestFailure('SETUP_FAILURE');$o=stream_get_contents($pipes[1]);$e=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return[proc_close($p),$o,$e];}
$bad=[64,"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",''];foreach([[],['stand-restore/run'],['stand-restore/run','--interactive=0'],['stand-restore/run','--manifest=/x','--manifest=/y','--bundle-digest='.str_repeat('a',64),'--operation-id=11111111-1111-4111-8111-111111111111','--interactive=0'],['stand-restore/run','--manifest=/x','--bundle-digest='.str_repeat('a',64),'--operation-id=bad','--interactive=1']]as$args)assertSameValue($bad,runRestore($args,$root),'closed exact argv');
echo "PASS: YII2-STAND-RESTORE-CONTROL-001 transport\n";
