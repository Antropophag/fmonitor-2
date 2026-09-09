<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/autoload.php';

use FMonitor\IdentityAccess\CsprngPilotSessionEntropy;
use FMonitor\IdentityAccess\NativePilotSessionFilesystem;
use FMonitor\IdentityAccess\PilotSessionFilesystemEvent;
use FMonitor\IdentityAccess\PilotSessionFilesystemOperation;
use FMonitor\IdentityAccess\PilotSessionFilesystemPhase;
use FMonitor\IdentityAccess\PilotSessionLifecycleObserver;
use FMonitor\IdentityAccess\PilotSessionPrimitiveOutcome;
use FMonitor\IdentityAccess\PilotSessionStorageConfig;
use FMonitor\IdentityAccess\PilotSessionStorageFactory;
use FMonitor\IdentityAccess\SystemPilotSessionClock;

[$script,$root,$instance,$id,$entered] = $argv + [null,null,null,null,null];
if (!is_string($root) || !is_string($instance) || !is_string($id) || !is_string($entered)) exit(64);
$observer=new class($entered) implements PilotSessionLifecycleObserver {
    private bool $signalled=false;
    public function __construct(private string $path) {}
    public function observe(PilotSessionFilesystemEvent $event):void
    {
        if(!$this->signalled&&$event->operation()===PilotSessionFilesystemOperation::FLOCK&&$event->phase()===PilotSessionFilesystemPhase::AFTER&&$event->outcome()!==PilotSessionPrimitiveOutcome::OK){file_put_contents($this->path,"contended\n",LOCK_EX);$this->signalled=true;}
    }
};
$storage=(new PilotSessionStorageFactory())->create(new PilotSessionStorageConfig($root,$instance),new NativePilotSessionFilesystem(),new SystemPilotSessionClock(),new CsprngPilotSessionEntropy(),$observer);
$result=$storage->start($id);
echo json_encode(['status'=>$result->status()->name,'category'=>$result->category()?->name,'payload'=>$result->sessionPayload()],JSON_THROW_ON_ERROR),"\n";
$storage->close();
