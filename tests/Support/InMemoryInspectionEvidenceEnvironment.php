<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

final class InMemoryInspectionEvidenceEnvironment
{
    /** @var array<int, array{active: bool, capabilities: list<string>}> */
    private array $actors = [];

    /** @var array<int, array<string, mixed>> */
    private array $cases = [];

    /** @var array<int, array<string, mixed>> */
    private array $templates = [];

    /** @var array<int, array<string, mixed>> */
    private array $installers = [];

    private bool $inspectionSchemaAvailable = true;

    public function __construct(private readonly string $now)
    {
    }

    /** @param list<string> $capabilities */
    public function seedActor(int $userId, bool $active, array $capabilities): void
    {
        $this->actors[$userId] = ['active' => $active, 'capabilities' => $capabilities];
    }

    /** @param array<string, mixed> $case */
    public function seedCase(int $installationCaseId, array $case): void
    {
        $this->cases[$installationCaseId] = $case;
    }

    /** @param array<string, mixed> $template */
    public function seedTemplate(int $templateId, array $template): void
    {
        $this->templates[$templateId] = $template;
    }

    /** @param array<string, mixed> $snapshot */
    public function seedInstaller(int $tabId, array $snapshot): void
    {
        $this->installers[$tabId] = $snapshot;
    }

    public function setInspectionSchemaAvailable(bool $available): void
    {
        $this->inspectionSchemaAvailable = $available;
    }

    public function inspectionSchemaAvailable(): bool
    {
        return $this->inspectionSchemaAvailable;
    }

    /** @param array<string, mixed> $changes */
    public function changeCase(int $installationCaseId, array $changes): void
    {
        $this->cases[$installationCaseId] = array_replace(
            $this->cases[$installationCaseId],
            $changes,
        );
    }

    /** @return array{active: bool, capabilities: list<string>}|null */
    public function actor(int $userId): ?array
    {
        return $this->actors[$userId] ?? null;
    }

    /** @return array<string, mixed>|null */
    public function installationCase(int $installationCaseId): ?array
    {
        return $this->cases[$installationCaseId] ?? null;
    }

    /** @return array<string, mixed>|null */
    public function template(int $templateId): ?array
    {
        return $this->templates[$templateId] ?? null;
    }

    /** @return array<string, mixed>|null */
    public function installer(int $tabId): ?array
    {
        return $this->installers[$tabId] ?? null;
    }

    public function now(): string
    {
        return $this->now;
    }

    /** @param array<string, mixed> $evidence */
    public function appendItemCompletion(int $installationCaseId, array $evidence): void
    {
        $this->cases[$installationCaseId]['revision'] = $evidence['acceptedRevision'];
        $this->cases[$installationCaseId]['itemCompletions'][$evidence['clientOperationId']] = $evidence;
    }

    /** @return array<string, mixed>|null */
    public function itemCompletion(int $installationCaseId, string $clientOperationId): ?array
    {
        return $this->cases[$installationCaseId]['itemCompletions'][$clientOperationId] ?? null;
    }
}
