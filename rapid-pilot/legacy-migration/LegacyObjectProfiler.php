<?php

declare(strict_types=1);

require_once __DIR__ . '/LegacyObjectClassification.php';

final class LegacyObjectMySqlProfiler
{
    public function __construct(private mysqli $db, private int $pageSize = 500)
    {
        if ($pageSize < 1 || $pageSize > 5000) throw new InvalidArgumentException('Page size must be between 1 and 5000');
    }

    public function profile(): array
    {
        $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
        try {
            $profile = LegacyObjectProfile::aggregate($this->rows());
            $this->db->commit();
            return $profile + ['source' => 'legacy_fmonitor', 'mode' => 'read_only_dry_run', 'pageSize' => $this->pageSize];
        } catch (Throwable $error) {
            try { $this->db->rollback(); } catch (Throwable) {}
            throw $error;
        }
    }

    /** @return Generator<int,array<string,mixed>> */
    private function rows(): Generator
    {
        $sql = <<<'SQL'
SELECT m.id,m.ordadr_address,m.entrance,m.regnumber,m.factworkstartdate,m.ptoactdate,m.object_status,m.fact_percent,m.workstarted
FROM fm_maintable m
WHERE m.id>? ORDER BY m.id LIMIT ?
SQL;
        $after = 0;
        $statement = $this->db->prepare($sql);
        do {
            $statement->bind_param('ii', $after, $this->pageSize);
            $statement->execute();
            $batch = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            if ($batch !== []) {
                $first = (int)$batch[0]['id']; $last = (int)$batch[array_key_last($batch)]['id'];
                $events = $this->counts('SELECT value_id object_id,COUNT(*) n FROM fm_install_checklists_values_log WHERE value_id BETWEEN ? AND ? GROUP BY value_id', $first, $last);
                $attributions = $this->counts('SELECT v.value_id object_id,COUNT(*) n FROM fm_install_checklists_values_installators_log ai JOIN fm_install_checklists_values v ON v.id=ai.checklist_value_id WHERE v.value_id BETWEEN ? AND ? GROUP BY v.value_id', $first, $last);
                foreach ($batch as $row) {
                    $after = (int)$row['id'];
                    $row['checklist_event_count'] = $events[$after] ?? 0;
                    $row['attribution_count'] = $attributions[$after] ?? 0;
                    yield $row;
                }
            }
        } while (count($batch) === $this->pageSize);
    }

    /** @return array<int,int> */
    private function counts(string $sql, int $first, int $last): array
    {
        $statement = $this->db->prepare($sql); $statement->bind_param('ii', $first, $last); $statement->execute();
        $counts = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $counts[(int)$row['object_id']] = (int)$row['n'];
        return $counts;
    }
}
