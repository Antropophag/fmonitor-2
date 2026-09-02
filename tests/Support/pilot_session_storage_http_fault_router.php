<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
spl_autoload_register(static function(string$class)use($root):void{foreach(['FMonitor2\\'=>$root.'/app/','FMonitor\\IdentityAccess\\'=>$root.'/app/IdentityAccess/']as$p=>$base)if(str_starts_with($class,$p)){$f=$base.str_replace('\\','/',substr($class,strlen($p))).'.php';if(is_file($f))require_once$f;return;}});
foreach(['PilotHttp','PilotView','PilotShellView','ObjectListView','ConstructionControlView','ObjectCardView','ChecklistView','PrepareFormView','InstallerDirectoryView','UserDirectoryView','PilotE2ECoordinator','ProductionPilotHttpEntrypointFactory']as$f)require_once"$root/app/PilotHttp/$f.php";
require_once __DIR__.'/PilotSessionStoragePublicApiFixture.php';
$entry=FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies(new FMonitor2\PilotHttp\ProcessEnvironmentSource(),new FMonitor2\Tests\Support\NativePilotSessionFilesystem('fsyncFile','stage',1,'false'),new FMonitor2\Tests\Support\FixedPilotSessionClock(1_788_200_000,1_000),new FMonitor2\Tests\Support\FixedPilotSessionEntropy([str_repeat("\x11",32),str_repeat("\x22",16),str_repeat("\x33",12)]),new FMonitor2\Tests\Support\RecordingPilotSessionObserver());
$response=$entry->handle($_SERVER);http_response_code($response->status);header_remove('Server');header_remove('X-Powered-By');foreach($response->headers as$n=>$v)header("$n: $v");if(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')echo$response->body;
