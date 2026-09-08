<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/MariaDbOriginalSql.php';
require_once __DIR__.'/AssignmentOrderOriginalPhysicalNames.php';
require_once __DIR__.'/MariaDbOriginalMaintenanceRows.php';

final readonly class AssignmentOrderOriginalMariaDbMaintenanceRepository implements AssignmentOrderOriginalMaintenanceRepository
{
    public function __construct(private \mysqli $connection, private string $tablePrefix = '', private ?AssignmentOrderOriginalPersistenceObserver $observer = null) {}

    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalMaintenanceResultLookup
    {
        try {
            if (!AssignmentOrderOriginalDataScalar::uuid($requestId)) AssignmentOrderOriginalSql::fail();
            $sql = new AssignmentOrderOriginalSql($this->connection,$this->tablePrefix);
            $value = $sql->snapshot(function() use ($sql,$requestId) {
                $where = ' WHERE request_id='.$sql->quote($requestId);
                $rows = $sql->rows('SELECT * FROM '.$this->table('requests').$where);
                $audits = $sql->rows('SELECT * FROM '.$this->table('audits').$where);
                if ($rows === [] && $audits === []) return null;
                if (count($rows) !== 1 || count($audits) !== 1) AssignmentOrderOriginalSql::fail();
                return AssignmentOrderOriginalMaintenanceRows::decode($requestId,$rows[0],$audits[0]);
            },$this->observer);
            return new AssignmentOrderOriginalMaintenanceLookupValue($value === null ? AssignmentOrderOriginalLookupStatus::NOT_FOUND : AssignmentOrderOriginalLookupStatus::FOUND,$value);
        } catch (\Throwable) { return new AssignmentOrderOriginalMaintenanceLookupValue(AssignmentOrderOriginalLookupStatus::UNAVAILABLE); }
    }

    public function commitResultAndAudit(AssignmentOrderOriginalMaintenanceCommit $commit): AssignmentOrderOriginalCommitStatus
    {
        $owned = $attempted = $committed = $collision = false;
        try {
            if (!AssignmentOrderOriginalMaintenanceRows::valid($commit)) return AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
            $sql = new AssignmentOrderOriginalSql($this->connection,$this->tablePrefix);
            if (!$sql->idle()) return AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
            $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_BEGIN);
            $sql->execute('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            if (!$this->connection->begin_transaction(MYSQLI_TRANS_START_READ_WRITE)) AssignmentOrderOriginalSql::fail();
            $owned = true; $values = AssignmentOrderOriginalMaintenanceRows::values($commit);
            try { $this->insert($sql,'requests',$values); }
            catch (\mysqli_sql_exception $error) {
                if ($error->getCode() === 1062) {
                    $rows = $sql->rows('SELECT request_id FROM '.$this->table('requests').' WHERE request_id='.$sql->quote($commit->requestId).' FOR UPDATE');
                    $collision = count($rows) === 1 && $rows[0]['request_id'] === $commit->requestId;
                }
                throw $error;
            }
            unset($values['next_cursor']); $this->insert($sql,'audits',$values);
            $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_NATIVE_COMMIT); $attempted = true;
            if (!$this->connection->commit()) AssignmentOrderOriginalSql::fail(); $committed = true;
            $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::AFTER_NATIVE_COMMIT);
            return AssignmentOrderOriginalCommitStatus::COMMITTED;
        } catch (\Throwable) {
            if ($committed) return AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;
            $rolledBack = !$owned || $this->rollback();
            if ($attempted || !$rolledBack) return AssignmentOrderOriginalCommitStatus::OUTCOME_UNKNOWN;
            return $collision ? AssignmentOrderOriginalCommitStatus::CONFLICT : AssignmentOrderOriginalCommitStatus::ROLLED_BACK;
        }
    }

    private function table(string $kind): string
    { return '`'.AssignmentOrderOriginalPhysicalNames::table($this->tablePrefix,'fm2_assignment_order_original_maintenance_'.$kind).'`'; }

    private function insert(AssignmentOrderOriginalSql $sql, string $kind, array $values): void
    { $sql->execute('INSERT INTO '.$this->table($kind).' ('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_map($sql->quote(...),$values)).')'); }

    private function rollback(): bool
    {
        $ok = true;
        try { $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_ROLLBACK); } catch (\Throwable) { $ok = false; }
        try { if (!$this->connection->rollback()) $ok = false; } catch (\Throwable) { $ok = false; }
        return $ok;
    }
}
