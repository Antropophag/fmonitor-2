<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

// Test-only marker: never supplies a missing production interface or implementation.
if (interface_exists(O\AssignmentOrderOriginalAttemptAuditWriter::class)) {
    interface OriginalAttemptAuditMarker extends O\AssignmentOrderOriginalAttemptAuditWriter {}
} else { interface OriginalAttemptAuditMarker {} }

final class OriginalAttemptAuditWriter implements OriginalAttemptAuditMarker
{
    public array $calls = []; public array $rows = []; public string $outcome = 'committed';
    public function __construct(private OriginalIntegrityFixture $f) {}
    public function recordDenied(O\AssignmentOrderOriginalSafeAttemptAudit $a): O\AssignmentOrderOriginalAuditWriteStatus
    { return $this->write($a, true); }
    public function appendFailure(O\AssignmentOrderOriginalSafeAttemptAudit $a): O\AssignmentOrderOriginalAuditWriteStatus
    { return $this->write($a, false); }
    private function write(O\AssignmentOrderOriginalSafeAttemptAudit $a, bool $denied): O\AssignmentOrderOriginalAuditWriteStatus
    {
        $this->f->trace->add($denied ? 'audit.denied' : 'audit.failure'); $this->calls[] = [$denied, $a];
        if ($this->outcome === 'throw') throw new \RuntimeException('private synthetic audit detail');
        if ($this->outcome === 'committed') {
            $this->rows[] = $a;
            if ($denied && !isset($this->f->repository->terminal[$a->requestId])) {
                $result = OriginalIntegrityResult::rejected(O\AssignmentOrderOriginalReason::AUTHORIZATION_DENIED);
                $result->values['request'] = $a->requestId; $this->f->repository->terminal[$a->requestId] = $result;
            }
        }
        return O\AssignmentOrderOriginalAuditWriteStatus::from($this->outcome);
    }
}
final class OriginalAttemptAuditClock implements O\AssignmentOrderOriginalClock
{
    public int $calls = 0; public string|\Throwable $value = '2026-09-06T09:00:00Z';
    public function __construct(private OriginalIntegrityTrace $trace) {}
    public function nowUtc(): string { ++$this->calls; $this->trace->add('clock'); if ($this->value instanceof \Throwable) throw $this->value; return $this->value; }
}
final class OriginalAttemptAuditStreamFailure implements O\AssignmentOrderOriginalByteStream
{
    public int $readCalls = 0; public int $closeCalls = 0;
    public function __construct(private OriginalIntegrityTrace $trace) {}
    public function read(int $maximumBytes): O\AssignmentOrderOriginalStreamRead
    { ++$this->readCalls; $this->trace->add('stream.read'); return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::FAILED, ''); }
    public function close(): void { ++$this->closeCalls; $this->trace->add('stream.close'); }
}
final class OriginalAttemptAuditFixture
{
    public OriginalIntegrityFixture $f; public OriginalAttemptAuditWriter $writer; public OriginalAttemptAuditClock $clock;
    public O\AssignmentOrderOriginalApplication $application; public O\AssignmentOrderOriginalAuthorizationStatus $authorization;
    public O\SubmitAssignmentOrderOriginalCommand $command;
    public OriginalIntegrityStream|OriginalAttemptAuditStreamFailure $stream;
    public function __construct(array $options = [])
    {
        $this->f = new OriginalIntegrityFixture($options); $f = $this->f;
        $this->writer = new OriginalAttemptAuditWriter($f); $this->writer->outcome = $options['auditOutcome'] ?? 'committed';
        $this->clock = new OriginalAttemptAuditClock($f->trace); $this->clock->value = $options['clock'] ?? '2026-09-06T09:00:00Z';
        $this->authorization = $options['authorization'] ?? O\AssignmentOrderOriginalAuthorizationStatus::DENIED;
        $authorizer = new class($this) implements O\AssignmentOrderOriginalAuthorizer {
            public function __construct(private OriginalAttemptAuditFixture $h) {}
            public function authorize(int $actorUserId, string $exactCapability): O\AssignmentOrderOriginalAuthorizationStatus
            { ++$this->h->f->authorizationCalls; $this->h->f->trace->add('authorize'); return $this->h->authorization; }
        };
        $reader = new class($f) implements O\AssignmentOrderCompositionReader {
            public function __construct(private OriginalIntegrityFixture $f) {}
            public function find(int $installationCaseId, int $assignmentOrderId): O\AssignmentOrderCompositionSnapshot
            { ++$this->f->compositionCalls; $this->f->trace->add('composition'); return new O\AssignmentOrderCompositionSnapshot(O\AssignmentOrderCompositionLookupStatus::FOUND,4512,81,'composition-81-v1','388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',[7001,7002],31); }
        };
        $inspector = ($options['fileFailure'] ?? '') === 'storage' ? new class implements O\AssignmentOrderOriginalPdfInspector {
            public function algorithmId(): string { return 'fmonitor-passive-pdf-v1'; }
            public function inspect(string $completedBytes): O\AssignmentOrderOriginalPdfInspection { return O\AssignmentOrderOriginalPdfInspection::failed(); }
        } : new OriginalIntegrityInspector();
        // Before implementation PHP ignores the extra positional dependency. The public
        // named-parameter/declaration control separately prevents ignored-port false GREEN.
        $d = new O\AssignmentOrderOriginalDependencies($authorizer,$reader,$this->clock,$f->ids,$inspector,$f->storage,
            $f->repository,$f->observers,$f->observers,$f->observers,$f->observers,$f->observers,$f->fresh,$this->writer);
        $this->application = O\AssignmentOrderOriginalVerificationFactory::create($d);
        $this->stream = ($options['fileFailure'] ?? '') === 'stream' ? new OriginalAttemptAuditStreamFailure($f->trace) : $f->stream;
        $this->command = $this->withStream($this->stream);
    }
    public function withStream(O\AssignmentOrderOriginalByteStream $stream): O\SubmitAssignmentOrderOriginalCommand
    {
        $c=$this->f->command;
        return new O\SubmitAssignmentOrderOriginalCommand($c->requestId,$c->mode,$c->installationCaseId,$c->assignmentOrderId,
            $c->actorUserId,$c->documentDate,$c->compositionConfirmed,$c->rootOriginalId,$c->targetRevisionId,
            $c->expectedCurrentRevisionId,$c->correctionReason,new O\AssignmentOrderOriginalUpload($stream,'original.pdf','application/pdf'));
    }
    public function run(): O\AssignmentOrderOriginalResult { return $this->application->submitAssignmentOrderOriginal($this->command); }
    public function next(): void { $this->stream=new OriginalIntegrityStream(OriginalIntegrityFixture::pdf(),$this->f->trace);$this->command=$this->withStream($this->stream); }
}
