<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/MariaDbOriginalSql.php';
require_once __DIR__.'/MariaDbOriginalSqlComposition.php';

/** Unwired registry-first read adapter. Original mutation and locked checks retain their owner. */
final class MariaDbRegisteredAssignmentOrderCompositionReader implements AssignmentOrderCompositionReader
{
    public function __construct(private \mysqli $db, private string $prefix, private AssignmentOrderRegisteredCompositionReadObserver $observer) {}

    public function find(int $caseId, int $orderId): AssignmentOrderCompositionSnapshot
    {
        try {
            AssignmentOrderRegisteredCompositionValues::require($caseId > 0 && $orderId > 0);
            $sql = new AssignmentOrderOriginalSql($this->db, $this->prefix);
            $configuration = $sql->rows('SELECT DATABASE() db,@@character_set_connection charset');
            AssignmentOrderRegisteredCompositionValues::require(count($configuration) === 1 && is_string($configuration[0]['db'])
                && $configuration[0]['db'] !== '' && $configuration[0]['charset'] === 'utf8mb4');
            $release = new class($this->observer) implements AssignmentOrderOriginalPersistenceObserver {
                public function __construct(private AssignmentOrderRegisteredCompositionReadObserver $observer) {}
                public function observe(AssignmentOrderOriginalPersistenceEvent $event): void
                {
                    if ($event === AssignmentOrderOriginalPersistenceEvent::BEFORE_READ_RELEASE) { $this->observer->observe(AssignmentOrderRegisteredCompositionReadPhase::BEFORE_RELEASE); }
                }
            };
            return $sql->snapshot(fn () => MariaDbRegisteredCompositionQuery::read($sql, $caseId, $orderId, $this->observer), $release);
        } catch (\Throwable) { return AssignmentOrderOriginalSqlComposition::empty(AssignmentOrderCompositionLookupStatus::UNAVAILABLE, $caseId, $orderId); }
    }


}
