<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

final class MariaDbOriginalApplicationReferenceReader implements AssignmentOrderOriginalApplicationReferenceReader
{
    /** @var \WeakMap<AssignmentOrderOriginalApplicationReference,array> */
    private \WeakMap $issued;
    private readonly MariaDbOriginalApplicationReferenceSource $source;

    public function __construct(private readonly AssignmentOrderOriginalSql $sql)
    {
        $this->issued = new \WeakMap();
        $this->source = new MariaDbOriginalApplicationReferenceSource($sql);
    }

    public function readCurrent(int $objectId, int $orderId): AssignmentOrderOriginalApplicationReferenceLookup
    {
        if ($objectId <= 0 || $orderId <= 0)
            return new AssignmentOrderOriginalApplicationReferenceLookup(AssignmentOrderOriginalApplicationReferenceStatus::INVALID_ARGUMENT);
        try {
            $scope = $this->scope();
            $value = $this->sql->snapshot(function () use ($objectId, $orderId): ?array {
                $metadata = $this->source->metadata($objectId, $orderId);
                return $metadata === null ? null : [$metadata, $this->source->seal($metadata, $metadata['revisionId'])];
            });
            if ($value === null)
                return new AssignmentOrderOriginalApplicationReferenceLookup(AssignmentOrderOriginalApplicationReferenceStatus::NOT_FOUND);
            $reference = new AssignmentOrderOriginalApplicationReference($value[0]);
            $this->issued[$reference] = [$scope, $value[1]];
            return new AssignmentOrderOriginalApplicationReferenceLookup(AssignmentOrderOriginalApplicationReferenceStatus::FOUND, $reference);
        } catch (\Throwable) {
            return new AssignmentOrderOriginalApplicationReferenceLookup(AssignmentOrderOriginalApplicationReferenceStatus::UNAVAILABLE);
        }
    }

    public function confirmCurrent(AssignmentOrderOriginalApplicationReference $reference): AssignmentOrderOriginalApplicationGuardStatus
    {
        try {
            if (!isset($this->issued[$reference]) || $this->issued[$reference][0] !== $this->scope() || $this->sql->idle())
                return AssignmentOrderOriginalApplicationGuardStatus::UNAVAILABLE;
            $metadata = $reference->metadata(); $s = $this->sql;
            $cases = $s->rows('SELECT id FROM '.$s->table('fm2_installation_cases').' WHERE id='.$metadata['caseId']
                .' AND legacy_installation_object_id='.$metadata['objectId'].' FOR UPDATE');
            if (\count($cases) !== 1) return AssignmentOrderOriginalApplicationGuardStatus::UNAVAILABLE;
            $current = $this->source->metadata($metadata['objectId'], $metadata['orderId'], true);
            if ($current === null || $current['caseId'] !== $metadata['caseId']
                || $this->source->seal($current, $metadata['revisionId'], true) !== $this->issued[$reference][1])
                return AssignmentOrderOriginalApplicationGuardStatus::UNAVAILABLE;
            return $current['revisionId'] === $metadata['revisionId']
                ? AssignmentOrderOriginalApplicationGuardStatus::MATCHED : AssignmentOrderOriginalApplicationGuardStatus::CHANGED;
        } catch (\Throwable) {
            return AssignmentOrderOriginalApplicationGuardStatus::UNAVAILABLE;
        }
    }

    private function scope(): array
    {
        $db = $this->sql->db;
        if ($db->character_set_name() !== 'utf8mb4') AssignmentOrderOriginalSql::fail();
        $rows = $this->sql->rows('SELECT DATABASE() selected_database');
        if (\count($rows) !== 1 || !\is_string($rows[0]['selected_database']) || $rows[0]['selected_database'] === '')
            AssignmentOrderOriginalSql::fail();
        return [$rows[0]['selected_database'], $db->thread_id, $db->character_set_name()];
    }
}
