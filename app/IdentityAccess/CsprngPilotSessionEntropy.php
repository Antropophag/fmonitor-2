<?php
declare(strict_types=1);
namespace FMonitor\IdentityAccess;
require_once __DIR__.'/PilotSessionEntropy.php';
final class CsprngPilotSessionEntropy implements PilotSessionEntropy{public function bytes(int$length):PilotSessionEntropyResult{try{return $length>0?PilotSessionEntropyResult::ok(random_bytes($length)):PilotSessionEntropyResult::failed();}catch(\Throwable){return PilotSessionEntropyResult::failed();}}}
