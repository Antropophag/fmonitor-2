<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class MariaDbInspectionEvidenceEnvironment
{
    private readonly MariaDbInspectionAuthorization $auth;
    private readonly MariaDbInspectionTransaction $tx;
    private readonly MariaDbInspectionCaseDirectory $cases;
    private readonly MariaDbInspectionTemplateDirectory $templates;
    private readonly MariaDbInspectionEvidenceWriter $writer;
    private readonly MariaDbInspectionEvidenceReader $reader;

    public function __construct(\mysqli $db, string $prefix, private readonly InspectionEvidenceClock $clock)
    {
        $this->auth = new MariaDbInspectionAuthorization($db, $prefix);
        $this->tx = new MariaDbInspectionTransaction($db, $prefix);
        $this->cases = new MariaDbInspectionCaseDirectory($db, $prefix);
        $this->templates = new MariaDbInspectionTemplateDirectory($db, $prefix);
        $this->writer = new MariaDbInspectionEvidenceWriter($db, $prefix, $this->cases);
        $this->reader = new MariaDbInspectionEvidenceReader($db, $prefix);
    }

    public function actor(int $id): ?array
    {
        return $this->auth->actor($id);
    }

    public function inspectionSchemaAvailable(): bool
    {
        return $this->auth->schemaAvailable();
    }

    public function beginCommand(int $id): void
    {
        $this->tx->begin($id);
    }

    public function commitCommand(): void
    {
        $this->tx->commit();
    }

    public function rollBackCommand(): void
    {
        $this->tx->rollBack();
    }

    public function installationCase(int $id): ?array
    {
        return $this->cases->installationCase($id, $this->tx->revision());
    }

    public function template(int $id): ?array
    {
        return $this->templates->template($id);
    }

    public function installer(int $id): ?array
    {
        return $this->cases->installer($id);
    }

    public function now(): string
    {
        return $this->clock->now()->format('Y-m-d\TH:i:sP');
    }

    public function appendItemCompletion(int $id, array $evidence): void
    {
        $this->writer->append($id, $evidence);
        $this->tx->setAcceptedRevision((int) $evidence['acceptedRevision']);
    }

    public function itemCompletion(int $id, string $operationId): ?array
    {
        return $this->reader->find($id, $operationId);
    }
}
