<?php

declare(strict_types=1);

final class LegacyHistorySnapshot
{
    public const EXTRACTOR_VERSION = 'history-import-001-v1';

    /** @param array<string,mixed> $object @param list<array<string,mixed>> $events @param list<array<string,mixed>> $attributions */
    public static function build(array $object, array $events, array $attributions, string $cutoff): array
    {
        self::date($cutoff, 'cutoff');
        $issues = [];
        foreach ($events as $index => $row) {
            try { self::date((string)($row['ctime'] ?? ''), "events[{$index}].ctime"); }
            catch (InvalidArgumentException $e) { $issues[] = ['code' => 'MALFORMED_EVENT_DATE', 'record' => $index, 'detail' => $e->getMessage()]; }
            if (($row['checklist_definition_id'] ?? null) === null) $issues[] = ['code' => 'ORPHAN_CHECKLIST_EVENT', 'record' => $index];
        }
        foreach ($attributions as $index => $row) {
            try { self::date((string)($row['ctime'] ?? ''), "attributions[{$index}].ctime"); }
            catch (InvalidArgumentException $e) { $issues[] = ['code' => 'MALFORMED_ATTRIBUTION_DATE', 'record' => $index, 'detail' => $e->getMessage()]; }
            if (($row['checklist_value_id'] ?? null) === null) $issues[] = ['code' => 'ORPHAN_ATTRIBUTION', 'record' => $index];
        }
        usort($events, self::order(...));
        usort($attributions, self::order(...));
        $payload = self::canonical([
            'extractorVersion' => self::EXTRACTOR_VERSION,
            'cutoff' => $cutoff,
            'object' => $object,
            'checklistEvents' => $events,
            'attributions' => $attributions,
        ]);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return ['payload' => $payload, 'contentSha256' => hash('sha256', $json), 'issues' => $issues,
            'counts' => ['objects' => 1, 'checklistEvents' => count($events), 'attributions' => count($attributions), 'quarantined' => count($issues)]];
    }

    private static function date(string $value, string $field): void
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if (!$parsed || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) || $parsed->format('Y-m-d H:i:s') !== $value) {
            throw new InvalidArgumentException("{$field} is not an exact Y-m-d H:i:s timestamp");
        }
    }

    private static function order(array $a, array $b): int
    {
        return [(string)($a['ctime'] ?? ''), (int)($a['id'] ?? 0)] <=> [(string)($b['ctime'] ?? ''), (int)($b['id'] ?? 0)];
    }

    private static function canonical(mixed $value): mixed
    {
        if (!is_array($value)) return $value;
        if (!array_is_list($value)) ksort($value, SORT_STRING);
        foreach ($value as $key => $item) $value[$key] = self::canonical($item);
        return $value;
    }
}

final class LegacyHistoryMySqlSource
{
    private const OBJECT_SQL = <<<'SQL'
SELECT id, ordadr_address, entrance, regnumber, workdatestart, workdatefinish,
       workdateendadjusted, factworkstartdate, ptoactdate, declarations,
       installator, installator2, installator3, installator4
FROM fm_maintable WHERE id = ? LIMIT 1
SQL;
    private const EVENTS_SQL = <<<'SQL'
SELECT l.id, l.value_id, l.checklist_id, l.value, l.ctime, l.cuser_id,
       c.id AS checklist_definition_id, c.part_id, c.share
FROM fm_install_checklists_values_log l
LEFT JOIN fm_install_checklist c ON c.id = l.checklist_id
WHERE l.value_id = ? AND l.ctime <= ? ORDER BY l.ctime, l.id
SQL;
    private const ATTRIBUTIONS_SQL = <<<'SQL'
SELECT a.id, a.checklist_value_id, a.tab_id, a.fio, a.ctime, a.cuser_id
FROM fm_install_checklists_values_installators_log a
JOIN fm_install_checklists_values v ON v.id = a.checklist_value_id
WHERE v.value_id = ? AND a.ctime <= ? ORDER BY a.ctime, a.id
SQL;

    public function __construct(private mysqli $db) {}

    public function extract(int $objectId, string $cutoff): array
    {
        $object = $this->one(self::OBJECT_SQL, 'i', [$objectId]);
        if ($object === null) throw new RuntimeException("Legacy object {$objectId} was not found");
        return LegacyHistorySnapshot::build($object, $this->many(self::EVENTS_SQL, 'is', [$objectId, $cutoff]), $this->many(self::ATTRIBUTIONS_SQL, 'is', [$objectId, $cutoff]), $cutoff);
    }

    private function one(string $sql, string $types, array $values): ?array { return $this->many($sql, $types, $values)[0] ?? null; }
    private function many(string $sql, string $types, array $values): array
    {
        $statement = $this->db->prepare($sql);
        $statement->bind_param($types, ...$values);
        $statement->execute();
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

final class LegacyHistoryMySqlTarget
{
    public function __construct(private mysqli $db, private string $prefix)
    {
        if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new InvalidArgumentException('Invalid local table prefix');
    }

    public function createSchema(): void
    {
        $p = $this->prefix;
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_history_source_snapshots` (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,legacy_object_id BIGINT UNSIGNED NOT NULL,source_system VARCHAR(40) NOT NULL,source_locator VARCHAR(160) NOT NULL,cutoff_at DATETIME NOT NULL,extractor_version VARCHAR(80) NOT NULL,content_sha256 CHAR(64) NOT NULL,payload_json LONGTEXT NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_content(content_sha256),KEY object_id(legacy_object_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_history_import_quarantine` (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NOT NULL,issue_no INT UNSIGNED NOT NULL,code VARCHAR(80) NOT NULL,diagnostic_json LONGTEXT NOT NULL,UNIQUE KEY uq_issue(snapshot_id,issue_no)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function apply(array $snapshot, int $objectId, string $cutoff, string $now): array
    {
        $this->createSchema();
        $json = json_encode($snapshot['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $hash = (string)$snapshot['contentSha256'];
        $this->db->begin_transaction();
        try {
            $sql = "INSERT IGNORE INTO `{$this->prefix}fm2_history_source_snapshots`(legacy_object_id,source_system,source_locator,cutoff_at,extractor_version,content_sha256,payload_json,created_at) VALUES(?,'legacy_fmonitor','fm_maintable+checklist_logs',?,?,?, ?,?)";
            $statement = $this->db->prepare($sql);
            $version = LegacyHistorySnapshot::EXTRACTOR_VERSION;
            $statement->bind_param('isssss', $objectId, $cutoff, $version, $hash, $json, $now);
            $statement->execute();
            $created = $statement->affected_rows === 1;
            $lookup = $this->db->prepare("SELECT id FROM `{$this->prefix}fm2_history_source_snapshots` WHERE content_sha256=?");
            $lookup->bind_param('s', $hash); $lookup->execute();
            $snapshotId = (int)$lookup->get_result()->fetch_assoc()['id'];
            $issueInsert = $this->db->prepare("INSERT IGNORE INTO `{$this->prefix}fm2_history_import_quarantine`(snapshot_id,issue_no,code,diagnostic_json) VALUES(?,?,?,?)");
            foreach ($snapshot['issues'] as $i => $issue) {
                $number = $i + 1; $code = (string)$issue['code']; $diagnostic = json_encode($issue, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $issueInsert->bind_param('iiss', $snapshotId, $number, $code, $diagnostic); $issueInsert->execute();
            }
            $this->db->commit();
            return ['snapshotId' => $snapshotId, 'created' => $created];
        } catch (Throwable $e) { $this->db->rollback(); throw $e; }
    }
}
