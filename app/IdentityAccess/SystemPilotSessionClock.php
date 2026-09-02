<?php
declare(strict_types=1);
namespace FMonitor\IdentityAccess;
require_once __DIR__.'/PilotSessionClock.php';
final class SystemPilotSessionClock implements PilotSessionClock{public function wallSeconds():int{return time();}public function monotonicNanoseconds():int{return hrtime(true);}}
