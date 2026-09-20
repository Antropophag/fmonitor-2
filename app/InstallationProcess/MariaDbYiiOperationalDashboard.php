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

    public function read(string $cutoff, string $windowEnd): array
    {
        [$from, $active, $completed, $start, $finish] = $this->source();
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

        return [
            'cutoff' => $cutoff,
            'total' => (int) $metrics['total'],
            'active' => (int) $metrics['active'],
            'overdueCount' => (int) $metrics['overdue'],
            'upcomingCount' => (int) $metrics['upcoming'],
            'upcoming' => self::rows($upcoming),
            'overdue' => self::rows($overdue),
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
        $from = " FROM `{$p}fm2_installation_cases` c JOIN `{$l}fm_maintable` l ON l.id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_migration_classification_provenance` m ON m.output_kind='operational_case' AND m.output_id=c.id AND m.legacy_object_id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_pilot_object_details` d ON d.object_id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_assignment_orders` o ON o.installation_case_id=c.id AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x WHERE x.installation_case_id=c.id) LEFT JOIN `{$p}fm2_assignment_order_applications` a ON a.application_id=(SELECT x.application_id FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=c.id ORDER BY x.application_sequence DESC LIMIT 1) WHERE {$source}";
        $start = "CASE WHEN LEFT(l.workdatestart,10) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND LEFT(l.workdatestart,4)<>'0000' THEN LEFT(l.workdatestart,10) ELSE NULL END";
        $finish = "CASE WHEN LEFT(NULLIF(l.workdateendadjusted,''),10) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND LEFT(l.workdateendadjusted,4)<>'0000' THEN LEFT(l.workdateendadjusted,10) WHEN LEFT(l.plan_finish_date,10) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' AND LEFT(l.plan_finish_date,4)<>'0000' THEN LEFT(l.plan_finish_date,10) ELSE NULL END";
        return [$from, $active, $completed, $start, $finish];
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
