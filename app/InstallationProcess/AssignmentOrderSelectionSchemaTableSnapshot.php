<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class AssignmentOrderSelectionSchemaTableSnapshot
{
    public function __construct(public string $tableName, public string $shapeSha256,
        public string $rowCount, public string $rowsSha256, public ?string $nextId) {}
}
