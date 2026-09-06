<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/MariaDbOriginalStoredReader.php';

/** @internal One newly opened native connection, one lookup and one cached close. */
final class AssignmentOrderOriginalMariaDbFreshTerminalReader implements AssignmentOrderOriginalFreshTerminalReader
{
    private bool $read = false;
    private ?AssignmentOrderOriginalFreshReaderCloseStatus $closed = null;

    public function __construct(private readonly \mysqli $db, private readonly string $prefix,
        private readonly ?AssignmentOrderOriginalPersistenceObserver $observer) {}

    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup
    {
        $owned = false;
        try {
            if ($this->read || $this->closed !== null) AssignmentOrderOriginalSql::fail();
            $this->read = true;
            if (!AssignmentOrderOriginalDataScalar::uuid($requestId)) AssignmentOrderOriginalSql::fail();
            $sql = new AssignmentOrderOriginalSql($this->db, $this->prefix);
            if (!$sql->idle()) AssignmentOrderOriginalSql::fail();
            $sql->execute('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            if (!$this->db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY)) AssignmentOrderOriginalSql::fail();
            $owned = true;
            $query = 'SELECT * FROM '.$sql->table('fm2_assignment_order_original_requests').' WHERE request_id='.$sql->quote($requestId);
            // A current-key lock waits for a pending insert; a plain snapshot miss cannot prove rollback.
            $barrier = $sql->rows($query.' LOCK IN SHARE MODE');
            if (count($barrier) > 1) AssignmentOrderOriginalSql::fail();
            if ($barrier === []) {
                $result = (new AssignmentOrderOriginalStoredReader($sql))->request($requestId);
                if ($result->status() !== AssignmentOrderOriginalLookupStatus::NOT_FOUND) AssignmentOrderOriginalSql::fail();
                $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_READ_RELEASE);
                $owned = false;
                if (!$this->db->rollback()) AssignmentOrderOriginalSql::fail();
                return $result;
            }
            $owned = false;
            if (!$this->db->rollback()) AssignmentOrderOriginalSql::fail();
            return $sql->snapshot(function () use ($sql, $query, $barrier, $requestId): AssignmentOrderOriginalResultLookup {
                if ($sql->rows($query) !== $barrier) AssignmentOrderOriginalSql::fail();
                $result = (new AssignmentOrderOriginalStoredReader($sql))->request($requestId);
                if ($result->status() !== AssignmentOrderOriginalLookupStatus::FOUND) AssignmentOrderOriginalSql::fail();
                return $result;
            }, $this->observer);
        } catch (\Throwable) {
            return new AssignmentOrderOriginalResultLookupValue(AssignmentOrderOriginalLookupStatus::UNAVAILABLE);
        } finally {
            if ($owned) { try { $this->db->rollback(); } catch (\Throwable) {} }
        }
    }

    public function close(): AssignmentOrderOriginalFreshReaderCloseStatus
    {
        if ($this->closed !== null) return $this->closed;
        $closed = true;
        try { $this->observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_FRESH_READER_CLOSE); }
        catch (\Throwable) { $closed = false; }
        try { if (!$this->db->close()) $closed = false; }
        catch (\Throwable) { $closed = false; }
        return $this->closed = $closed ? AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED : AssignmentOrderOriginalFreshReaderCloseStatus::FAILED;
    }

    private function __clone() {}
}
