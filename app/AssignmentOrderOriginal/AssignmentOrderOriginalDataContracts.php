<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

interface AssignmentOrderOriginalCompleteLineageLookup extends AssignmentOrderOriginalLineageLookup, AssignmentOrderOriginalCurrentEvidenceLookup
{
    public function installationCaseId(): ?int;
    public function assignmentOrderId(): ?int;
    /** @return list<string> */
    public function revisionIds(): array;
}

interface AssignmentOrderOriginalRevisionLineageRepository
{
    public function findLineageForRevision(string $revisionId): AssignmentOrderOriginalLineageLookup;
}

enum AssignmentOrderOriginalFreshReaderOpenStatus: string
{
    case OPENED = 'opened';
    case UNAVAILABLE = 'unavailable';
}

enum AssignmentOrderOriginalFreshReaderCloseStatus: string
{
    case CLOSED = 'closed';
    case FAILED = 'failed';
}

interface AssignmentOrderOriginalFreshTerminalReaderFactory
{
    public function open(): AssignmentOrderOriginalFreshTerminalReaderOpenResult;
}

interface AssignmentOrderOriginalFreshTerminalReader
{
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup;
    public function close(): AssignmentOrderOriginalFreshReaderCloseStatus;
}

final readonly class AssignmentOrderOriginalFreshTerminalReaderOpenResult
{
    private function __construct(public AssignmentOrderOriginalFreshReaderOpenStatus $status, public ?AssignmentOrderOriginalFreshTerminalReader $reader) {}
    public static function opened(AssignmentOrderOriginalFreshTerminalReader $reader): self
    { return new self(AssignmentOrderOriginalFreshReaderOpenStatus::OPENED, $reader); }
    public static function unavailable(): self
    { return new self(AssignmentOrderOriginalFreshReaderOpenStatus::UNAVAILABLE, null); }
    private function __clone() {}
    public function __serialize(): array
    { throw new \LogicException('AssignmentOrderOriginalFreshReaderOpenResultNotSerializable'); }
    public function __unserialize(array $data): void
    { throw new \LogicException('AssignmentOrderOriginalFreshReaderOpenResultNotSerializable'); }
}

final readonly class AssignmentOrderOriginalFreshReaderConfig
{
    public function __construct(
        public string $databaseHost,
        public int $databasePort,
        public string $databaseName,
        public string $databaseUser,
        public string $databasePasswordFile,
        public string $tablePrefix,
    ) {}
}

/** @internal Explicit degraded recovery for source-compatible construction. */
final class AssignmentOrderOriginalUnavailableFreshReaders implements AssignmentOrderOriginalFreshTerminalReaderFactory
{
    public function open(): AssignmentOrderOriginalFreshTerminalReaderOpenResult
    { return AssignmentOrderOriginalFreshTerminalReaderOpenResult::unavailable(); }
}

enum AssignmentOrderOriginalPersistenceEvent: string
{
    case AFTER_COMPOSITION_ORDER_READ = 'after_composition_order_read';
    case BEFORE_READ_RELEASE = 'before_read_release';
    case BEFORE_WRITE_BEGIN = 'before_write_begin';
    case BEFORE_NATIVE_COMMIT = 'before_native_commit';
    case AFTER_NATIVE_COMMIT = 'after_native_commit';
    case BEFORE_WRITE_ROLLBACK = 'before_write_rollback';
    case BEFORE_FRESH_READER_CLOSE = 'before_fresh_reader_close';
}

interface AssignmentOrderOriginalPersistenceObserver
{
    public function observe(AssignmentOrderOriginalPersistenceEvent $event): void;
}
