<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

final readonly class MariaDbOriginalHistoryReader implements AssignmentOrderOriginalHistoryReader
{
    private MariaDbOriginalHistorySource $source;
    public function __construct(private AssignmentOrderOriginalSql $sql, private string $root)
    { $this->source = new MariaDbOriginalHistorySource($sql); }

    public function readHistory(int $objectId, int $orderId, int $afterRevisionNumber = 0, int $limit = 50): AssignmentOrderOriginalHistoryLookup
    {
        if ($objectId <= 0 || $orderId <= 0 || $afterRevisionNumber < 0 || $afterRevisionNumber > 4294967295 || $limit < 1 || $limit > 100)
            return new AssignmentOrderOriginalHistoryLookup(AssignmentOrderOriginalHistoryStatus::INVALID_ARGUMENT);
        try {
            $data = $this->snapshot(fn() => $this->source->history($objectId, $orderId, $afterRevisionNumber, $limit));
            return $data === null ? new AssignmentOrderOriginalHistoryLookup(AssignmentOrderOriginalHistoryStatus::NOT_FOUND)
                : new AssignmentOrderOriginalHistoryLookup(AssignmentOrderOriginalHistoryStatus::FOUND, new AssignmentOrderOriginalHistoryPage($data));
        } catch (\Throwable) {
            return new AssignmentOrderOriginalHistoryLookup(AssignmentOrderOriginalHistoryStatus::UNAVAILABLE);
        }
    }

    public function prepareDownload(int $objectId, int $orderId, string $revisionId): AssignmentOrderOriginalDownloadLookup
    {
        if ($objectId <= 0 || $orderId <= 0 || !AssignmentOrderOriginalCommandShape::validId($revisionId))
            return new AssignmentOrderOriginalDownloadLookup(AssignmentOrderOriginalHistoryStatus::INVALID_ARGUMENT);
        try {
            $data = $this->snapshot(fn() => $this->source->download($objectId, $orderId, $revisionId));
            if ($data === null) return new AssignmentOrderOriginalDownloadLookup(AssignmentOrderOriginalHistoryStatus::NOT_FOUND);
            $bytes = (new OriginalPreparedPdfBytes($this->root))->read($data['metadata']['revision'], $data['identity']);
            return new AssignmentOrderOriginalDownloadLookup(AssignmentOrderOriginalHistoryStatus::FOUND,
                new AssignmentOrderOriginalPreparedDownload($data['metadata'], $bytes));
        } catch (\Throwable) {
            return new AssignmentOrderOriginalDownloadLookup(AssignmentOrderOriginalHistoryStatus::UNAVAILABLE);
        }
    }

    private function snapshot(callable $read): mixed
    {
        if ($this->sql->db->character_set_name() !== 'utf8mb4') AssignmentOrderOriginalSql::fail();
        $database = $this->sql->rows('SELECT DATABASE() selected_database');
        if (\count($database) !== 1 || !\is_string($database[0]['selected_database']) || $database[0]['selected_database'] === '')
            AssignmentOrderOriginalSql::fail();
        return $this->sql->snapshot($read);
    }
}
