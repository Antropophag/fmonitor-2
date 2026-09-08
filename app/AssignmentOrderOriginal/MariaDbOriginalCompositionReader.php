<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__.'/MariaDbOriginalSql.php';
require_once __DIR__.'/MariaDbOriginalSqlComposition.php';

final class AssignmentOrderOriginalMariaDbCompositionReader implements AssignmentOrderCompositionReader
{
    public function __construct(private \mysqli $db, private string $p, private ?AssignmentOrderOriginalPersistenceObserver $observer = null) {}

    public function find(int $case, int $order): AssignmentOrderCompositionSnapshot
    {
        try {
            if ($case < 1 || $order < 1) AssignmentOrderOriginalSql::fail();
            $sql = new AssignmentOrderOriginalSql($this->db, $this->p);
            return $sql->snapshot(fn() => AssignmentOrderOriginalSqlComposition::read($sql, $case, $order, $this->observer), $this->observer);
        } catch (\Throwable) {
            return AssignmentOrderOriginalSqlComposition::empty(AssignmentOrderCompositionLookupStatus::UNAVAILABLE, $case, $order);
        }
    }
}
