<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

interface YiiChecklist
{
    public function access(int $actorId,int $objectId):array;
    public function projection(int $objectId):array;
    public function accept(int $objectId,int $actorId,array $operation,?string $bytes=null):array;
    public function queue(int $actorId,int $page=1,int $size=50):array;
    public function completion(int $objectId):array;
}
