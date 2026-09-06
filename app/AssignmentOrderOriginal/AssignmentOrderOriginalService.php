<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';
require_once __DIR__.'/AssignmentOrderOriginalSubmissionFailure.php';
require_once __DIR__.'/AssignmentOrderOriginalResponseDeliveryLost.php';
require_once __DIR__.'/AssignmentOrderOriginalResourceSignals.php';
require_once __DIR__.'/AssignmentOrderOriginalLeaseResources.php';
require_once __DIR__.'/AssignmentOrderOriginalResourceScope.php';
require_once __DIR__.'/AssignmentOrderOriginalPdfAcquisition.php';
require_once __DIR__.'/AssignmentOrderOriginalResultSnapshot.php';
require_once __DIR__.'/AssignmentOrderOriginalCommitProtocol.php';

final class AssignmentOrderOriginalService implements AssignmentOrderOriginalApplication
{
    private readonly AssignmentOrderOriginalDependencies $dependencies;
    public function __construct(AssignmentOrderOriginalDependencies $d) { $this->dependencies = $d; }

    public function submitAssignmentOrderOriginal(SubmitAssignmentOrderOriginalCommand $c): AssignmentOrderOriginalResult
    {
        $d = $this->dependencies;
        if ($d->safeLog instanceof AssignmentOrderOriginalRequestSafeLogObserver) $d->safeLog->useRequest($c->requestId);
        $resources = new AssignmentOrderOriginalResourceScope($d, $c->upload->stream);
        $at = '1970-01-01T00:00:00Z';
        try {
            if (!AssignmentOrderOriginalCommandShape::valid($c)) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::INVALID_COMMAND);
            $capability = $c->mode === AssignmentOrderOriginalMode::INITIAL ? 'assignment_order.original.upload' : 'assignment_order.original.correct';
            $authorization = $this->persistenceCall(fn() => $d->authorizer->authorize($c->actorUserId, $capability));
            if ($authorization === AssignmentOrderOriginalAuthorizationStatus::UNAVAILABLE) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
            if ($authorization !== AssignmentOrderOriginalAuthorizationStatus::ALLOWED) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::AUTHORIZATION_DENIED);
            $prior = $this->lookup(fn() => $d->repository->findTerminalRequest($c->requestId));
            if ($prior[0] === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
            if ($prior[0] === AssignmentOrderOriginalLookupStatus::FOUND && ($value = $prior[1]) !== null) {
                $result = $this->persistenceCall(fn() => AssignmentOrderOriginalResultSnapshot::copy($value, $c->requestId, replayAccepted: true));
                $resources->cleanup();
                return $result;
            }
            $composition = $this->persistenceCall(fn() => $d->compositions->find($c->installationCaseId, $c->assignmentOrderId));
            if ($composition->status === AssignmentOrderCompositionLookupStatus::NOT_FOUND) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::ORDER_NOT_FOUND);
            if ($composition->status === AssignmentOrderCompositionLookupStatus::UNAVAILABLE) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
            if (!$this->persistenceCall(fn() => $this->validComposition($composition))) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::INVALID_COMPOSITION);
            $instant = AssignmentOrderOriginalPortValues::nowUtc($d->clock);
            if ($instant === null) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
            $at = $instant;
            if (!$c->compositionConfirmed) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::COMPOSITION_NOT_CONFIRMED);
            $today = (new \DateTimeImmutable($at))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d');
            if ($c->documentDate > $today) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::FUTURE_DOCUMENT_DATE);
            $line = $c->mode === AssignmentOrderOriginalMode::CORRECTION ? $this->initialLineage($c, $composition) : null;
            $resources->lifecycle(AssignmentOrderOriginalLifecycleEvent::AFTER_REQUEST_MISS_BEFORE_STREAM);
            [$sha, $size] = AssignmentOrderOriginalPdfAcquisition::inspect($resources, $d->pdfInspector, $c->upload->declaredMediaType);
            $fingerprint = $this->fingerprint($c, $composition, $sha);
            $same = $this->lookup(fn() => $d->repository->findAcceptedFingerprint($fingerprint));
            if ($same[0] === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
            if ($same[0] === AssignmentOrderOriginalLookupStatus::FOUND && ($value = $same[1]) !== null) {
                $result = $this->persistenceCall(fn() => AssignmentOrderOriginalResultSnapshot::copy($value, $c->requestId, forceReplay: true));
                $resources->cleanup();
                return $result;
            }
            $resources->lifecycle(AssignmentOrderOriginalLifecycleEvent::AFTER_FINGERPRINT_MISS_BEFORE_CAS);
            $root = $c->rootOriginalId;
            if ($c->mode === AssignmentOrderOriginalMode::INITIAL) $root = AssignmentOrderOriginalPortValues::nextId($d->ids, true);
            if ($root === null) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
            $revision = AssignmentOrderOriginalPortValues::nextId($d->ids, false);
            if ($revision === null) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
            $number = $line === null ? 1 : (int) $this->persistenceCall(fn() => $line->currentRevisionNumber()) + 1;
            $identity = $resources->finalize($sha, $size);
            $resources->closeCandidate();
            $resources->lifecycle(AssignmentOrderOriginalLifecycleEvent::AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT);
            $result = (new AssignmentOrderOriginalCommitProtocol($d->repository))->execute($c, $composition, $root, $revision, $number, $at, $sha, $size, $identity, $fingerprint, $resources);
            if (in_array($result->status(), [AssignmentOrderOriginalStatus::REJECTED, AssignmentOrderOriginalStatus::CONFLICT], true) && $result->reasonCode() !== null)
                return $this->finish($c, $result->status(), $result->reasonCode(), $result->retryable(), $at, $resources);
            return $result;
        } catch (AssignmentOrderOriginalResponseDeliveryLost $lost) {
            throw $lost;
        } catch (AssignmentOrderOriginalSubmissionFailure $failure) {
            return $this->finish($c, $failure->status, $failure->reason, $failure->retryable, $at, $resources);
        } catch (\Throwable) {
            return $this->finish($c, AssignmentOrderOriginalStatus::FAILED, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE, true, $at, $resources);
        }
    }

    private function finish(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderOriginalStatus $status, AssignmentOrderOriginalReason $reason, bool $retry, string $at, AssignmentOrderOriginalResourceScope $resources): AssignmentOrderOriginalResult
    {
        $resources->cleanup();
        if (!$retry && in_array($status, [AssignmentOrderOriginalStatus::REJECTED, AssignmentOrderOriginalStatus::CONFLICT], true) && $at !== '1970-01-01T00:00:00Z') {
            try { $audit = $this->dependencies->repository->commitAttempt(new AssignmentOrderOriginalAttemptCommit($c->requestId, $c->actorUserId, $c->mode, $c->installationCaseId, $c->assignmentOrderId, $status, $reason, false, $at)); }
            catch (\Throwable) { $audit = AssignmentOrderOriginalCommitStatus::ROLLED_BACK; }
            if ($audit !== AssignmentOrderOriginalCommitStatus::COMMITTED) return new AssignmentOrderOriginalResultValue(AssignmentOrderOriginalStatus::FAILED, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE, true, $c->requestId);
        }
        return new AssignmentOrderOriginalResultValue($status, $reason, $retry, $c->requestId);
    }

    private function initialLineage(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition): AssignmentOrderOriginalLineageLookup
    {
        [$line, $status, $matches] = $this->persistenceCall(function () use ($c, $composition): array {
            $line = $this->dependencies->repository->findLineage((string) $c->rootOriginalId);
            $status = $line->status();
            return [$line, $status, $status === AssignmentOrderOriginalLookupStatus::FOUND && $line->rootOriginalId() === $c->rootOriginalId && $line->compositionIdentity() === $composition->identity && $line->compositionSha256() === $composition->sha256];
        });
        if ($status === AssignmentOrderOriginalLookupStatus::UNAVAILABLE) throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE);
        if (!$matches) throw AssignmentOrderOriginalSubmissionFailure::conflict(AssignmentOrderOriginalReason::SEMANTIC_COLLISION);
        return $line;
    }

    private function lookup(callable $read): array
    {
        return $this->persistenceCall(function () use ($read): array {
            $lookup = $read(); $status = $lookup->status();
            return [$status, $status === AssignmentOrderOriginalLookupStatus::FOUND ? $lookup->result() : null];
        });
    }

    private function persistenceCall(callable $read): mixed
    {
        try { return $read(); }
        catch (\Throwable) { throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE); }
    }

    private function validComposition(AssignmentOrderCompositionSnapshot $value): bool
    {
        return (bool) $value->identity && preg_match('/^[0-9a-f]{64}$/D', (string) $value->sha256) === 1
            && $value->installerIds !== [] && count($value->installerIds) === count(array_unique($value->installerIds))
            && min($value->installerIds) > 0 && ($value->controlEngineerUserId ?? 0) > 0;
    }

    private function fingerprint(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition, string $sha): string
    {
        $raw = '';
        foreach ([$c->mode->value, (string) $c->installationCaseId, (string) $c->assignmentOrderId, $c->rootOriginalId ?? '', $c->targetRevisionId ?? '', $c->expectedCurrentRevisionId ?? '', $c->documentDate, (string) $composition->identity, (string) $composition->sha256, $sha] as $member) $raw .= pack('N', strlen($member)).$member;
        return hash('sha256', $raw);
    }
}
