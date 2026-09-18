<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbLegacySourceSnapshot
{
    public function __construct(private readonly \mysqli $source) {}

    /** @return array{objects:list<array<string,mixed>>,engineers:list<array<string,mixed>>,template:array<string,mixed>} */
    public function read(string $cutoff): array
    {
        $this->source->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->source->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
        try {
            $statement = $this->source->prepare("SELECT id,ordadr_address,entrance,regnumber,zavnumber,workdatestart,workdatestartadjusted,workdateendadjusted,plan_finish_date,CASE WHEN workdatefinish<=? THEN workdatefinish ELSE NULL END workdatefinish,CASE WHEN ptoactdate<=? THEN ptoactdate ELSE NULL END ptoactdate,responsstroicontrol,CASE WHEN factworkstartdate<=? THEN factworkstartdate ELSE NULL END factworkstartdate,object_status,fact_percent,workstarted,floors,weight,speed,pittype,pitmaterial,paired FROM fm_maintable ORDER BY id");
            $statement->bind_param('sss', $cutoff, $cutoff, $cutoff);
            $statement->execute();
            $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $eventCounts = $this->countsByObject('SELECT value_id object_id,COUNT(*) fact_count FROM fm_install_checklists_values_log WHERE ctime<=? GROUP BY value_id', $cutoff);
            $attributionCounts = $this->countsByObject('SELECT v.value_id object_id,COUNT(*) fact_count FROM fm_install_checklists_values_installators_log a JOIN fm_install_checklists_values v ON v.id=a.checklist_value_id WHERE a.ctime<=? GROUP BY v.value_id', $cutoff);
            $objects = [];
            foreach ($rows as $row) {
                $id = (int) $row['id'];
                $row['checklist_event_count'] = $eventCounts[$id] ?? 0;
                $row['attribution_count'] = $attributionCounts[$id] ?? 0;
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
                    || $start === null || $plannedFinish === null || $completed !== null) continue;
                $row['workdatestart'] = $start;
                $row['workdateendadjusted'] = $adjustedFinish;
                $row['plan_finish_date'] = $plannedFinish;
                $row['workdatefinish'] = null;
                $row['classification'] = $classification;
                $objects[] = $row;
            }
            $referenced = [];
            foreach ($objects as $object) {
                $raw = $object['responsstroicontrol'];
                if ($raw === null || (string)$raw === '' || (string)$raw === '0') continue;
                if (filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) === false) throw new \DomainException('ENGINEER_REFERENCE_INVALID');
                $referenced[(int)$raw] = true;
            }
            $engineers = [];
            if ($referenced !== []) {
                $ids = array_keys($referenced);
                $marks = implode(',', array_fill(0, count($ids), '?'));
                $query = $this->source->prepare("SELECT u.id,u.name,u.email,u.status,u.role_id,r.status role_status FROM users u JOIN users_roles r ON r.id=u.role_id WHERE u.id IN ({$marks}) ORDER BY u.id");
                $query->execute($ids);
                foreach ($query->get_result()->fetch_all(MYSQLI_ASSOC) as $engineer) $engineers[(int)$engineer['id']] = $engineer;
                foreach ($ids as $id) {
                    $engineer = $engineers[$id] ?? null;
                    if ($engineer === null || (int)$engineer['status'] !== 1 || (int)$engineer['role_status'] !== 1 || !in_array((int)$engineer['role_id'], [16,18], true)
                        || trim((string)$engineer['name']) === '' || filter_var(trim((string)$engineer['email']), FILTER_VALIDATE_EMAIL) === false) {
                        throw new \DomainException('ENGINEER_IDENTITY_INVALID');
                    }
                }
                $engineers = array_values($engineers);
            }
            $parts = $this->source->query('SELECT id,name,rang FROM fm_install_checklist_parts ORDER BY rang,id')->fetch_all(MYSQLI_ASSOC);
            $definitions = $this->source->query('SELECT id,part_id,name,share,rang,needphoto FROM fm_install_checklist ORDER BY part_id,rang,id')->fetch_all(MYSQLI_ASSOC);
            $this->source->commit();
            return ['objects' => $objects, 'engineers' => $engineers, 'template' => self::template($parts, $definitions, $cutoff)];
        } catch (\Throwable $error) {
            try { $this->source->rollback(); } catch (\Throwable) {}
            throw $error;
        }
    }

    /** @return array<int,int> */
    private function countsByObject(string $sql, string $cutoff): array
    {
        $statement = $this->source->prepare($sql);
        $statement->bind_param('s', $cutoff);
        $statement->execute();
        $counts = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $counts[(int) $row['object_id']] = (int) $row['fact_count'];
        }
        return $counts;
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
