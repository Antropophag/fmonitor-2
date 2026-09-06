<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
class_exists(\FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory::class);
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalInitialProcessState.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalInitialFixture.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalMatrixInputs.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionReader;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderCompositionSnapshot;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAcceptedCommit;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAttemptCommit;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalCommitStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDependencies;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLifecycleEvent;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLifecycleObserver;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLineageLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalLookupStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReason;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReferenceLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalRepository;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResultLookup;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResultLookupValue;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialClock;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialIds;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialLineageLookup;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialObservers;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialReferenceLookup;
use FMonitor2\Tests\Support\AssignmentOrderOriginalInitialStorage;
use FMonitor2\Tests\Support\AssignmentOrderOriginalMatrixAuthorizer;
use FMonitor2\Tests\Support\AssignmentOrderOriginalMatrixStream;

// Gate 2 correction for Gate 5 findings 3-6; approved contract v54.
$pdf = base64_decode('JVBERi0xLjQKJSVFT0YK', true);
assertSameValue(true, is_string($pdf), 'Minimal passive fixture decodes.');

final class Gate5DomainComposition implements AssignmentOrderCompositionReader
{
    public function find(int $caseId, int $orderId): AssignmentOrderCompositionSnapshot
    {
        return new AssignmentOrderCompositionSnapshot(
            AssignmentOrderCompositionLookupStatus::FOUND,
            $caseId,
            $orderId,
            'composition-' . $orderId . '-v1',
            hash('sha256', 'composition-' . $orderId),
            [7001],
            31,
        );
    }
}

final class Gate5DomainLineage implements AssignmentOrderOriginalLineageLookup
{
    public function __construct(private AssignmentOrderOriginalLookupStatus $status, private ?AssignmentOrderOriginalAcceptedCommit $commit = null) {}
    public function status(): AssignmentOrderOriginalLookupStatus { return $this->status; }
    public function rootOriginalId(): ?string { return $this->commit?->rootOriginalId; }
    public function currentRevisionId(): ?string { return $this->commit?->newRevisionId; }
    public function currentRevisionNumber(): ?int { return $this->commit?->newRevisionNumber; }
    public function compositionIdentity(): ?string { return $this->commit?->compositionIdentity; }
    public function compositionSha256(): ?string { return $this->commit?->compositionSha256; }
    public function containsRevision(string $revisionId): bool { return $revisionId === $this->commit?->newRevisionId; }
}

final class Gate5DomainRepository implements AssignmentOrderOriginalRepository
{
    public AssignmentOrderOriginalLookupStatus $terminalStatus = AssignmentOrderOriginalLookupStatus::NOT_FOUND;
    public AssignmentOrderOriginalLookupStatus $fingerprintStatus = AssignmentOrderOriginalLookupStatus::NOT_FOUND;
    public AssignmentOrderOriginalCommitStatus $attemptStatus = AssignmentOrderOriginalCommitStatus::COMMITTED;
    public bool $throwAttempt = false;
    public bool $forceCorrectionDrift = false;
    /** @var array<int,AssignmentOrderOriginalAcceptedCommit> */
    public array $acceptedByOrder = [];
    public int $attemptCalls = 0;
    public int $lineageCalls = 0;

    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup
    {
        return new AssignmentOrderOriginalResultLookupValue($this->terminalStatus);
    }
    public function findAcceptedFingerprint(string $fingerprint): AssignmentOrderOriginalResultLookup
    {
        return new AssignmentOrderOriginalResultLookupValue($this->fingerprintStatus);
    }
    public function findLineage(string $rootOriginalId): AssignmentOrderOriginalLineageLookup
    {
        ++$this->lineageCalls;
        if ($this->forceCorrectionDrift) {
            $commit = reset($this->acceptedByOrder) ?: null;
            return new Gate5DomainLineage(AssignmentOrderOriginalLookupStatus::FOUND, $commit);
        }
        if ($rootOriginalId !== '') {
            foreach ($this->acceptedByOrder as $commit) if ($commit->rootOriginalId === $rootOriginalId) return new Gate5DomainLineage(AssignmentOrderOriginalLookupStatus::FOUND, $commit);
            return new Gate5DomainLineage(AssignmentOrderOriginalLookupStatus::NOT_FOUND);
        }
        // Deliberately exposes the reviewed API defect: the repository cannot
        // choose the requested assignment order when INITIAL supplies ''.
        $commit = reset($this->acceptedByOrder) ?: null;
        return new Gate5DomainLineage($commit ? AssignmentOrderOriginalLookupStatus::FOUND : AssignmentOrderOriginalLookupStatus::NOT_FOUND, $commit);
    }
    public function commitAccepted(AssignmentOrderOriginalAcceptedCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        $this->acceptedByOrder[$commit->assignmentOrderId] = $commit;
        return AssignmentOrderOriginalCommitStatus::COMMITTED;
    }
    public function commitAttempt(AssignmentOrderOriginalAttemptCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        ++$this->attemptCalls;
        if ($this->throwAttempt) throw new RuntimeException('attempt unavailable');
        return $this->attemptStatus;
    }
    public function hasCommittedContent(string $opaqueIdentity): AssignmentOrderOriginalReferenceLookup { return new AssignmentOrderOriginalInitialReferenceLookup(); }
    public function evidenceCanonicalJson(int $caseId, int $orderId): string { return '{}'; }
}

final class Gate5DomainLifecycle implements AssignmentOrderOriginalLifecycleObserver
{
    public int $postFinalize = 0;
    public function __construct(private AssignmentOrderOriginalInitialStorage $storage) {}
    public function observe(AssignmentOrderOriginalLifecycleEvent $event): void
    {
        if ($event !== AssignmentOrderOriginalLifecycleEvent::AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT) return;
        ++$this->postFinalize;
        assertSameValue(true, $this->storage->stage?->lease !== null, 'Post-finalize event is emitted by the real finalize path.');
        assertSameValue(0, $this->storage->stage?->lease?->releaseCalls, 'Actual finalize lease is held while post-finalize observer runs.');
    }
}

$resultTuple = static fn (AssignmentOrderOriginalResult $result): array => [$result->status(), $result->reasonCode(), $result->retryable()];
$command = static fn (string $requestId, int $orderId, AssignmentOrderOriginalMatrixStream $stream, AssignmentOrderOriginalMode $mode = AssignmentOrderOriginalMode::INITIAL): SubmitAssignmentOrderOriginalCommand => new SubmitAssignmentOrderOriginalCommand(
    $requestId, $mode, 4512, $orderId, 18, '2026-09-01', true,
    $mode === AssignmentOrderOriginalMode::INITIAL ? null : 'original-0001',
    $mode === AssignmentOrderOriginalMode::INITIAL ? null : 'revision-0001',
    $mode === AssignmentOrderOriginalMode::INITIAL ? null : 'revision-0001',
    $mode === AssignmentOrderOriginalMode::INITIAL ? null : 'Исправление',
    new AssignmentOrderOriginalUpload($stream, 'signed.pdf', 'application/pdf'),
);
$build = static function (Gate5DomainRepository $repository, AssignmentOrderOriginalAuthorizationStatus $auth = AssignmentOrderOriginalAuthorizationStatus::ALLOWED): array {
    $storage = new AssignmentOrderOriginalInitialStorage();
    $observers = new AssignmentOrderOriginalInitialObservers();
    $lifecycle = new Gate5DomainLifecycle($storage);
    $app = AssignmentOrderOriginalVerificationFactory::create(new AssignmentOrderOriginalDependencies(
        new AssignmentOrderOriginalMatrixAuthorizer($auth), new Gate5DomainComposition(), new AssignmentOrderOriginalInitialClock(),
        new AssignmentOrderOriginalInitialIds(), new AssignmentOrderOriginalInitialInspector(), $storage, $repository,
        $lifecycle, $observers, $observers, $observers, $observers,
    ));
    return [$app, $storage, $lifecycle];
};

$unavailableAuthRepo = new Gate5DomainRepository();
[$unavailableAuth] = $build($unavailableAuthRepo, AssignmentOrderOriginalAuthorizationStatus::UNAVAILABLE);
$authStream = new AssignmentOrderOriginalMatrixStream($pdf);
assertSameValue(
    [AssignmentOrderOriginalStatus::FAILED, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE, true],
    $resultTuple($unavailableAuth->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000501', 81, $authStream))),
    'Authorization UNAVAILABLE has the only contract-valid technical tuple.',
);
assertSameValue(0, $authStream->readCalls, 'Authorization unavailable fails before stream.');

foreach (['terminalStatus', 'fingerprintStatus'] as $index => $property) {
    $repo = new Gate5DomainRepository();
    $repo->{$property} = AssignmentOrderOriginalLookupStatus::UNAVAILABLE;
    [$app] = $build($repo);
    $stream = new AssignmentOrderOriginalMatrixStream($pdf);
    $result = $app->submitAssignmentOrderOriginal($command(sprintf('00000000-0000-4000-8000-%012d', 502 + $index), 81, $stream));
    assertSameValue([AssignmentOrderOriginalStatus::FAILED, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE, true], $resultTuple($result), "{$property} UNAVAILABLE fails closed.");
    assertSameValue($property === 'terminalStatus' ? 0 : 2, $stream->readCalls, "{$property} UNAVAILABLE is detected at its exact terminal/pre-stream or fingerprint/post-stream boundary.");
}

foreach ([AssignmentOrderOriginalCommitStatus::ROLLED_BACK, 'throw'] as $index => $attemptFailure) {
    $repo = new Gate5DomainRepository();
    $repo->attemptStatus = $attemptFailure === 'throw' ? AssignmentOrderOriginalCommitStatus::COMMITTED : $attemptFailure;
    $repo->throwAttempt = $attemptFailure === 'throw';
    [$app] = $build($repo);
    $stream = new AssignmentOrderOriginalMatrixStream($pdf);
    $invalid = new SubmitAssignmentOrderOriginalCommand(sprintf('00000000-0000-4000-8000-%012d', 510 + $index), AssignmentOrderOriginalMode::INITIAL, 4512, 81, 18, '2026-09-01', false, null, null, null, null, new AssignmentOrderOriginalUpload($stream, 'signed.pdf', 'application/pdf'));
    assertSameValue([AssignmentOrderOriginalStatus::FAILED, AssignmentOrderOriginalReason::PERSISTENCE_FAILURE, true], $resultTuple($app->submitAssignmentOrderOriginal($invalid)), 'A missing atomic terminal attempt/audit cannot return the unaudited domain result.');
    assertSameValue(1, $repo->attemptCalls, 'Attempt audit persistence is invoked exactly once.');
}

$orders = new Gate5DomainRepository();
foreach ([[81, '00000000-0000-4000-8000-000000000521'], [82, '00000000-0000-4000-8000-000000000522']] as [$orderId, $requestId]) {
    [$app, , $lifecycle] = $build($orders);
    $result = $app->submitAssignmentOrderOriginal($command($requestId, $orderId, new AssignmentOrderOriginalMatrixStream($pdf)));
    assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $result->status(), "INITIAL is scoped to assignment order {$orderId} independently.");
    assertSameValue(1, $lifecycle->postFinalize, 'Exactly one real post-finalize lifecycle event is emitted for an accepted upload.');
}

$driftRepo = new Gate5DomainRepository();
$seedStream = new AssignmentOrderOriginalMatrixStream($pdf);
[$seedApp] = $build($driftRepo);
assertSameValue(AssignmentOrderOriginalStatus::ACCEPTED, $seedApp->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000530', 81, $seedStream))->status(), 'Correction precedence fixture has a committed root.');
$driftRepo->forceCorrectionDrift = true;
[$driftApp] = $build($driftRepo);
$correctionStream = new AssignmentOrderOriginalMatrixStream($pdf);
$drift = $driftApp->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000531', 82, $correctionStream, AssignmentOrderOriginalMode::CORRECTION));
assertSameValue([AssignmentOrderOriginalStatus::CONFLICT, AssignmentOrderOriginalReason::SEMANTIC_COLLISION, false], $resultTuple($drift), 'Correction composition drift has exact precedence.');
assertSameValue(0, $correctionStream->readCalls, 'Correction lineage/composition drift is resolved before stream access.');

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_GATE5_DOMAIN_RED_001_OK\n");
