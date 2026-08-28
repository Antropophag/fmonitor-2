<?php
declare(strict_types=1);
require __DIR__.'/PilotHttp.php';
return \FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory::create(new \FMonitor2\PilotHttp\ProcessEnvironmentSource());
