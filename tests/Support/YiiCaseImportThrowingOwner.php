<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class PilotCaseImporter
{
    public function __construct(\mysqli $connection,string $processPrefix,string $legacyPrefix){}
    public function assertSchemaAvailable():void{throw new \RuntimeException('PRIVATE_CASE_IMPORT_THROWABLE SELECT secret_sql');}
}
