<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Checked native SQL operations; never owns a caller transaction. */
final readonly class AssignmentOrderOriginalSql
{
    public function __construct(public \mysqli $db, private string $prefix)
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) self::fail();
    }

    public function table(string $name): string
    { return '`'.$this->prefix.$name.'`'; }

    public function quote(?string $value): string
    { return $value === null ? 'NULL' : "'".$this->db->real_escape_string($value)."'"; }

    public function rows(string $sql): array
    {
        $result = $this->db->query($sql);
        if (!$result instanceof \mysqli_result) self::fail();
        try { return $result->fetch_all(MYSQLI_ASSOC); }
        finally { $result->free(); }
    }

    public function execute(string $sql): void
    {
        if ($this->db->query($sql) !== true)
            throw new \mysqli_sql_exception('AssignmentOrderOriginalPersistenceUnavailable', $this->db->errno);
    }

    public function idle(): bool
    {
        $rows = $this->rows('SELECT @@in_transaction active');
        return count($rows) === 1 && AssignmentOrderOriginalDataScalar::integer($rows[0]['active'], 0, 1) === 0;
    }

    public function snapshot(callable $read, ?AssignmentOrderOriginalPersistenceObserver $observer = null): mixed
    {
        if (!$this->idle()) self::fail();
        $this->execute('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        if (!$this->db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY | MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT)) self::fail();
        try { return $read(); }
        finally {
            $released = true;
            try { $observer?->observe(AssignmentOrderOriginalPersistenceEvent::BEFORE_READ_RELEASE); }
            catch (\Throwable) { $released = false; }
            try { if (!$this->db->rollback()) $released = false; }
            catch (\Throwable) { $released = false; }
            if (!$released) self::fail();
        }
    }

    public static function fail(): never
    { throw new \RuntimeException('AssignmentOrderOriginalPersistenceUnavailable'); }
}
