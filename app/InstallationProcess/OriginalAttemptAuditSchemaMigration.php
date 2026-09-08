<?php

declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

enum OriginalAttemptAuditSchemaPhase:string
{
    case BEFORE_AUDIT_ALTER='before_audit_alter';
    case AFTER_AUDIT_ALTER='after_audit_alter';
}
interface OriginalAttemptAuditSchemaObserver
{
    public function observe(OriginalAttemptAuditSchemaPhase $phase):void;
}
require_once __DIR__.'/OriginalAttemptAuditSchemaEngineSchemaMigration.php';

final class OriginalAttemptAuditSchemaMigration
{
    public static function apply(\mysqli $connection,string $tablePrefix=''):array
    {
        return (new OriginalAttemptAuditSchemaEngine(new class implements OriginalAttemptAuditSchemaObserver {
            public function observe(OriginalAttemptAuditSchemaPhase $phase):void{}
        }))->apply($connection,$tablePrefix);
    }
}
