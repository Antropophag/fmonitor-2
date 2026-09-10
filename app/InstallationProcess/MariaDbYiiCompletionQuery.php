<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiCompletionQuery
{
    public function __construct(private Connection $db, private string $prefix)
    {
        if (strlen($prefix) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    /** @return array{pto_act: ?array, declaration: ?array} */
    public function read(int $objectId): array
    {
        $this->db->createCommand("SELECT 1 FROM `{$this->prefix}fm2_pilot_completion_fact_corrections` LIMIT 0")->queryAll();
        $roots = $this->db->createCommand(
            "SELECT f.id,f.fact_type,f.fact_date,f.details,f.recorded_at,f.recorded_by_user_id " .
            "FROM `{$this->prefix}fm2_installation_cases` c " .
            "JOIN `{$this->prefix}fm2_pilot_completion_facts` f ON f.installation_case_id=c.id " .
            "WHERE c.legacy_installation_object_id=:object ORDER BY f.id",
            [':object' => $objectId],
        )->queryAll();
        $result = ['pto_act' => null, 'declaration' => null];
        foreach ($roots as $root) {
            $type = (string) $root['fact_type'];
            if (!array_key_exists($type, $result) || $result[$type] !== null) {
                throw new \RuntimeException('Malformed completion facts.');
            }
            $history = [$this->row($root, 0, null, (string) $root['fact_date'], (string) $root['details'])];
            $effectiveDetails = (string) $root['details'];
            $corrections = $this->db->createCommand(
                "SELECT id,version_no,previous_correction_id,previous_version_no,fact_date,details,reason,recorded_at,recorded_by_user_id " .
                "FROM `{$this->prefix}fm2_pilot_completion_fact_corrections` WHERE root_fact_id=:root ORDER BY version_no",
                [':root' => (int) $root['id']],
            )->queryAll();
            $previousId = null;
            foreach ($corrections as $index => $correction) {
                $version = $index + 1;
                if ((int) $correction['version_no'] !== $version
                    || ($version === 1 && ($correction['previous_correction_id'] !== null || $correction['previous_version_no'] !== null))
                    || ($version > 1 && ((int) $correction['previous_correction_id'] !== $previousId || (int) $correction['previous_version_no'] !== $version - 1))) {
                    throw new \RuntimeException('Malformed completion history.');
                }
                if ($correction['details'] !== null) $effectiveDetails = (string) $correction['details'];
                $history[] = $this->row($correction, $version, (string) $correction['reason'], (string) $correction['fact_date'], $effectiveDetails);
                $previousId = (int) $correction['id'];
            }
            $last = $history[array_key_last($history)];
            $result[$type] = ['id' => (int) $root['id'], 'date' => $last['date'], 'details' => $last['details'], 'history' => $history];
        }
        return $result;
    }

    private function row(array $row, int $version, ?string $reason, string $date, string $details): array
    {
        $actor = (int) $row['recorded_by_user_id'];
        $name = $this->db->createCommand(
            "SELECT full_name FROM `{$this->prefix}fm2_pilot_users` WHERE user_id=:actor LIMIT 1",
            [':actor' => $actor],
        )->queryScalar();
        return [
            'version' => $version,
            'date' => $date,
            'details' => $details,
            'reason' => $reason,
            'recordedAt' => (string) $row['recorded_at'],
            'actorId' => $actor,
            'actorName' => is_string($name) && trim($name) !== '' ? trim($name) : 'Пользователь недоступен',
        ];
    }
}
