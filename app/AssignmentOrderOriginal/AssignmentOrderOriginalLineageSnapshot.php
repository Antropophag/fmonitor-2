<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal An immutable, validated copy of one lineage lookup. */
final readonly class AssignmentOrderOriginalLineageSnapshot
{
    private function __construct(
        public AssignmentOrderOriginalLookupStatus $status, public ?string $root, public ?string $current,
        public ?int $number, public ?string $composition, public ?string $hash, public ?string $date,
        public ?string $sha, public ?int $caseId, public ?int $orderId, public array $ids,
    ) {}

    public static function read(AssignmentOrderOriginalLineageLookup $source, ?string $target = null,
        ?string $queryRoot = null, ?int $queryCase = null, ?int $queryOrder = null, ?string $queryRevision = null): self
    {
        $status = $source->status();
        $root = $source->rootOriginalId();
        $current = $source->currentRevisionId();
        $number = $source->currentRevisionNumber();
        $composition = $source->compositionIdentity();
        $hash = $source->compositionSha256();
        $date = $source instanceof AssignmentOrderOriginalCurrentEvidenceLookup ? $source->currentDocumentDate() : null;
        $sha = $source instanceof AssignmentOrderOriginalCurrentEvidenceLookup ? $source->currentPdfSha256() : null;
        $complete = $source instanceof AssignmentOrderOriginalCompleteLineageLookup;
        $caseId = $complete ? $source->installationCaseId() : null;
        $orderId = $complete ? $source->assignmentOrderId() : null;
        $ids = $complete ? $source->revisionIds() : [];
        if ($status !== AssignmentOrderOriginalLookupStatus::FOUND) {
            if ([$root, $current, $number, $composition, $hash, $date, $sha, $caseId, $orderId, $ids]
                !== [null, null, null, null, null, null, null, null, null, []]) self::fail();
        } else {
            if (!$complete || !AssignmentOrderOriginalCommandShape::validId($root)
                || !AssignmentOrderOriginalCommandShape::validId($current) || ($caseId ?? 0) < 1 || ($orderId ?? 0) < 1
                || !AssignmentOrderOriginalDataScalar::composition($composition, $orderId)
                || !AssignmentOrderOriginalDataScalar::hash($hash) || !AssignmentOrderOriginalDataScalar::hash($sha)
                || !AssignmentOrderOriginalDataScalar::date($date) || $number === null || $number < 1 || $number > 4294967295
                || !array_is_list($ids) || count($ids) !== $number || $ids[array_key_last($ids)] !== $current) self::fail();
            $seen = [];
            foreach ($ids as $id) {
                if (!is_string($id) || !AssignmentOrderOriginalCommandShape::validId($id) || isset($seen[$id])) self::fail();
                $seen[$id] = true;
            }
            if (($queryRoot !== null && $root !== $queryRoot) || ($queryCase !== null && $caseId !== $queryCase)
                || ($queryOrder !== null && $orderId !== $queryOrder)
                || ($queryRevision !== null && !in_array($queryRevision, $ids, true))) self::fail();
        }
        foreach (array_unique(array_filter([$current, $target, $queryRevision], static fn($id) => $id !== null)) as $id)
            if ($source->containsRevision($id) !== in_array($id, $ids, true)) self::fail();
        return new self($status, $root, $current, $number, $composition, $hash, $date, $sha, $caseId, $orderId, $ids);
    }

    public function matches(SubmitAssignmentOrderOriginalCommand $c, AssignmentOrderCompositionSnapshot $composition): bool
    {
        return $this->status === AssignmentOrderOriginalLookupStatus::FOUND && $this->caseId === $c->installationCaseId
            && $this->orderId === $c->assignmentOrderId && $this->composition === $composition->identity && $this->hash === $composition->sha256;
    }

    private static function fail(): never
    { throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::PERSISTENCE_FAILURE); }
}
