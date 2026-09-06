<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalLogIsolationTrace
{
    public array $calls = [];
    public function add(string $call): void { $this->calls[] = $call; }
}
final class OriginalLogIsolationStream implements O\AssignmentOrderOriginalByteStream
{
    private AssignmentOrderOriginalInitialStream $inner;
    public function __construct(string $bytes, private OriginalLogIsolationTrace $trace, private bool $fails)
    { $this->inner = new AssignmentOrderOriginalInitialStream($bytes); }
    public function read(int $maximumBytes): O\AssignmentOrderOriginalStreamRead { return $this->inner->read($maximumBytes); }
    public function close(): void { $this->trace->add('stream.close'); if ($this->fails) throw new \RuntimeException('stream close'); $this->inner->close(); }
}
final class OriginalLogIsolationLease implements O\AssignmentOrderOriginalPrivateContentLease
{
    public function __construct(private O\AssignmentOrderOriginalPrivateContent $value, private OriginalLogIsolationTrace $trace, private string $fault) {}
    public function status(): O\AssignmentOrderOriginalStorageStatus { return O\AssignmentOrderOriginalStorageStatus::OK; }
    public function content(): ?O\AssignmentOrderOriginalPrivateContent { return $this->value; }
    public function release(): O\AssignmentOrderOriginalStorageStatus
    {
        $this->trace->add('lease.release');
        if ($this->fault === 'release_throw') throw new \RuntimeException('release');
        return $this->fault === 'release_failed' ? O\AssignmentOrderOriginalStorageStatus::FAILED : O\AssignmentOrderOriginalStorageStatus::OK;
    }
}
final class OriginalLogIsolationStage implements O\AssignmentOrderOriginalPrivateStage
{
    private string $bytes = '';
    public function __construct(private OriginalLogIsolationTrace $trace, private string $fault) {}
    public function write(string $chunk): O\AssignmentOrderOriginalStorageStatus { $this->bytes .= $chunk; return O\AssignmentOrderOriginalStorageStatus::OK; }
    public function completedBytesForInspection(): string { return $this->bytes; }
    public function finalize(string $sha256, int $byteSize): O\AssignmentOrderOriginalStorageOutcome
    { return new AssignmentOrderOriginalInitialStorageOutcome(new OriginalLogIsolationLease(new AssignmentOrderOriginalInitialContent($sha256, $byteSize), $this->trace, $this->fault)); }
    public function abort(): O\AssignmentOrderOriginalStorageStatus
    {
        $this->trace->add('stage.abort');
        if ($this->fault === 'abort_throw') throw new \RuntimeException('abort');
        return in_array($this->fault, ['abort_failed','all_cleanup'], true) ? O\AssignmentOrderOriginalStorageStatus::FAILED : O\AssignmentOrderOriginalStorageStatus::OK;
    }
    public function close(): void
    { $this->trace->add('stage.close'); if (in_array($this->fault, ['stage_close','all_cleanup'], true)) throw new \RuntimeException('stage close'); }
}
final class OriginalLogIsolationStorage implements O\AssignmentOrderOriginalPrivateStorage
{
    public function __construct(private OriginalLogIsolationTrace $trace, public string $fault) {}
    public function beginStage(): O\AssignmentOrderOriginalPrivateStage { return new OriginalLogIsolationStage($this->trace, $this->fault); }
    public function listOrphans(string $cutoffUtc, int $limit, ?string $cursor): O\AssignmentOrderOriginalOrphanPage { throw new \LogicException('unexpected maintenance'); }
    public function acquireDigestLock(string $opaqueIdentity): O\AssignmentOrderOriginalDigestLock { throw new \LogicException('unexpected maintenance'); }
    public function deleteLocked(O\AssignmentOrderOriginalDigestLock $lock): O\AssignmentOrderOriginalStorageStatus { throw new \LogicException('unexpected maintenance'); }
    public function inventoryCanonicalJson(): string { return '{"stages":[],"finalized":[]}'; }
}
final class OriginalLogIsolationLogger implements O\AssignmentOrderOriginalRequestSafeLogObserver
{
    public bool $bindingFails = false;
    public bool $recordFails = true;
    public string $context = 'old-request';
    public array $bindings = [];
    public array $records = [];
    public string $bytes = '';
    public function __construct(private OriginalLogIsolationTrace $trace) {}
    public function useRequest(string $requestId): void
    {
        $this->bindings[] = $requestId;
        if ($this->bindingFails) throw new \RuntimeException('request binding');
        $this->context = $requestId;
    }
    public function record(string $event, array $safeFields): void
    {
        $this->trace->add('log:'.$safeFields['phase']);
        $this->records[] = [$event, $safeFields, $this->context];
        if ($this->recordFails) throw new \RuntimeException('diagnostic write');
        $this->bytes .= json_encode([$event, $safeFields, $this->context], JSON_THROW_ON_ERROR)."\n";
    }
}
final class OriginalLogIsolationRepository implements O\AssignmentOrderOriginalRepository, O\AssignmentOrderOriginalAssignmentLineageRepository
{
    public array $accepted = [];
    public array $attempts = [];
    private array $terminal = [];
    private bool $winnerCommitted = false;
    public function __construct(private OriginalLogIsolationTrace $trace, public bool $conflict = false, public bool $auditFails = false) {}
    public function findTerminalRequest(string $requestId): O\AssignmentOrderOriginalResultLookup
    { return isset($this->terminal[$requestId]) ? new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::FOUND, $this->terminal[$requestId]) : new AssignmentOrderOriginalInitialResultLookup(); }
    public function findAcceptedFingerprint(string $fingerprint): O\AssignmentOrderOriginalResultLookup
    { if ($fingerprint !== '') $this->trace->add('fingerprint.read'); return new AssignmentOrderOriginalInitialResultLookup(); }
    public function findLineage(string $rootOriginalId): O\AssignmentOrderOriginalLineageLookup { throw new \LogicException('initial must read assignment lineage'); }
    public function findLineageForAssignmentOrder(int $installationCaseId, int $assignmentOrderId): O\AssignmentOrderOriginalLineageLookup
    {
        $this->trace->add('lineage.read');
        if ($installationCaseId !== 4512 || $assignmentOrderId !== 81) throw new \LogicException('wrong lineage lookup');
        if (!$this->winnerCommitted) {if ($this->accepted!==[]) throw new \LogicException('Unexpected second initial fixture request');return new AssignmentOrderOriginalInitialLineageLookup();}
        return new O\AssignmentOrderOriginalMariaDbLineage(O\AssignmentOrderOriginalLookupStatus::FOUND, [
            'root_original_id'=>'original-0099', 'current_revision_id'=>'revision-0099', 'current_revision_number'=>1, 'installation_case_id'=>4512, 'assignment_order_id'=>81,
            'current_document_date'=>'2026-09-01', 'current_pdf_sha256'=>'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',
            'composition_identity'=>'composition-81-v1', 'composition_sha256'=>'388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',
        ], ['revision-0099']);
    }
    public function commitAccepted(O\AssignmentOrderOriginalAcceptedCommit $commit): O\AssignmentOrderOriginalCommitStatus
    {
        $this->trace->add('accepted.commit');
        if ($this->conflict) {$this->winnerCommitted=true;return O\AssignmentOrderOriginalCommitStatus::CONFLICT;}
        $this->accepted[] = $commit;
        $this->terminal[$commit->requestId] = new O\AssignmentOrderOriginalResultValue(O\AssignmentOrderOriginalStatus::ACCEPTED, null, false, $commit->requestId, $commit->rootOriginalId, $commit->newRevisionId, $commit->newRevisionNumber, $commit->documentDate, $commit->pdfSha256, $commit->byteSize, $commit->uploadedAt);
        return O\AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    public function commitAttempt(O\AssignmentOrderOriginalAttemptCommit $commit): O\AssignmentOrderOriginalCommitStatus
    {
        $this->trace->add('attempt.commit');
        if ($this->auditFails) return O\AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
        $this->attempts[] = $commit;
        $this->terminal[$commit->requestId] = new O\AssignmentOrderOriginalResultValue($commit->status, $commit->reason, $commit->retryable, $commit->requestId);
        return O\AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    public function hasCommittedContent(string $opaqueIdentity): O\AssignmentOrderOriginalReferenceLookup { throw new \LogicException('unexpected maintenance'); }
    public function evidenceCanonicalJson(int $caseId, int $orderId): string
    { return json_encode(['preExistingRoot'=>$this->winnerCommitted ? ['original-0099','revision-0099',1,'composition-81-v1'] : null, 'newAccepted'=>$this->accepted], JSON_THROW_ON_ERROR); }
}
