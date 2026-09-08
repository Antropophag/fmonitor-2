<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final readonly class OpenConfirmedOriginalCommand
{
    public function __construct(public string $requestId,public int $objectId,public int $orderId,public string $originalRevisionId,public int $expectedApplicationSequence,public string $actualStartDate,public int $actorId) {}
}
