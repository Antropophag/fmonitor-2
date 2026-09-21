<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class ObjectDetailsEditCommand
{
    public function __construct(public string $requestId,public int $objectId,public int $actorId,public int $expectedRevision,public array $patch)
    {
        if(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$requestId)!==1||$objectId<1||$actorId<1||$expectedRevision<0||$patch===[])throw new \InvalidArgumentException('Invalid object details command.');
    }
}
