<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiObjectQueueProjection
{
    private const WEIGHTS = [28 => 2,29 => 2,30 => 2,31 => 2,32 => 1,33 => 1,34 => 2,35 => 1,36 => 2,37 => 3,38 => 3,39 => 3,40 => 3,41 => 2,1 => 2,2 => 2,3 => 1,4 => 2,5 => 1,6 => 1,7 => 2,8 => 5,9 => 1,10 => 1,11 => 3,12 => 2,13 => 2,14 => 1,15 => 2,16 => 4,17 => 4,18 => 3,19 => 3,20 => 3,21 => 2,22 => 3,23 => 2,24 => 1,25 => 1,26 => 1,27 => 1];
    public function __construct(private Connection$db, private string$prefix)
    {
    }
    public function decorate(array $objects, int $actor): array
    {
        if ($objects === []) {
            return[];
        }
        [$marks,$params] = $this->parameters(array_column($objects, 'caseId'));
        $operations = $this->db->createCommand("SELECT installation_case_id,item_id,operation_type FROM `{$this->prefix}fm2_checklist_operations` WHERE installation_case_id IN({$marks}) AND operation_type IN('item_completed','completion_retracted') ORDER BY installation_case_id,item_id,accepted_revision,id", $params)->queryAll();
        $latest = [];
        foreach ($operations as $row) {
            $latest[(int)$row['installation_case_id']][(int)$row['item_id']] = $row['operation_type'];
        }
        $facts = $this->db->createCommand("SELECT installation_case_id,fact_type FROM `{$this->prefix}fm2_pilot_completion_facts` WHERE installation_case_id IN({$marks})", $params)->queryAll();
        $types = [];
        foreach ($facts as $row) {
            $types[(int)$row['installation_case_id']][$row['fact_type']] = true;
        }
        $installerCounts = $this->installerCounts(array_column($objects, 'caseId'));
        $prepare = $this->can($actor, 'assignment_order.prepare') || $this->can($actor, 'assignment_order.confirm_registration');
        $open = $this->can($actor, 'installation.open');
        foreach ($objects as &$object) {
            $object['installerCount'] = $installerCounts[(int) $object['caseId']] ?? 0;
            if ($object['status'] === 'Требуется распоряжение' && $prepare) {
                $object['nextStep'] = 'Загрузить оригинал распоряжения';
            } elseif ($object['status'] === 'Готов к открытию' && $open) {
                $object['nextStep'] = 'Открыть работы';
            } elseif ($object['status'] === 'В работе') {
                $this->working($object, $latest[$object['caseId']] ?? [], $types[$object['caseId']] ?? []);
            } elseif ($object['status'] === 'Требуется изменение') {
                $object['completionProgress'] = $this->progress($latest[$object['caseId']] ?? [], $types[$object['caseId']] ?? []);
            }
        }
        return$objects;
    }
    /** @param list<int|string> $caseIds @return array<int,int> Counts keyed by installation case ID. */
    public function installerCounts(array $caseIds): array
    {
        if ($caseIds === []) {
            return [];
        }
        [$marks, $params] = $this->parameters($caseIds);
        $applications = $this->db->createCommand(
            "SELECT a.* FROM `{$this->prefix}fm2_assignment_order_applications` a "
            . "WHERE a.installation_case_id IN({$marks}) AND a.application_sequence=(SELECT MAX(x.application_sequence) "
            . "FROM `{$this->prefix}fm2_assignment_order_applications` x WHERE x.installation_case_id=a.installation_case_id)",
            $params,
        )->queryAll();
        $counts = [];
        foreach ($applications as $application) {
            $caseId = (int) $application['installation_case_id'];
            if (isset($counts[$caseId])) throw new \RuntimeException('Multiple current assignment applications.');
            $selected = json_decode((string) $application['selected_snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($selected) || array_keys($selected) !== ['selectedInstallers', 'selectedEngineer']) {
                throw new \RuntimeException('Malformed applied composition.');
            }
            YiiObjectCardApplicationIntegrity::validateComposition($application, $selected);
            $counts[$caseId] = count($selected['selectedInstallers']);
        }
        $day = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d');
        $registered = $this->db->createCommand(
            "SELECT o.installation_case_id,COUNT(*) installer_count FROM `{$this->prefix}fm2_assignment_orders` o "
            . "JOIN `{$this->prefix}fm2_order_installers` oi ON oi.assignment_order_id=o.id "
            . "WHERE o.installation_case_id IN({$marks}) AND o.status='registered' "
            . "AND o.version_no=(SELECT MAX(x.version_no) FROM `{$this->prefix}fm2_assignment_orders` x WHERE x.installation_case_id=o.installation_case_id) "
            . "AND NOT EXISTS(SELECT 1 FROM `{$this->prefix}fm2_assignment_order_applications` a WHERE a.installation_case_id=o.installation_case_id) "
            . "AND oi.change_action IN('assign','retain') AND oi.valid_from<=:installerDay "
            . "AND (oi.valid_to IS NULL OR oi.valid_to>=:installerDay) GROUP BY o.installation_case_id",
            $params + [':installerDay' => $day],
        )->queryAll();
        foreach ($registered as $row) $counts[(int) $row['installation_case_id']] = (int) $row['installer_count'];
        return $counts;
    }
    private function working(array&$object, array$latest, array$facts): void
    {
        $progress = $this->progress($latest, $facts);
        $pto = isset($facts['pto_act']);
        $done = $pto && isset($facts['declaration']);
        $object['completionProgress'] = $progress;
        $object['status'] = $done ? 'Работы завершены' : ($progress >= 85 ? 'Документарное закрытие' : 'Монтажные работы');
        $object['nextStep'] = $done ? 'Монтаж закрыт актом ПТО и декларацией' : ($progress < 85 ? 'Продолжить монтажные работы' : ($pto ? 'Добавить декларацию' : 'Зафиксировать дату акта ПТО'));
    }
    private function progress(array $latest, array $facts): int
    {
        $progress = 0;
        foreach ($latest as $item => $state) {
            if ($state === 'item_completed') $progress += self::WEIGHTS[$item] ?? 0;
        }
        $progress = min(85, $progress);
        return isset($facts['pto_act'], $facts['declaration']) ? 100 : $progress;
    }
    private function can(int$actor, string$permission): bool
    {
        $p = $this->prefix;
        $sql = "SELECT 1 FROM `{$p}fm2_pilot_user_roles` ur JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE ur.user_id=:id AND r.status=1 AND BINARY rp.permission=:permission LIMIT 1";
        return(bool)$this->db->createCommand($sql, [':id' => $actor,':permission' => $permission])->queryScalar();
    }
    private function parameters(array$ids): array
    {
        $marks = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = ':case' . $i;
            $marks[] = $key;
            $params[$key] = (int)$id;
        }return[implode(',',$marks),$params];
    }
}
