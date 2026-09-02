<?php
declare(strict_types=1);
namespace FMonitor\IdentityAccess;
require_once __DIR__.'/PilotSessionStorageTypes.php';

final class PilotSessionStorageFactory
{
    public function create(PilotSessionStorageConfig $config,PilotSessionFilesystemPrimitives $filesystem,PilotSessionClock $clock,PilotSessionEntropy $entropy,PilotSessionLifecycleObserver $observer):PilotSessionStorage
    { return new FilesystemPilotSessionStorage($config,$filesystem,$clock,$entropy,$observer); }
}

