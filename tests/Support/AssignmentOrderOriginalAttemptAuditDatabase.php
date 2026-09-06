<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;

final class OriginalAttemptAuditDatabase
{
    public static function case(string $name, callable $check): void
    {
        \integrityCase($name, static function() use($check):void {
            $f = new OriginalIntegrityDatabase();
            try { $check($f); } finally { $f->close(); }
        });
    }
    public static function migrate(OriginalIntegrityDatabase $f, ?string $prefix = null): array
    {
        if (!class_exists(I\OriginalAttemptAuditSchemaMigration::class)) throw new \TestFailure('INTENDED_RED: public audit schema migration absent');
        return I\OriginalAttemptAuditSchemaMigration::apply($f->db, $prefix ?? $f->prefix);
    }
    public static function prefix(OriginalIntegrityDatabase $f, string $prefix): void
    {
        I\ProductionProcessSchemaMigration::apply($f->db,$prefix);I\IdentityAccessSchemaMigration::apply($f->db,$prefix);
        I\ProcessUserCapabilitiesSchemaMigration::apply($f->db,$prefix);I\ProcessCommandCapabilitiesSchemaMigration::apply($f->db,$prefix);
    }
    public static function writer(OriginalIntegrityDatabase $f, ?O\AssignmentOrderOriginalPersistenceObserver $observer = null): O\AssignmentOrderOriginalAttemptAuditWriter
    {
        if (!class_exists(O\AssignmentOrderOriginalMariaDbAttemptAuditWriter::class)) throw new \TestFailure('INTENDED_RED: public MariaDB audit writer absent');
        return new O\AssignmentOrderOriginalMariaDbAttemptAuditWriter($f->db,$f->prefix,$observer);
    }
    public static function dto(array $changes = []): O\AssignmentOrderOriginalSafeAttemptAudit
    {
        if (!class_exists(O\AssignmentOrderOriginalSafeAttemptAudit::class)) throw new \TestFailure('INTENDED_RED: passive audit DTO absent');
        return new O\AssignmentOrderOriginalSafeAttemptAudit(...($changes+[
            'requestId'=>'00000000-0000-4000-8000-000000000701','actorUserId'=>18,'mode'=>O\AssignmentOrderOriginalMode::INITIAL,
            'installationCaseId'=>4512,'assignmentOrderId'=>81,'status'=>O\AssignmentOrderOriginalStatus::REJECTED,
            'reason'=>O\AssignmentOrderOriginalReason::AUTHORIZATION_DENIED,'attemptedAtUtc'=>'2026-09-06T09:00:00Z']));
    }
    public static function auditRows(OriginalIntegrityDatabase $f): array
    {
        return $f->db->query('SELECT audit_id,request_id,actor_identity,mode,installation_case_id,assignment_order_id,status,reason_code,attempted_at_utc FROM `'.$f->prefix.'fm2_assignment_order_original_audits` ORDER BY audit_id')->fetch_all(MYSQLI_ASSOC);
    }
    public static function runCanonical(OriginalIntegrityDatabase $f): array
    {
        $env=['PATH'=>getenv('PATH'),'FMONITOR_DB_HOST'=>$f->host,'FMONITOR_DB_PORT'=>(string)$f->port,
            'FMONITOR_DB_NAME'=>$f->database,'FMONITOR_DB_USER'=>$f->user,'FMONITOR_DB_PASSWORD'=>rtrim(file_get_contents($f->passwordFile),"\n"),'FMONITOR_PROCESS_TABLE_PREFIX'=>$f->prefix];
        $process=proc_open([PHP_BINARY,dirname(__DIR__,2).'/bin/fmonitor2-migrate.php'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);
        if(!is_resource($process))throw new \TestFailure('Canonical migration child unavailable');
        $out=$error='';$exit=-1;$deadline=hrtime(true)+15_000_000_000;
        foreach($pipes as $pipe)stream_set_blocking($pipe,false);
        try {
            do {
                $read=array_values(array_filter($pipes,static fn($pipe)=>!feof($pipe)));$write=$except=[];
                if($read!==[]){if(stream_select($read,$write,$except,0,100_000)===false)throw new \TestFailure('Canonical child select failed');foreach($read as $pipe){$bytes=stream_get_contents($pipe,8192);if($bytes===false)throw new \TestFailure('Canonical child read failed');if($pipe===$pipes[1])$out.=$bytes;else $error.=$bytes;}}
                $state=proc_get_status($process);if(!$state['running']&&$state['exitcode']!==-1)$exit=$state['exitcode'];
                if(hrtime(true)>=$deadline||strlen($out)+strlen($error)>32768)throw new \TestFailure('Canonical child exceeded time/output bound');
            } while($state['running']||!feof($pipes[1])||!feof($pipes[2]));
        } finally {
            if(proc_get_status($process)['running'])proc_terminate($process,9);
            foreach($pipes as $pipe)fclose($pipe);$closed=proc_close($process);if($exit===-1)$exit=$closed;
        }
        \assertSameValue('', $error, 'canonical child no native diagnostics');
        return [$exit,json_decode($out,true,512,JSON_THROW_ON_ERROR)];
    }
}
