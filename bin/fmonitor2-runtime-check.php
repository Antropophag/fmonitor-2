<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

FMonitor2\Runtime\RuntimeCommand::run(static function(FMonitor2\Runtime\RuntimeConfiguration $config):void{
    FMonitor2\Runtime\RuntimeStartupAttestation::invalidate($config);
    FMonitor2\Runtime\RuntimeStorage::assertReady($config);
    $marker=FMonitor2\Runtime\MariaDbRuntimeReadiness::assertDeepReady($config);
    FMonitor2\Runtime\RuntimeStartupAttestation::publish($config,$marker);
});
