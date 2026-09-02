<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;
final class InspectionEvidence implements InspectionEvidenceApplication
{
    private readonly InspectionItemCommandPolicy $commandPolicy;
    private readonly ItemCompletionEvidenceCodec $evidenceCodec;
    public function __construct(private readonly object $environment)
    {
        $this->commandPolicy = new InspectionItemCommandPolicy();
        $this->evidenceCodec = new ItemCompletionEvidenceCodec();
    }
    public function completeItem(CompleteInspectionItem $command): ItemCompletionResult
    {
        /** @var array{active: bool, capabilities: list<string>}|null $actor */
        $actor = $this->environment->actor($command->actorUserId);
        if ($actor === null || !$actor['active'] || !in_array('inspection.item.complete', $actor['capabilities'], true)) {
            return new ItemCompletionResult('ACTOR_NOT_AUTHORIZED', 0);
        }
        if (!$this->commandPolicy->valid($command)) {
            return new ItemCompletionResult('INVALID_COMMAND', 0);
        }
        if (!$this->environment->inspectionSchemaAvailable()) {
            return new ItemCompletionResult('INSPECTION_SCHEMA_UNAVAILABLE', 0);
        }
        if (method_exists($this->environment, 'beginCommand')) {
            $this->environment->beginCommand($command->installationCaseId);
        }
        try {
            return $this->completeAuthorizedItem($command);
        } catch (\Throwable $failure) {
            if (method_exists($this->environment, 'rollBackCommand')) {
                $this->environment->rollBackCommand();
            }
            throw $failure;
        }
    }
    private function completeAuthorizedItem(CompleteInspectionItem $command): ItemCompletionResult
    {
        $normalizedInstallerIds = $this->commandPolicy->normalizedInstallerIds($command);
        $normalizedPayload = $this->commandPolicy->normalizedPayload($command, $normalizedInstallerIds);
        /** @var array<string, mixed>|null $existing */
        $existing = $this->environment->itemCompletion(
            $command->installationCaseId,
            $command->clientOperationId,
        );
        if ($existing !== null) {
            if (($existing['normalizedPayload'] ?? null) === $normalizedPayload) {
                return $this->finish(
                    new ItemCompletionResult('DUPLICATE', (int) $existing['acceptedRevision']),
                    false,
                );
            }
            return $this->finish(
                new ItemCompletionResult(
                    'OPERATION_PAYLOAD_CONFLICT',
                    (int) $existing['acceptedRevision'],
                ),
                false,
            );
        }
        /** @var array<string, mixed>|null $case */
        $case = $this->environment->installationCase($command->installationCaseId);
        if ($case === null) {
            return $this->finish(new ItemCompletionResult('CASE_NOT_FOUND', 0), false);
        }
        $currentRevision = (int) ($case['revision'] ?? 0);
        if (($case['state'] ?? null) !== 'working') {
            return $this->finish(new ItemCompletionResult('CASE_NOT_WORKING', $currentRevision), false);
        }
        /** @var array<string, mixed>|null $template */
        $template = $this->environment->template((int) $case['templateId']);
        if ($template === null) {
            return $this->finish(new ItemCompletionResult('CHECKLIST_TEMPLATE_UNAVAILABLE', $currentRevision), false);
        }
        $items = $template['items'][$command->sectionId] ?? [];
        if (!in_array($command->itemId, $items, true)) {
            return $this->finish(new ItemCompletionResult('CHECKLIST_ITEM_UNKNOWN', $currentRevision), false);
        }
        $assigned = array_map('intval', $case['registeredInstallerTabIds'] ?? []);
        $installerSnapshots = [];
        foreach ($normalizedInstallerIds as $tabId) {
            if (!in_array($tabId, $assigned, true)) {
                return $this->finish(new ItemCompletionResult('INSTALLER_NOT_ASSIGNED', $currentRevision), false);
            }
            /** @var array<string, mixed>|null $installer */
            $installer = $this->environment->installer($tabId);
            if (
                $installer === null
                || trim((string) ($installer['fullName'] ?? '')) === ''
                || trim((string) ($installer['position'] ?? '')) === ''
            ) {
                return $this->finish(new ItemCompletionResult('INSTALLER_SNAPSHOT_INCOMPLETE', $currentRevision), false);
            }
            $installerSnapshots[] = new InstallerEvidence(
                $tabId,
                (string) $installer['fullName'],
                (string) $installer['position'],
            );
        }
        if ($command->expectedRevision !== $currentRevision) {
            return $this->finish(new ItemCompletionResult('STALE_REVISION', $currentRevision), false);
        }
        $acceptedRevision = $currentRevision + 1;
        $evidence = new ItemCompletionEvidence(
            $command->clientOperationId,
            $command->installationCaseId,
            $command->sectionId,
            $command->itemId,
            $command->actorUserId,
            isset($case['assignedControlEngineerUserId'])
                ? (int) $case['assignedControlEngineerUserId']
                : null,
            $command->deviceTime,
            $this->environment->now(),
            $command->expectedRevision,
            $acceptedRevision,
            (int) $case['templateId'],
            (string) $template['version'],
            (string) $template['sha256'],
            $acceptedRevision,
            $installerSnapshots,
        );
        $this->environment->appendItemCompletion(
            $command->installationCaseId,
            $this->evidenceCodec->toStored($evidence, $normalizedPayload),
        );
        return $this->finish(new ItemCompletionResult('ACCEPTED', $acceptedRevision), true);
    }
    public function getItemCompletion(int $installationCaseId,string $clientOperationId):?ItemCompletionEvidence {
        /** @var array<string, mixed>|null $evidence */
        $evidence = $this->environment->itemCompletion($installationCaseId, $clientOperationId);
        if ($evidence === null) {
            return null;
        }
        return $this->evidenceCodec->fromStored($evidence);
    }
    private function finish(ItemCompletionResult $result, bool $commit): ItemCompletionResult
    {
        if ($commit && method_exists($this->environment, 'commitCommand')) {
            $this->environment->commitCommand();
        } elseif (!$commit && method_exists($this->environment, 'rollBackCommand')) {
            $this->environment->rollBackCommand();
        }
        return $result;
    }
}
