<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

final class PilotCaseSchemaUnavailable extends \RuntimeException {}
final class PilotCaseCommitOutcomeUnknown extends \RuntimeException
{
    /** @param list<int>|null $expectedNewIds @param list<int>|null $alreadyPresent */
    public function __construct(
        public readonly ?array $expectedNewIds = null,
        public readonly ?array $alreadyPresent = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct('', 0, $previous);
    }
}

final class PilotCaseImporter
{
    private const LEGACY_COLUMNS = [
        'id', 'ordadr_address', 'entrance', 'regnumber', 'workdatestart',
        'workdateendadjusted', 'plan_finish_date', 'workdatefinish', 'ptoactdate',
    ];

    public function __construct(
        private readonly \mysqli $connection,
        private readonly string $processPrefix,
        private readonly string $legacyPrefix,
    ) {
        MariaDbSchemaInspector::validateTablePrefix($processPrefix);
        MariaDbSchemaInspector::validateTablePrefix($legacyPrefix);
    }

    public function assertSchemaAvailable(): void
    {
        if (!ProductionProcessSchemaMigration::isInstallationCasesCompatible($this->connection, $this->processPrefix)) {
            throw new PilotCaseSchemaUnavailable();
        }
        $table = $this->legacyPrefix . 'fm_maintable';
        if (!MariaDbSchemaInspector::tableExists($this->connection, $table)) {
            throw new PilotCaseSchemaUnavailable();
        }
        $present = array_column(MariaDbSchemaInspector::columns($this->connection, $table), 'COLUMN_NAME');
        foreach (self::LEGACY_COLUMNS as $column) {
            if (!in_array($column, $present, true)) {
                throw new PilotCaseSchemaUnavailable();
            }
        }
    }

    /** @param list<int> $selected */
    public function import(array $selected, string $timestamp): array
    {
        $this->connection->begin_transaction();
        try {
            $orderedForLocks = $selected;
            sort($orderedForLocks, SORT_NUMERIC);
            $existing = $this->existingIds($orderedForLocks, true);
            $new = array_values(array_diff($selected, $existing));
            $legacyRows = $this->legacyRows($new);
            $rejected = $this->rejections($new, $legacyRows);
            if ($rejected !== []) {
                $this->connection->rollback();
                return ['rejected' => $rejected];
            }

            $statement = $this->connection->prepare(
                "INSERT INTO `{$this->processPrefix}fm2_installation_cases` "
                . '(legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) '
                . "VALUES (?,'needs_assignment_order',NULL,NULL,NULL,?,?,1)",
            );
            foreach ($new as $id) {
                $statement->bind_param('iss', $id, $timestamp, $timestamp);
                $statement->execute();
            }
        } catch (\Throwable $error) {
            try {
                $this->connection->rollback();
            } catch (\Throwable $rollbackError) {
                throw new PilotCaseCommitOutcomeUnknown(previous: $rollbackError);
            }
            throw $error;
        }

        try {
            $this->connection->commit();
        } catch (\Throwable $error) {
            throw new PilotCaseCommitOutcomeUnknown(
                $new,
                array_values(array_intersect($selected, $existing)),
                $error,
            );
        }

        return ['imported' => $new, 'alreadyPresent' => array_values(array_intersect($selected, $existing))];
    }

    /** @param list<int> $ids @return list<int> */
    private function existingIds(array $ids, bool $lock): array
    {
        if ($ids === []) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->connection->prepare(
            "SELECT legacy_installation_object_id FROM `{$this->processPrefix}fm2_installation_cases` "
            . "WHERE legacy_installation_object_id IN ({$placeholders}) ORDER BY legacy_installation_object_id"
            . ($lock ? ' FOR UPDATE' : ''),
        );
        $types = str_repeat('i', count($ids));
        $statement->bind_param($types, ...$ids);
        $statement->execute();
        return array_map('intval', array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), 'legacy_installation_object_id'));
    }

    /** @param list<int> $ids @return array<int, array<string, mixed>> */
    private function legacyRows(array $ids): array
    {
        if ($ids === []) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->connection->prepare(
            'SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,workdatefinish,ptoactdate '
            . "FROM `{$this->legacyPrefix}fm_maintable` WHERE id IN ({$placeholders}) ORDER BY id FOR UPDATE",
        );
        $types = str_repeat('i', count($ids));
        $statement->bind_param($types, ...$ids);
        $statement->execute();
        $rows = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $rows[(int) $row['id']] = $row;
        return $rows;
    }

    /** @param list<int> $ids @param array<int, array<string, mixed>> $rows */
    private function rejections(array $ids, array $rows): array
    {
        $rejected = [];
        foreach ($ids as $id) {
            if (!isset($rows[$id])) {
                $rejected[] = ['installationObjectId' => $id, 'reasonCodes' => ['LEGACY_OBJECT_NOT_FOUND']];
                continue;
            }
            $row = $rows[$id];
            $start = self::date($row['workdatestart']);
            $adjustedFinish = self::date($row['workdateendadjusted']);
            $finish = $adjustedFinish ?? self::date($row['plan_finish_date']);
            $pto = self::date($row['ptoactdate']);
            $completed = self::date($row['workdatefinish']);
            $reasons = [];
            if (trim((string) $row['ordadr_address']) === ''
                || trim((string) $row['entrance']) === ''
                || trim((string) $row['regnumber']) === ''
                || $start === null || $finish === null
            ) $reasons[] = 'LEGACY_OBJECT_REQUIRED_DATA_MISSING';
            if ($start !== null && $start < '2026-10-01') $reasons[] = 'PILOT_PLANNED_START_BEFORE_CUTOFF';
            if ($pto !== null) $reasons[] = 'ORDER_HAS_PTO_ACT';
            if ($completed !== null) $reasons[] = 'LEGACY_INSTALLATION_ALREADY_COMPLETED';
            if ($reasons !== []) $rejected[] = ['installationObjectId' => $id, 'reasonCodes' => $reasons];
        }
        return $rejected;
    }

    private static function date(mixed $value): ?string
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^0+$/D', $value) === 1 || str_starts_with($value, '0000-00-00')) return null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\D|$)/D', $value, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new PilotCaseSchemaUnavailable();
        }
        return $parts[1] . '-' . $parts[2] . '-' . $parts[3];
    }

    /** @param list<int> $ids */
    public function reconciles(array $ids, string $timestamp): bool
    {
        if ($ids === []) return true;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->connection->prepare(
            "SELECT legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version FROM `{$this->processPrefix}fm2_installation_cases` WHERE legacy_installation_object_id IN ({$placeholders})",
        );
        $types = str_repeat('i', count($ids));
        $statement->bind_param($types, ...$ids);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($rows) !== count($ids)) return false;
        foreach ($rows as $row) {
            if ($row['process_state'] !== 'needs_assignment_order'
                || $row['actual_start_date'] !== null || $row['opened_at'] !== null || $row['opened_by_user_id'] !== null
                || $row['created_at'] !== $timestamp || $row['updated_at'] !== $timestamp || (int) $row['lock_version'] !== 1
            ) return false;
        }
        return true;
    }
}
