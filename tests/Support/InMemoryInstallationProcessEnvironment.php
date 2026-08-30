<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use FMonitor2\InstallationProcess\PersistenceFailureWithConfirmedRollback;
use FMonitor2\InstallationProcess\PersistenceCommitOutcomeUnknown;

final class InMemoryInstallationProcessEnvironment
{
    /** @var array<int, true> */
    private array $actorsAllowedToPrepareAssignmentOrder = [];

    /** @var array<int, true> */
    private array $actorsAllowedToConfirmOrderRegistration = [];

    /** @var array<int, true> */
    private array $actorsAllowedToOpenInstallation = [];

    /** @var array<int|string, array<string, mixed>> */
    private array $currentInstallerSnapshots = [];

    /** @var list<int|string> */
    private array $currentInstallerSnapshotReads = [];

    private bool $currentInstallerSnapshotReadsForbidden = false;

    /** @var array<int, true> */
    private array $actorsAllowedToReadSecurityAudit = [];

    /** @var array<int, array<string, mixed>> */
    private array $installationObjectProcesses = [];

    /** @var array<int, int> */
    private array $installationObjectProcessRevisions = [];

    /** @var array<int, array{process: array<string, mixed>, revision: int}> */
    private array $concurrentProcessReplacements = [];

    /** @var array<int, array{event: array<string, mixed>, revision: int}> */
    private array $concurrentAuditAppends = [];

    /** @var array<int, list<array<string, mixed>>> */
    private array $securityEvents = [];

    /** @var array<int, array<string, mixed>> */
    private array $installationObjectSnapshots = [];

    /** @var array<int|string, array<string, mixed>> */
    private array $installerSnapshots = [];

    /** @var array<int|string, true> */
    private array $missingInstallers = [];

    /** @var array<int, array<string, mixed>> */
    private array $engineerSnapshots = [];

    /** @var array<int, true> */
    private array $missingEngineers = [];

    /** @var list<array<string, mixed>> */
    private array $renderedArtifacts = [];
    private ?array $lastDocumentInput = null;

    private bool $installationObjectSnapshotReadsForbidden = false;

    private bool $installerSnapshotReadsForbidden = false;

    private bool $engineerSnapshotReadsForbidden = false;

    private bool $renderingForbidden = false;

    private bool $repeatedRenderingForbidden = false;

    private int $renderCallCount = 0;

    private bool $renderingFails = false;

    private bool $processReplacementForbidden = false;

    private bool $processReplacementFails = false;

    private bool $processReplacementOutcomeIsUnknown = false;

    private int $processReplacementCallCount = 0;

    private ?string $nextPreparationOperationId = null;

    private int $preparationOperationIdGenerationCount = 0;

    private bool $commitThenLoseAcknowledgement = false;

    private bool $loseAcknowledgementWithoutResult = false;

    /** @var array<string, array<string, mixed>> */
    private array $preparationResults = [];

    private int $preparationReconciliationCallCount = 0;

    private ?string $lastPersistedPreparationOperationId = null;

    private ?string $lastReconciledPreparationOperationId = null;

    private string $now = '1970-01-01T00:00:00+00:00';

    public function allowPreparationBy(int $actorId): void
    {
        $this->actorsAllowedToPrepareAssignmentOrder[$actorId] = true;
    }

    public function actorCanPrepareAssignmentOrder(int $actorId): bool
    {
        return isset($this->actorsAllowedToPrepareAssignmentOrder[$actorId]);
    }

    public function allowRegistrationConfirmationBy(int $actorId): void
    {
        $this->actorsAllowedToConfirmOrderRegistration[$actorId] = true;
    }

    public function actorCanConfirmOrderRegistration(int $actorId): bool
    {
        return isset($this->actorsAllowedToConfirmOrderRegistration[$actorId]);
    }

    public function allowOpeningBy(int $actorId): void
    {
        $this->actorsAllowedToOpenInstallation[$actorId] = true;
    }

    public function actorCanOpenInstallation(int $actorId): bool
    {
        return isset($this->actorsAllowedToOpenInstallation[$actorId]);
    }

    /** @param array<string, mixed> $snapshot */
    public function seedCurrentInstallerSnapshot(int|string $tabId, array $snapshot): void
    {
        $this->currentInstallerSnapshots[$tabId] = $snapshot;
    }

    /** @return array<string, mixed>|null */
    public function findCurrentInstallerSnapshot(int|string $tabId): ?array
    {
        if ($this->currentInstallerSnapshotReadsForbidden) {
            throw new \LogicException('Current Workforce must not be read for this fixture.');
        }
        $this->currentInstallerSnapshotReads[] = $tabId;
        return $this->currentInstallerSnapshots[$tabId] ?? null;
    }

    public function forbidCurrentInstallerSnapshotReads(): void
    {
        $this->currentInstallerSnapshotReadsForbidden = true;
    }

    /** @return list<int|string> */
    public function getCurrentInstallerSnapshotReads(): array
    {
        return $this->currentInstallerSnapshotReads;
    }

    public function corruptCurrentAssignmentOrderByClearingInstallers(int $installationObjectId): void
    {
        $orders = $this->installationObjectProcesses[$installationObjectId]['assignmentOrders'] ?? [];
        $current = array_key_last($orders);
        if ($current === null) {
            throw new \LogicException('A current assignment order is required for corruption fixture.');
        }
        $this->installationObjectProcesses[$installationObjectId]['assignmentOrders'][$current]['installers'] = [];
    }

    public function allowSecurityAuditReadBy(int $actorId): void
    {
        $this->actorsAllowedToReadSecurityAudit[$actorId] = true;
    }

    public function actorCanReadSecurityAudit(int $actorId): bool
    {
        return isset($this->actorsAllowedToReadSecurityAudit[$actorId]);
    }

    /** @param array<string, mixed> $process */
    public function seedInstallationObjectProcess(int $installationObjectId, array $process): void
    {
        $this->installationObjectProcesses[$installationObjectId] = $process;
    }

    public function seedInstallationObjectProcessRevision(int $installationObjectId, int $revision): void
    {
        $this->installationObjectProcessRevisions[$installationObjectId] = $revision;
    }

    /** @param array<string, mixed> $process */
    public function simulateConcurrentProcessReplacement(
        int $installationObjectId,
        array $process,
        int $revision,
    ): void {
        $this->concurrentProcessReplacements[$installationObjectId] = [
            'process' => $process,
            'revision' => $revision,
        ];
    }

    /** @param array<string, mixed> $event */
    public function simulateConcurrentAuditAppend(
        int $installationObjectId,
        array $event,
        int $revision,
    ): void {
        $this->concurrentAuditAppends[$installationObjectId] = [
            'event' => $event,
            'revision' => $revision,
        ];
    }

    public function setNow(string $now): void
    {
        $this->now = $now;
    }

    /** @param array<string, mixed> $snapshot */
    public function seedInstallationObjectSnapshot(int $installationObjectId, array $snapshot): void
    {
        $this->installationObjectSnapshots[$installationObjectId] = $snapshot;
    }

    /** @return array<string, mixed> */
    public function getInstallationObjectSnapshot(int $installationObjectId): array
    {
        if ($this->installationObjectSnapshotReadsForbidden) {
            throw new \LogicException('Installation object snapshot must not be read for this fixture.');
        }

        return $this->installationObjectSnapshots[$installationObjectId];
    }

    public function forbidInstallationObjectSnapshotReads(): void
    {
        $this->installationObjectSnapshotReadsForbidden = true;
    }

    /** @param array<string, mixed> $snapshot */
    public function seedInstallerSnapshot(int|string $tabId, array $snapshot): void
    {
        $this->installerSnapshots[$tabId] = $snapshot;
    }

    public function markInstallerMissing(int|string $tabId): void
    {
        $this->missingInstallers[$tabId] = true;
    }

    /** @return array<string, mixed>|null */
    public function findInstallerSnapshot(int|string $tabId): ?array
    {
        if ($this->installerSnapshotReadsForbidden) {
            throw new \LogicException('Installer catalog must not be read for this fixture.');
        }

        if (isset($this->missingInstallers[$tabId])) {
            return null;
        }

        return $this->installerSnapshots[$tabId];
    }

    /** @return array<string, mixed> */
    public function getInstallerSnapshot(int|string $tabId): array
    {
        if ($this->installerSnapshotReadsForbidden) {
            throw new \LogicException('Installer catalog must not be read for this fixture.');
        }

        if (isset($this->missingInstallers[$tabId])) {
            throw new \LogicException('Missing installer must be handled through nullable catalog lookup.');
        }

        return $this->installerSnapshots[$tabId];
    }

    public function forbidInstallerSnapshotReads(): void
    {
        $this->installerSnapshotReadsForbidden = true;
    }

    /** @param array<string, mixed> $snapshot */
    public function seedEngineerSnapshot(int $userId, array $snapshot): void
    {
        $this->engineerSnapshots[$userId] = $snapshot;
    }

    public function markEngineerMissing(int $userId): void
    {
        $this->missingEngineers[$userId] = true;
    }

    /** @return array<string, mixed>|null */
    public function findEngineerSnapshot(int $userId): ?array
    {
        if ($this->engineerSnapshotReadsForbidden) {
            throw new \LogicException('Engineer catalog must not be read for this fixture.');
        }

        if (isset($this->missingEngineers[$userId])) {
            return null;
        }

        return $this->engineerSnapshots[$userId];
    }

    /** @return array<string, mixed> */
    public function getEngineerSnapshot(int $userId): array
    {
        if ($this->engineerSnapshotReadsForbidden) {
            throw new \LogicException('Engineer catalog must not be read for this fixture.');
        }

        if (isset($this->missingEngineers[$userId])) {
            throw new \LogicException('Missing engineer must be handled through nullable catalog lookup.');
        }

        return $this->engineerSnapshots[$userId];
    }

    public function forbidEngineerSnapshotReads(): void
    {
        $this->engineerSnapshotReadsForbidden = true;
    }

    /** @param list<array<string, mixed>> $artifacts */
    public function setRenderedArtifacts(array $artifacts): void
    {
        $this->renderedArtifacts = $artifacts;
    }

    /** @return list<array<string, mixed>> */
    public function renderAssignmentOrder(array $documentInput): array
    {
        if ($this->renderingForbidden) {
            throw new \LogicException('Renderer must not be called for this fixture.');
        }

        ++$this->renderCallCount;
        $this->lastDocumentInput = $documentInput;
        if ($this->repeatedRenderingForbidden && $this->renderCallCount > 1) {
            throw new \LogicException('Renderer must not be repeated while retrying audit append.');
        }

        if ($this->renderingFails) {
            throw new \RuntimeException('template service unavailable');
        }

        return $this->renderedArtifacts;
    }

    public function getLastDocumentInput(): ?array { return $this->lastDocumentInput; }

    public function forbidRendering(): void
    {
        $this->renderingForbidden = true;
    }

    public function forbidRepeatedRendering(): void
    {
        $this->repeatedRenderingForbidden = true;
    }

    public function getRenderCallCount(): int
    {
        return $this->renderCallCount;
    }

    public function failRendering(): void
    {
        $this->renderingFails = true;
    }

    public function forbidProcessReplacement(): void
    {
        $this->processReplacementForbidden = true;
    }

    public function failProcessReplacement(): void
    {
        $this->processReplacementFails = true;
        $this->processReplacementOutcomeIsUnknown = false;
    }

    public function failProcessReplacementWithUnknownOutcome(): void
    {
        $this->processReplacementFails = false;
        $this->processReplacementOutcomeIsUnknown = true;
    }

    public function getProcessReplacementCallCount(): int
    {
        return $this->processReplacementCallCount;
    }

    public function setNextPreparationOperationId(string $operationId): void
    {
        $this->nextPreparationOperationId = $operationId;
    }

    public function newPreparationOperationId(): string
    {
        ++$this->preparationOperationIdGenerationCount;

        if ($this->nextPreparationOperationId === null) {
            return 'default-preparation-operation';
        }

        return $this->nextPreparationOperationId;
    }

    public function getPreparationOperationIdGenerationCount(): int
    {
        return $this->preparationOperationIdGenerationCount;
    }

    public function commitThenLoseAcknowledgement(): void
    {
        $this->commitThenLoseAcknowledgement = true;
    }

    public function loseAcknowledgementWithoutResult(): void
    {
        $this->loseAcknowledgementWithoutResult = true;
    }

    /** @return array<string, mixed>|null */
    public function findPreparationResult(string $operationId): ?array
    {
        ++$this->preparationReconciliationCallCount;
        $this->lastReconciledPreparationOperationId = $operationId;

        return $this->preparationResults[$operationId] ?? null;
    }

    public function getLastPersistedPreparationOperationId(): ?string
    {
        return $this->lastPersistedPreparationOperationId;
    }

    public function getLastReconciledPreparationOperationId(): ?string
    {
        return $this->lastReconciledPreparationOperationId;
    }

    public function getPreparationReconciliationCallCount(): int
    {
        return $this->preparationReconciliationCallCount;
    }

    /** @param array<string, mixed> $process */
    public function replaceInstallationObjectProcess(int $installationObjectId, array $process): void
    {
        if ($this->processReplacementForbidden) {
            throw new \LogicException('Process replacement must not follow renderer failure.');
        }

        if (isset($this->concurrentProcessReplacements[$installationObjectId])) {
            throw new \LogicException('Concurrent fixture requires revision-checked process replacement.');
        }

        $this->installationObjectProcesses[$installationObjectId] = $process;
    }

    public function getInstallationObjectProcessRevision(int $installationObjectId): int
    {
        if (isset($this->concurrentProcessReplacements[$installationObjectId])) {
            throw new \LogicException('Concurrent fixture requires atomic process and revision read.');
        }

        return $this->installationObjectProcessRevisions[$installationObjectId] ?? 0;
    }

    /** @return array{process: array<string, mixed>, revision: int} */
    public function loadInstallationObjectProcessAtRevision(int $installationObjectId): array
    {
        return [
            'process' => $this->installationObjectProcesses[$installationObjectId],
            'revision' => $this->installationObjectProcessRevisions[$installationObjectId] ?? 0,
        ];
    }

    /**
     * @param array<string, mixed> $process
     * @return array{replaced: bool, currentRevision: int}
     */
    public function replaceInstallationObjectProcessAtRevision(
        int $installationObjectId,
        int $expectedRevision,
        array $process,
        ?string $preparationOperationId = null,
    ): array {
        ++$this->processReplacementCallCount;
        $this->lastPersistedPreparationOperationId = $preparationOperationId;

        if ($this->processReplacementForbidden) {
            throw new \LogicException('Process replacement must not follow renderer failure.');
        }

        if ($this->processReplacementFails) {
            throw new PersistenceFailureWithConfirmedRollback('database unavailable');
        }

        if ($this->processReplacementOutcomeIsUnknown) {
            throw new \RuntimeException('commit outcome unknown');
        }

        if ($this->commitThenLoseAcknowledgement) {
            if ($preparationOperationId === null) {
                throw new \LogicException('Preparation operation id is required for unknown commit reconciliation.');
            }

            $this->installationObjectProcesses[$installationObjectId] = $process;
            $this->installationObjectProcessRevisions[$installationObjectId] = $expectedRevision + 1;
            $this->preparationResults[$preparationOperationId] = [
                'accepted' => true,
                'assignmentOrderVersion' => 1,
                'status' => 'prepared',
                'assignmentOrderDate' => $process['assignmentOrders'][0]['assignmentOrderDate'],
                'organizationType' => $process['assignmentOrders'][0]['organizationType'],
            ];

            throw new PersistenceCommitOutcomeUnknown('commit outcome unknown');
        }

        if ($this->loseAcknowledgementWithoutResult) {
            throw new PersistenceCommitOutcomeUnknown('commit outcome unknown');
        }

        if (isset($this->concurrentProcessReplacements[$installationObjectId])) {
            $replacement = $this->concurrentProcessReplacements[$installationObjectId];
            unset($this->concurrentProcessReplacements[$installationObjectId]);
            $this->installationObjectProcesses[$installationObjectId] = $replacement['process'];
            $this->installationObjectProcessRevisions[$installationObjectId] = $replacement['revision'];

            return ['replaced' => false, 'currentRevision' => $replacement['revision']];
        }

        $currentRevision = $this->getInstallationObjectProcessRevision($installationObjectId);
        if ($currentRevision !== $expectedRevision) {
            return ['replaced' => false, 'currentRevision' => $currentRevision];
        }

        $this->installationObjectProcesses[$installationObjectId] = $process;
        $this->installationObjectProcessRevisions[$installationObjectId] = $currentRevision + 1;

        return ['replaced' => true, 'currentRevision' => $currentRevision + 1];
    }

    public function now(): string
    {
        return $this->now;
    }

    /** @return array<string, mixed> */
    public function getInstallationObjectProcess(int $installationObjectId): array
    {
        return $this->installationObjectProcesses[$installationObjectId];
    }

    /** @param array<string, mixed> $event */
    public function appendEvent(int $installationObjectId, array $event): void
    {
        if (isset($this->concurrentAuditAppends[$installationObjectId])) {
            throw new \LogicException('Concurrent fixture requires revision-checked audit append.');
        }

        $this->installationObjectProcesses[$installationObjectId]['events'][] = $event;
    }

    /** @param array<string, mixed> $event
     * @return array{appended: bool, currentRevision: int}
     */
    public function appendEventAtRevision(
        int $installationObjectId,
        int $expectedRevision,
        array $event,
    ): array {
        if (isset($this->concurrentAuditAppends[$installationObjectId])) {
            $concurrentAppend = $this->concurrentAuditAppends[$installationObjectId];
            unset($this->concurrentAuditAppends[$installationObjectId]);
            $this->installationObjectProcesses[$installationObjectId]['events'][] = $concurrentAppend['event'];
            $this->installationObjectProcessRevisions[$installationObjectId] = $concurrentAppend['revision'];

            return ['appended' => false, 'currentRevision' => $concurrentAppend['revision']];
        }

        $currentRevision = $this->installationObjectProcessRevisions[$installationObjectId] ?? 0;
        if ($currentRevision !== $expectedRevision) {
            return ['appended' => false, 'currentRevision' => $currentRevision];
        }

        $this->installationObjectProcesses[$installationObjectId]['events'][] = $event;
        $this->installationObjectProcessRevisions[$installationObjectId] = $currentRevision + 1;

        return ['appended' => true, 'currentRevision' => $currentRevision + 1];
    }

    /** @param array<string, mixed> $event */
    public function appendSecurityEvent(int $installationObjectId, array $event): void
    {
        $this->securityEvents[$installationObjectId][] = $event;
    }

    /** @return list<array<string, mixed>> */
    public function getSecurityEvents(int $installationObjectId): array
    {
        return $this->securityEvents[$installationObjectId] ?? [];
    }
}
