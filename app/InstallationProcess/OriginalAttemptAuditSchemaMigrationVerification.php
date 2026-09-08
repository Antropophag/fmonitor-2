<?php

declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
require_once __DIR__.'/OriginalAttemptAuditSchemaMigration.php';

final class OriginalAttemptAuditSchemaMigrationVerification
{
    public static function apply(\mysqli $connection,string $tablePrefix,OriginalAttemptAuditSchemaObserver $observer):array
    {return (new OriginalAttemptAuditSchemaEngine($observer))->apply($connection,$tablePrefix);}
}
