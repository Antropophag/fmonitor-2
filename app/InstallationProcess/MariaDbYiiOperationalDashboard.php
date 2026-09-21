<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiOperationalDashboard
{
    public function __construct(private Connection $db, private string $prefix, private string $legacyPrefix)
    {
        foreach ([$prefix, $legacyPrefix] as $value) {
            if (strlen($value) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $value) !== 1) {
                throw new \InvalidArgumentException('Invalid table prefix.');
            }
        }
    }

    public function authorized(int $actorId): bool
    {
        $p = $this->prefix;
        $sql = "SELECT 1 FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=:id AND u.status=1 AND u.activation_state='active' AND r.status=1 AND BINARY rp.permission='objects.read' LIMIT 1";
        return (bool) $this->db->createCommand($sql, [':id' => $actorId])->queryScalar();
    }

    public function read(string $cutoff, string $windowEnd, array $weeks): array
    {
        [$from, $active, $completed, $start, $finish, $stages] = $this->source();
        $params = [':cutoff' => $cutoff, ':windowEnd' => $windowEnd];
        $metrics = $this->db->createCommand(
            "SELECT COUNT(*) total,COALESCE(SUM({$active}),0) active,COALESCE(SUM(NOT({$completed}) AND {$finish} IS NOT NULL AND {$finish}<:cutoff),0) overdue,COALESCE(SUM({$start} BETWEEN :cutoff AND :windowEnd),0) upcoming{$from}",
            $params,
        )->queryOne();
        if ($metrics === false) throw new \RuntimeException('Dashboard aggregate unavailable.');

        $upcoming = $this->db->createCommand(
            "SELECT c.legacy_installation_object_id object_id,l.regnumber,{$start} relevant_date{$from} AND {$start} BETWEEN :cutoff AND :windowEnd ORDER BY {$start},BINARY COALESCE(l.regnumber,''),c.legacy_installation_object_id LIMIT 5",
            $params,
        )->queryAll();
        $overdue = $this->db->createCommand(
            "SELECT c.legacy_installation_object_id object_id,l.regnumber,{$finish} relevant_date{$from} AND NOT({$completed}) AND {$finish} IS NOT NULL AND {$finish}<:cutoff ORDER BY {$finish},BINARY COALESCE(l.regnumber,''),c.legacy_installation_object_id LIMIT 5",
            [':cutoff' => $cutoff],
        )->queryAll();

        $stageColumns = [];
        foreach ($stages as $key => $predicate) {
            $stageColumns[] = "COALESCE(SUM({$predicate}),0) `{$key}`";
        }
        $stageRow = $this->db->createCommand('SELECT ' . implode(',', $stageColumns) . $from)->queryOne();
        if ($stageRow === false) throw new \RuntimeException('Dashboard stage aggregate unavailable.');

        $weekColumns = [];
        $weekParams = [];
        foreach ($weeks as $index => [$weekStart, $weekEnd]) {
            $weekColumns[] = "COALESCE(SUM({$start} BETWEEN :ws{$index} AND :we{$index}),0) starts_{$index}";
            $weekColumns[] = "COALESCE(SUM({$finish} BETWEEN :ws{$index} AND :we{$index}),0) finishes_{$index}";
            $weekParams[":ws{$index}"] = $weekStart;
            $weekParams[":we{$index}"] = $weekEnd;
        }
        $weekRow = $this->db->createCommand('SELECT ' . implode(',', $weekColumns) . $from, $weekParams)->queryOne();
        if ($weekRow === false) throw new \RuntimeException('Dashboard week aggregate unavailable.');

        $p = $this->prefix;
        $lastActivity = "GREATEST(COALESCE((SELECT MAX(co.server_received_at) FROM `{$p}fm2_checklist_operations` co WHERE co.installation_case_id=c.id AND co.server_received_at<DATE_ADD(:activityCutoff,INTERVAL 1 DAY)),'1000-01-01'),COALESCE((SELECT MAX(ph.server_received_at) FROM `{$p}fm2_checklist_photos` ph WHERE ph.installation_case_id=c.id AND ph.revoked_at IS NULL AND ph.server_received_at<DATE_ADD(:activityCutoff,INTERVAL 1 DAY)),'1000-01-01'),COALESCE((SELECT MAX(x.recorded_at) FROM (SELECT cf.installation_case_id,cf.recorded_at FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.fact_type IN('pto_act','declaration') UNION ALL SELECT cf.installation_case_id,cc.recorded_at FROM `{$p}fm2_pilot_completion_fact_corrections` cc JOIN `{$p}fm2_pilot_completion_facts` cf ON cf.id=cc.root_fact_id WHERE cf.fact_type IN('pto_act','declaration')) x WHERE x.installation_case_id=c.id AND x.recorded_at<DATE_ADD(:activityCutoff,INTERVAL 1 DAY)),'1000-01-01'))";
        $activityScope = '(' . $stages['installation'] . ' OR ' . $stages['document_closeout'] . ' OR ' . $stages['needs_assignment_change'] . ')';
        $age = "DATEDIFF(:activityCutoff,DATE({$lastActivity}))";
        $activityRow = $this->db->createCommand("SELECT COALESCE(SUM({$activityScope} AND {$lastActivity}<>'1000-01-01' AND {$age} BETWEEN 0 AND 7),0) age_0_7,COALESCE(SUM({$activityScope} AND {$lastActivity}<>'1000-01-01' AND {$age} BETWEEN 8 AND 14),0) age_8_14,COALESCE(SUM({$activityScope} AND {$lastActivity}<>'1000-01-01' AND {$age} BETWEEN 15 AND 30),0) age_15_30,COALESCE(SUM({$activityScope} AND {$lastActivity}<>'1000-01-01' AND {$age}>=31),0) age_31_plus,COALESCE(SUM({$activityScope} AND {$lastActivity}='1000-01-01'),0) never{$from}", [':activityCutoff' => $cutoff])->queryOne();
        if ($activityRow === false) throw new \RuntimeException('Dashboard activity aggregate unavailable.');

        $stageLabels = ['needs_assignment_order'=>'Требуется распоряжение','ready_to_open'=>'Готов к открытию','installation'=>'Монтажные работы','document_closeout'=>'Документарное закрытие','completed'=>'Работы завершены','needs_assignment_change'=>'Требуется изменение'];
        $activityLabels = ['age_0_7'=>'0–7 дней','age_8_14'=>'8–14 дней','age_15_30'=>'15–30 дней','age_31_plus'=>'Более 30 дней','never'=>'Активности не было'];

        return [
            'cutoff' => $cutoff,
            'total' => (int) $metrics['total'],
            'active' => (int) $metrics['active'],
            'overdueCount' => (int) $metrics['overdue'],
            'upcomingCount' => (int) $metrics['upcoming'],
            'upcoming' => self::rows($upcoming),
            'overdue' => self::rows($overdue),
            'charts' => [
                'stages' => array_map(static fn(string $key, string $label): array => ['key'=>$key,'label'=>$label,'value'=>(int)$stageRow[$key]], array_keys($stageLabels), array_values($stageLabels)),
                'weeks' => array_map(static fn(array $week, int $index): array => ['start'=>$week[0],'end'=>$week[1],'starts'=>(int)$weekRow['starts_'.$index],'finishes'=>(int)$weekRow['finishes_'.$index]], $weeks, array_keys($weeks)),
                'activityAge' => array_map(static fn(string $key, string $label): array => ['key'=>$key,'label'=>$label,'value'=>(int)$activityRow[$key]], array_keys($activityLabels), array_values($activityLabels)),
            ],
        ];
    }

    private function source(): array
    {
        $p = $this->prefix;
        $l = $this->legacyPrefix;
        $completed = "c.process_state IN('working','needs_assignment_change') AND (a.application_id IS NOT NULL OR o.status IN('prepared','registered')) AND EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='pto_act') AND EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='declaration')";
        $count = "(SELECT COUNT(DISTINCT co.item_id) FROM `{$p}fm2_checklist_operations` co WHERE co.installation_case_id=c.id AND co.operation_type='item_completed' AND co.item_id<>42)";
        $active = "c.process_state IN('working','needs_assignment_change') AND (a.application_id IS NOT NULL OR o.status IN('prepared','registered')) AND NOT({$completed})";
        $source = "(m.category='native_candidate' OR (m.output_id IS NULL AND d.object_id=c.legacy_installation_object_id AND d.content_sha256=SHA2(d.payload_json,256)))";
        $from = " FROM `{$p}fm2_installation_cases` c JOIN `{$l}fm_maintable` l ON l.id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_migration_classification_provenance` m ON m.output_kind='operational_case' AND m.output_id=c.id AND m.legacy_object_id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_pilot_object_details` d ON d.object_id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_assignment_orders` o ON o.installation_case_id=c.id AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x WHERE x.installation_case_id=c.id) LEFT JOIN `{$p}fm2_assignment_order_applications` a ON a.application_id=(SELECT x.application_id FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=c.id ORDER BY x.application_sequence DESC LIMIT 1) LEFT JOIN `{$p}fm2_assignment_order_selections` s ON s.installation_case_id=c.id AND s.selection_revision=(SELECT MAX(x.selection_revision) FROM `{$p}fm2_assignment_order_selections` x WHERE x.installation_case_id=c.id) LEFT JOIN `{$p}fm2_assignment_order_original_roots` r ON r.installation_case_id=c.id AND r.assignment_order_id=s.assignment_order_id AND r.composition_identity=s.composition_identity AND r.composition_sha256=s.composition_sha256 LEFT JOIN `{$p}fm2_assignment_order_original_revisions` v ON v.root_original_id=r.root_original_id AND v.revision_id=r.current_revision_id LEFT JOIN `{$p}fm2_deadline_certificate_roots` dr ON dr.installation_case_id=c.id LEFT JOIN `{$p}fm2_deadline_certificate_revisions` dc ON dc.id=dr.current_revision_id WHERE {$source}";
        $start = "CASE WHEN LEFT(l.workdatestart,10) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND LEFT(l.workdatestart,4)<>'0000' THEN LEFT(l.workdatestart,10) ELSE NULL END";
        $finish = "CASE WHEN dc.new_deadline REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN dc.new_deadline WHEN LEFT(NULLIF(l.workdateendadjusted,''),10) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND LEFT(l.workdateendadjusted,4)<>'0000' THEN LEFT(l.workdateendadjusted,10) WHEN LEFT(l.plan_finish_date,10) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND LEFT(l.plan_finish_date,4)<>'0000' THEN LEFT(l.plan_finish_date,10) ELSE NULL END";
        $stages = MariaDbYiiObjectQueue::stagePredicates($p);
        return [$from, $active, $completed, $start, $finish, $stages];
    }

    private static function rows(array $rows): array
    {
        return array_map(static fn(array $row): array => [
            'id' => (int) $row['object_id'],
            'registrationNumber' => trim((string) ($row['regnumber'] ?? '')),
            'date' => (string) $row['relevant_date'],
        ], $rows);
    }
}
