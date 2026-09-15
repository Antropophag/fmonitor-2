<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class ControlEngineerAssignmentCommand
{
    public function __construct(public string $requestId, public int $objectId, public int $engineerUserId, public int $expectedRevision, public int $actorUserId)
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $requestId)
            || $objectId < 1 || $engineerUserId < 1 || $actorUserId < 1 || $expectedRevision < 0 || $expectedRevision > 2147483647) {
            throw new \InvalidArgumentException('Invalid assignment command.');
        }
    }
}
