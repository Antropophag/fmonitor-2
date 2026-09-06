<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class AssignmentOrderSelectionSchemaSnapshot
{
    public function __construct(public string $schemaSha256, public array $tables) {}
}
