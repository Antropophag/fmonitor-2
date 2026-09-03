<?php
declare(strict_types=1);
require_once \dirname(__DIR__) . '/autoload.php';
require __DIR__.'/PilotHttp.php';
require_once __DIR__.'/PilotView.php';
require_once __DIR__.'/PilotShellView.php';
require_once __DIR__.'/ObjectListView.php';
require_once __DIR__.'/ConstructionControlView.php';
require_once __DIR__.'/ObjectCardView.php';
require_once __DIR__.'/ChecklistView.php';
require_once __DIR__.'/PrepareFormView.php';
require_once __DIR__.'/InstallerDirectoryView.php';
require_once __DIR__.'/UserDirectoryView.php';
require_once __DIR__.'/PilotE2ECoordinator.php';
require __DIR__.'/ProductionPilotHttpEntrypointFactory.php';
return \FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::create(new \FMonitor2\PilotHttp\ProcessEnvironmentSource());
