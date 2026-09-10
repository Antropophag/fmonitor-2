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
        $prepare = $this->can($actor, 'assignment_order.prepare') || $this->can($actor, 'assignment_order.confirm_registration');
        $open = $this->can($actor, 'installation.open');
        foreach ($objects as &$object) {
            if ($object['status'] === 'Требуется распоряжение' && $prepare) {
                $object['nextStep'] = 'Загрузить оригинал распоряжения';
            } elseif ($object['status'] === 'Готов к открытию' && $open) {
                $object['nextStep'] = 'Открыть работы';
            } elseif ($object['status'] === 'В работе') {
                $this->working($object, $latest[$object['caseId']] ?? [], $types[$object['caseId']] ?? []);
            }
        }
        return$objects;
    }
    private function working(array&$object, array$latest, array$facts): void
    {
        $progress = 0;
        foreach ($latest as $item => $state) {
            if ($state === 'item_completed') {
                $progress += self::WEIGHTS[$item] ?? 0;
            }
        }$progress = min(85, $progress);
        $pto = isset($facts['pto_act']);
        $done = $pto && isset($facts['declaration']);
        $object['completionProgress'] = $done ? 100 : $progress;
        $object['status'] = $done ? 'Работы завершены' : ($progress >= 85 ? 'Документарное закрытие' : 'Монтажные работы');
        $object['nextStep'] = $done ? 'Монтаж закрыт актом ПТО и декларацией' : ($progress < 85 ? 'Продолжить монтажные работы' : ($pto ? 'Добавить декларацию' : 'Зафиксировать дату акта ПТО'));
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
