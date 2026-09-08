<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizer;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalByteStream;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamRead;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamReadStatus;

final class AssignmentOrderOriginalMatrixAuthorizer implements AssignmentOrderOriginalAuthorizer
{
    /** @var list<array{int,string}> */
    public array $calls = [];

    public function __construct(private readonly AssignmentOrderOriginalAuthorizationStatus $outcome) {}

    public function authorize(int $actorUserId, string $exactCapability): AssignmentOrderOriginalAuthorizationStatus
    {
        $this->calls[] = [$actorUserId, $exactCapability];
        return $this->outcome;
    }
}

final class AssignmentOrderOriginalMatrixStream implements AssignmentOrderOriginalByteStream
{
    private int $offset = 0;
    public int $readCalls = 0;
    public int $closeCalls = 0;

    public function __construct(private readonly string $bytes) {}

    public function read(int $maximumBytes): AssignmentOrderOriginalStreamRead
    {
        ++$this->readCalls;
        if ($this->offset === strlen($this->bytes)) {
            return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF, '');
        }
        $chunk = substr($this->bytes, $this->offset, $maximumBytes);
        $this->offset += strlen($chunk);
        return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES, $chunk);
    }

    public function close(): void
    {
        ++$this->closeCalls;
    }
}
