<?php
declare(strict_types=1);
namespace FMonitor\IdentityAccess;
require_once __DIR__.'/PilotSessionLifecycleObserver.php';
final class NoOpPilotSessionLifecycleObserver implements PilotSessionLifecycleObserver{public function observe(PilotSessionFilesystemEvent$event):void{}}
