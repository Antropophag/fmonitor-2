<?php

declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final readonly class MariaDbObjectQueue
{
    public function __construct(
        private \mysqli $db,
        private string $prefix,
        private string $legacyPrefix,
    ) {
        foreach ([$prefix, $legacyPrefix] as $value) {
            if (\preg_match('/^[A-Za-z0-9_]+$/D', $value) !== 1) {
                throw new PilotHttpInfrastructureUnavailable();
            }
        }
    }

    public function read(string $query, string $status, int $page, int $size = 50): array
    {
        $allowed = ['', 'needs_assignment_order', 'ready_to_open', 'installation', 'document_closeout', 'completed', 'needs_assignment_change'];
        if (\mb_strlen($query) > 120 || $page < 1 || $size < 1 || !\in_array($status, $allowed, true)) {
            throw new PilotHttpInfrastructureUnavailable();
        }

        $progressCount = "(SELECT COUNT(DISTINCT co.item_id) FROM `{$this->prefix}fm2_checklist_operations` co WHERE co.installation_case_id=c.id AND co.operation_type='item_completed' AND co.item_id<>42)";
        $hasPto = "EXISTS(SELECT 1 FROM `{$this->prefix}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='pto_act')";
        $hasDeclaration = "EXISTS(SELECT 1 FROM `{$this->prefix}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='declaration')";
        $active = "c.process_state IN('working','needs_assignment_change') AND (a.application_id IS NOT NULL OR o.status IN('prepared','registered'))";
        // A current confirmed original is the opening basis even before application.
        // Keep the legacy fallback only for cases with no native selection lineage.
        $readyBasis = "(v.revision_id IS NOT NULL OR (s.assignment_order_id IS NULL AND (a.application_id IS NOT NULL OR COALESCE(o.status='registered',0))))";
        $statusSql = match ($status) {
            'needs_assignment_order' => "c.process_state IN('needs_assignment_order','assignment_order_prepared') AND NOT {$readyBasis} AND (s.assignment_order_id IS NOT NULL OR (c.process_state='needs_assignment_order' AND o.id IS NULL AND a.application_id IS NULL) OR (c.process_state='assignment_order_prepared' AND o.status='prepared' AND a.application_id IS NULL))",
            'ready_to_open' => "c.process_state IN('needs_assignment_order','assignment_order_prepared') AND {$readyBasis}",
            'installation' => "{$active} AND {$progressCount}<41",
            'document_closeout' => "{$active} AND {$progressCount}=41 AND NOT({$hasPto} AND {$hasDeclaration})",
            'completed' => "{$active} AND {$hasPto} AND {$hasDeclaration}",
            'needs_assignment_change' => "c.process_state='needs_assignment_change' AND (a.application_id IS NOT NULL OR o.status='registered')",
            default => '1=1',
        };
        $source = "(m.category='native_candidate' OR (m.output_id IS NULL AND d.object_id=c.legacy_installation_object_id AND d.content_sha256=SHA2(d.payload_json,256)))";
        $where = "{$source} AND ({$statusSql})";
        if ($query !== '') {
            $like = '%' . \str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';
            $like = $this->db->real_escape_string($like);
            $where .= " AND (CAST(c.legacy_installation_object_id AS CHAR) LIKE '{$like}' ESCAPE '\\\\' OR l.regnumber LIKE '{$like}' ESCAPE '\\\\' OR l.ordadr_address LIKE '{$like}' ESCAPE '\\\\' OR l.entrance LIKE '{$like}' ESCAPE '\\\\')";
        }
        $from = " FROM `{$this->prefix}fm2_installation_cases` c JOIN `{$this->legacyPrefix}fm_maintable` l ON l.id=c.legacy_installation_object_id LEFT JOIN `{$this->prefix}fm2_migration_classification_provenance` m ON m.output_kind='operational_case' AND m.output_id=c.id AND m.legacy_object_id=c.legacy_installation_object_id LEFT JOIN `{$this->prefix}fm2_pilot_object_details` d ON d.object_id=c.legacy_installation_object_id LEFT JOIN `{$this->prefix}fm2_assignment_orders` o ON o.installation_case_id=c.id AND o.version_no=(SELECT MAX(latest.version_no) FROM `{$this->prefix}fm2_assignment_orders` latest WHERE latest.installation_case_id=c.id) LEFT JOIN `{$this->prefix}fm2_assignment_order_applications` a ON a.application_id=(SELECT latest.application_id FROM `{$this->prefix}fm2_assignment_order_applications` latest WHERE latest.installation_case_id=c.id ORDER BY latest.application_sequence DESC LIMIT 1) LEFT JOIN `{$this->prefix}fm2_assignment_order_selections` s ON s.installation_case_id=c.id AND s.selection_revision=(SELECT MAX(latest.selection_revision) FROM `{$this->prefix}fm2_assignment_order_selections` latest WHERE latest.installation_case_id=c.id) LEFT JOIN `{$this->prefix}fm2_assignment_order_original_roots` r ON r.installation_case_id=c.id AND r.assignment_order_id=s.assignment_order_id AND r.composition_identity=s.composition_identity AND r.composition_sha256=s.composition_sha256 LEFT JOIN `{$this->prefix}fm2_assignment_order_original_revisions` v ON v.root_original_id=r.root_original_id AND v.revision_id=r.current_revision_id WHERE {$where}";
        $total = (int) $this->db->query("SELECT COUNT(*) n{$from}")->fetch_assoc()['n'];
        $pages = \max(1, (int) \ceil($total / $size));
        if ($page > $pages) {
            throw new PilotHttpInfrastructureUnavailable();
        }
        $offset = ($page - 1) * $size;
        $rows = $this->db->query("SELECT c.legacy_installation_object_id,c.process_state,c.actual_start_date,c.opened_at,c.opened_by_user_id,l.ordadr_address,l.entrance,l.regnumber,l.workdatestart,l.workdateendadjusted,l.plan_finish_date,o.status order_status,a.application_id,s.assignment_order_id selection_order_id,v.revision_id original_revision_id{$from} ORDER BY l.workdatestart IS NULL,LEFT(l.workdatestart,10),c.legacy_installation_object_id LIMIT {$size} OFFSET {$offset}")->fetch_all(MYSQLI_ASSOC);
        $objects = [];
        foreach ($rows as $row) {
            $opening = [$row['actual_start_date'], $row['opened_at'], $row['opened_by_user_id']];
            $opened = \count(\array_filter($opening, static fn (mixed $value): bool => $value !== null)) === 3;
            $empty = \count(\array_filter($opening, static fn (mixed $value): bool => $value !== null)) === 0;
            $order = $row['order_status'];
            $applied = $row['application_id'] !== null;
            $selected = $row['selection_order_id'] !== null;
            $ready = $row['original_revision_id'] !== null || (!$selected && ($applied || $order === 'registered'));
            $label = match ((string) $row['process_state']) {
                'needs_assignment_order' => $empty && $ready ? 'Готов к открытию' : ($empty && ($selected || ($order === null && !$applied)) ? 'Требуется распоряжение' : null),
                'assignment_order_prepared' => $empty && $ready ? 'Готов к открытию' : ($empty && ($selected || ($order === 'prepared' && !$applied)) ? 'Распоряжение подготовлено' : null),
                'working' => $opened && ($applied || \in_array($order, ['prepared', 'registered'], true)) ? 'В работе' : null,
                'needs_assignment_change' => $opened && ($applied || \in_array($order, ['prepared', 'registered'], true)) ? 'Требуется изменение' : null,
                default => null,
            };
            $id = (int) $row['legacy_installation_object_id'];
            $start = self::date($row['workdatestart']);
            $finish = self::date($row['workdateendadjusted']) ?? self::date($row['plan_finish_date']);
            if ($id < 1 || $label === null || \trim((string) $row['ordadr_address']) === '' || \trim((string) $row['entrance']) === '' || \trim((string) $row['regnumber']) === '') {
                throw new PilotHttpInfrastructureUnavailable();
            }
            $objects[] = [
                'id' => $id,
                'registrationNumber' => (string) $row['regnumber'],
                'address' => (string) $row['ordadr_address'],
                'entrance' => (string) $row['entrance'],
                'plannedStartDate' => $start ?? 'Не указано',
                'plannedFinishDate' => $finish ?? 'Не указано',
                'planningDatesUnknownAtCutover' => $start === null || $finish === null,
                'dataOrigin' => 'migration_native',
                'status' => $label,
                '_pagination' => ['page' => $page, 'pages' => $pages, 'total' => $total, 'pageSize' => $size, 'origin' => 'all'],
            ];
        }

        return [$objects, ['q' => $query, 'status' => $status, 'page' => $page, 'pages' => $pages, 'total' => $total]];
    }

    private static function date(mixed $value): ?string
    {
        $value = \trim((string) $value);
        if (\preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\D|$)/D', $value, $parts) !== 1 || $parts[1] === '0000' || !\checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return null;
        }
        return $parts[1] . '-' . $parts[2] . '-' . $parts[3];
    }
}
