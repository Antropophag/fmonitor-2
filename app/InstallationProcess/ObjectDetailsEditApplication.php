<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final readonly class ObjectDetailsEditApplication
{
 public function __construct(private MariaDbObjectDetailsEditStore$store,private ObjectDetailsFieldRegistry$registry,private mixed$clock){}
 public function edit(ObjectDetailsEditCommand$c):array
 {
  try{$patch=$this->registry->normalizePatch($c->patch);}catch(\InvalidArgumentException){return['status'=>'invalid','reasonCode'=>'invalid_patch'];}
  $fingerprint=hash('sha256',json_encode(['object_details.edit',$c->actorId,$c->objectId,$c->expectedRevision,$patch],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
  return$this->store->apply($c,$patch,$fingerprint,$this->registry,$this->clock);
 }
}
