<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class LegacySourceSnapshot
{
    public function __construct(private readonly \mysqli $source) {}

    /** @return array{objects:list<array<string,mixed>>,template:array<string,mixed>} */
    public function read(string $cutoff): array
    {
        $this->source->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->source->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
        try {
            $statement = $this->source->prepare("SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdatestartadjusted,workdateendadjusted,plan_finish_date,workdatefinish,CASE WHEN ptoactdate<=? THEN ptoactdate ELSE NULL END ptoactdate,responsstroicontrol,CASE WHEN factworkstartdate<=? THEN factworkstartdate ELSE NULL END factworkstartdate,object_status,fact_percent,workstarted,floors,weight,speed,pittype,pitmaterial,paired FROM fm_maintable ORDER BY id");
            $statement->bind_param('ss', $cutoff, $cutoff);
            $statement->execute();
            $objects = [];
            foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                $id = (int) $row['id'];
                $events = $this->count('SELECT COUNT(*) FROM fm_install_checklists_values_log WHERE value_id=? AND ctime<=?', $id, $cutoff);
                $attributions = $this->count('SELECT COUNT(*) FROM fm_install_checklists_values_installators_log a JOIN fm_install_checklists_values v ON v.id=a.checklist_value_id WHERE v.value_id=? AND a.ctime<=?', $id, $cutoff);
                $row['checklist_event_count'] = $events;
                $row['attribution_count'] = $attributions;
                $classification = LegacyImportRouting::classify($row);
                if (!LegacyImportRouting::importsOperationalCase($classification)) continue;
                try {
                    $start = self::date($row['workdatestart']);
                    $adjustedFinish = self::date($row['workdateendadjusted']);
                    $plannedFinish = $adjustedFinish ?? self::date($row['plan_finish_date']);
                    $completed = self::date($row['workdatefinish']);
                } catch (\InvalidArgumentException) {
                    continue;
                }
                $row['ordadr_address'] = trim((string) $row['ordadr_address']);
                $row['entrance'] = trim((string) $row['entrance']);
                $row['regnumber'] = trim((string) $row['regnumber']);
                if ($row['ordadr_address'] === '' || $row['entrance'] === '' || $row['regnumber'] === ''
                    || $start === null || $plannedFinish === null || $start < '2026-10-01' || $completed !== null) continue;
                $row['workdatestart'] = $start;
                $row['workdateendadjusted'] = $adjustedFinish;
                $row['plan_finish_date'] = $plannedFinish;
                $row['workdatefinish'] = null;
                $row['classification'] = $classification;
                $objects[] = $row;
            }
            $parts = $this->source->query('SELECT id,name,rang FROM fm_install_checklist_parts ORDER BY rang,id')->fetch_all(MYSQLI_ASSOC);
            $definitions = $this->source->query('SELECT id,part_id,name,share,rang,needphoto FROM fm_install_checklist ORDER BY part_id,rang,id')->fetch_all(MYSQLI_ASSOC);
            $this->source->commit();
            return ['objects' => $objects, 'template' => self::template($parts, $definitions, $cutoff)];
        } catch (\Throwable $error) {
            try { $this->source->rollback(); } catch (\Throwable) {}
            throw $error;
        }
    }

    private function count(string $sql, int $id, string $cutoff): int
    {
        $statement = $this->source->prepare($sql);
        $statement->bind_param('is', $id, $cutoff);
        $statement->execute();
        return (int) $statement->get_result()->fetch_column();
    }

    private static function date(mixed $raw): ?string
    {
        if ($raw === null) return null;
        $value = trim((string) $raw);
        if ($value === '' || preg_match('/^0+$/D', $value) === 1 || str_starts_with($value, '0000-00-00')) return null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}:\d{2})?$/D', $value, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) throw new \InvalidArgumentException('Malformed legacy date.');
        return $parts[1] . '-' . $parts[2] . '-' . $parts[3];
    }

    private static function template(array $parts, array $definitions, string $cutoff): array
    {
        $payload = ['snapshotVersion' => 'legacy-checklist-template-cutover-v1', 'capturedAt' => $cutoff, 'validFrom' => $cutoff, 'validity' => 'current_at_cutover_for_active_baselines_and_future_native_events', 'source' => 'legacy_fmonitor.fm_install_checklist+parts', 'parts' => $parts, 'definitions' => $definitions];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return ['payload' => $payload, 'contentSha256' => hash('sha256', $json)];
    }
}
