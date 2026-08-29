<?php
declare(strict_types=1);
\spl_autoload_register(static function(string $class):void{$prefix='FMonitor2\\';if(\str_starts_with($class,$prefix)){$path=\dirname(__DIR__).'/'.\str_replace('\\','/',\substr($class,\strlen($prefix))).'.php';if(\is_file($path))require_once $path;}});
require __DIR__.'/PilotHttp.php';
require_once __DIR__.'/PilotView.php';
require_once __DIR__.'/PilotShellView.php';
require_once __DIR__.'/ObjectListView.php';
require_once __DIR__.'/ObjectCardView.php';
require_once __DIR__.'/ChecklistView.php';
require_once __DIR__.'/PrepareFormView.php';
require_once __DIR__.'/InstallerDirectoryView.php';
require_once __DIR__.'/UserDirectoryView.php';
require_once __DIR__.'/PilotE2ECoordinator.php';
require __DIR__.'/ProductionPilotHttpEntrypointFactory.php';
return \FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::create(new \FMonitor2\PilotHttp\ProcessEnvironmentSource());
