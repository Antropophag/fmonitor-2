<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Per-invocation acquisition and cleanup owner. */
final class AssignmentOrderOriginalResourceScope
{
    private ?AssignmentOrderOriginalPrivateStage $stage = null;
    private bool $abortAttempted = false;
    private bool $stageCloseAttempted = false;
    private bool $streamCloseAttempted = false;
    private bool $finalizeAttempted = false;
    private bool $candidateReady = false;
    private AssignmentOrderOriginalResourceSignals $signals;
    private AssignmentOrderOriginalLeaseResources $leases;

    public function __construct(private readonly AssignmentOrderOriginalDependencies $dependencies, private readonly AssignmentOrderOriginalByteStream $stream)
    {
        $this->signals = new AssignmentOrderOriginalResourceSignals($dependencies);
        $this->leases = new AssignmentOrderOriginalLeaseResources($this->signals);
    }

    public function begin(): void
    {
        $this->signals->storage(AssignmentOrderOriginalStorageEvent::STAGE_BEGIN);
        $this->stage = $this->storageCall(fn() => $this->dependencies->storage->beginStage());
    }

    public function read(): AssignmentOrderOriginalStreamRead
    {
        try { $read = $this->stream->read(65536); }
        catch (\Throwable) { throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STREAM_FAILURE); }
        if (($read->status === AssignmentOrderOriginalStreamReadStatus::BYTES && $read->bytes !== '' && strlen($read->bytes) <= 65536)
            || ($read->status === AssignmentOrderOriginalStreamReadStatus::EOF && $read->bytes === '')) return $read;
        throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STREAM_FAILURE);
    }

    public function write(string $chunk): void
    {
        if ($this->storageCall(fn() => $this->stage->write($chunk)) !== AssignmentOrderOriginalStorageStatus::OK)
            throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE);
        $this->signals->storage(AssignmentOrderOriginalStorageEvent::STAGE_WRITE);
    }

    public function done(): void { $this->signals->storage(AssignmentOrderOriginalStorageEvent::STAGE_DONE); }

    public function completed(): string
    {
        return $this->storageCall(fn() => $this->stage->completedBytesForInspection());
    }

    public function finalize(string $sha256, int $byteSize): string
    {
        if ($this->finalizeAttempted) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE);
        $this->finalizeAttempted = true;
        $this->signals->storage(AssignmentOrderOriginalStorageEvent::FINALIZE_BEGIN);
        $outcome = $this->storageCall(fn() => $this->stage->finalize($sha256, $byteSize));
        $identity = $this->leases->capture($outcome, $sha256, $byteSize);
        $this->signals->storage(AssignmentOrderOriginalStorageEvent::FINALIZE_DONE, $identity);
        return $identity;
    }

    public function closeCandidate(): void
    {
        $this->closeStage(true);
        $this->closeStream(true);
        $this->candidateReady = true;
    }

    public function cleanup(): void
    {
        if (!$this->candidateReady) $this->abort();
        $this->closeStage(false);
        $this->closeStream(false);
        $this->leases->release('rolled_back');
    }

    public function release(string $phase): void { $this->leases->release($phase); }
    public function lifecycle(AssignmentOrderOriginalLifecycleEvent $event): void { $this->signals->lifecycle($event); }
    public function deliver(AssignmentOrderOriginalResult $result): void { $this->signals->deliver($result); }

    private function storageCall(callable $operation): mixed
    {
        try { return $operation(); }
        catch (\Throwable) { throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE); }
    }

    private function abort(): void
    {
        if ($this->stage === null || $this->abortAttempted) return;
        $this->abortAttempted = true;
        $this->signals->storage(AssignmentOrderOriginalStorageEvent::ABORT_BEGIN, cleanup: true);
        try { $aborted = $this->stage->abort() === AssignmentOrderOriginalStorageStatus::OK; }
        catch (\Throwable) { $aborted = false; }
        if ($aborted) $this->signals->storage(AssignmentOrderOriginalStorageEvent::ABORT_DONE, cleanup: true);
        else $this->signals->diagnostic('ASSIGNMENT_ORDER_ORIGINAL_STAGE_ABORT_FAILED', 'stage_abort');
    }

    private function closeStage(bool $candidate): void
    {
        if ($this->stage === null || $this->stageCloseAttempted) return;
        $this->stageCloseAttempted = true;
        $observed = $this->signals->storage(AssignmentOrderOriginalStorageEvent::STAGE_CLOSE, cleanup: true);
        try { $this->stage->close(); $closed = true; }
        catch (\Throwable) { $closed = false; }
        if (!$closed) $this->signals->diagnostic('ASSIGNMENT_ORDER_ORIGINAL_STAGE_CLOSE_FAILED', 'stage_close');
        if ($candidate && (!$closed || !$observed)) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE);
    }

    private function closeStream(bool $candidate): void
    {
        if ($this->streamCloseAttempted) return;
        $this->streamCloseAttempted = true;
        try { $this->stream->close(); $closed = true; }
        catch (\Throwable) { $closed = false; }
        if (!$closed) $this->signals->diagnostic('ASSIGNMENT_ORDER_ORIGINAL_STREAM_CLOSE_FAILED', 'stream_close');
        if ($candidate && !$closed) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STREAM_FAILURE);
    }
}
