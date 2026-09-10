<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiObjectQueue
{
    public function __construct(private Connection$db, private string$prefix, private string$legacyPrefix, private MariaDbYiiObjectQueueProjection $projection)
    {
        foreach ([$prefix,$legacyPrefix] as $p) {
            if (strlen($p) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $p) !== 1) {
                throw new \InvalidArgumentException();
            }
        }
    }
    public function authorized(int$actor): bool
    {
        $p = $this->prefix;
        $sql = "SELECT 1 FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=:id AND u.status=1 AND u.activation_state='active' AND r.status=1 AND BINARY rp.permission='objects.read' LIMIT 1";
        return(bool)$this->db->createCommand($sql, [':id' => $actor])->queryScalar();
    }
    public function read(int$actor, string$q, string$status, int$page, int$size = 50): array
    {
        $allowed = ['','needs_assignment_order','ready_to_open','installation','document_closeout','completed','needs_assignment_change'];
        if (mb_strlen($q) > 120 || $page < 1 || !in_array($status, $allowed, true)) {
            throw new \RuntimeException('Invalid queue filter.');
        }
        if (!MariaDbYiiSchemaFingerprint::queueFamiliesReady($this->db, $this->prefix)) {
            throw new \RuntimeException('Queue schema unavailable.');
        }
        [$from,$params] = $this->query($q, $status);
        $total = (int)$this->db->createCommand('SELECT COUNT(*)' . $from, $params)->queryScalar();
        $pages = max(1, (int)ceil($total / $size));
        if ($page > $pages) {
            throw new \RuntimeException('Page unavailable.');
        }
        $offset = ($page - 1) * $size;
        $columns = 'c.id case_id,c.legacy_installation_object_id,c.process_state,c.actual_start_date,c.opened_at,c.opened_by_user_id,l.ordadr_address,l.entrance,l.regnumber,l.workdatestart,l.workdateendadjusted,l.plan_finish_date,o.status order_status,a.application_id,s.assignment_order_id selection_order_id,v.revision_id original_revision_id';
        $rows = $this->db->createCommand("SELECT {$columns}{$from} ORDER BY l.workdatestart IS NULL,LEFT(l.workdatestart,10),c.legacy_installation_object_id LIMIT {$size} OFFSET {$offset}", $params)->queryAll();
        $objects = [];
        foreach ($rows as $row) {
            $objects[] = $this->map($row, $page, $pages, $total);
        }
        $objects = $this->projection->decorate($objects, $actor);
        return['objects' => $objects,'filters' => ['q' => $q,'status' => $status,'page' => $page,'pages' => $pages,'total' => $total]];
    }
    private function query(string$q, string$status): array
    {
        $p = $this->prefix;
        $l = $this->legacyPrefix;
        $count = "(SELECT COUNT(DISTINCT co.item_id) FROM `{$p}fm2_checklist_operations` co WHERE co.installation_case_id=c.id AND co.operation_type='item_completed' AND co.item_id<>42)";
        $pto = "EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='pto_act')";
        $dec = "EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='declaration')";
        $active = "c.process_state IN('working','needs_assignment_change') AND (a.application_id IS NOT NULL OR o.status IN('prepared','registered'))";
        $ready = "(v.revision_id IS NOT NULL OR (s.assignment_order_id IS NULL AND (a.application_id IS NOT NULL OR COALESCE(o.status='registered',0))))";
        $filter = match ($status) {
            'needs_assignment_order' => "c.process_state IN('needs_assignment_order','assignment_order_prepared')"
                . " AND NOT {$ready} AND (s.assignment_order_id IS NOT NULL"
                . " OR (c.process_state='needs_assignment_order' AND o.id IS NULL AND a.application_id IS NULL)"
                . " OR (c.process_state='assignment_order_prepared' AND o.status='prepared' AND a.application_id IS NULL))",
            'ready_to_open' => "c.process_state IN('needs_assignment_order','assignment_order_prepared') AND {$ready}",
            'installation' => "{$active} AND {$count}<41",
            'document_closeout' => "{$active} AND {$count}=41 AND NOT({$pto} AND {$dec})",
            'completed' => "{$active} AND {$pto} AND {$dec}",
            'needs_assignment_change' => "c.process_state='needs_assignment_change'"
                . " AND (a.application_id IS NOT NULL OR o.status='registered')",
            default => '1=1',
        };
        $source = "(m.category='native_candidate' OR (m.output_id IS NULL AND d.object_id=c.legacy_installation_object_id AND d.content_sha256=SHA2(d.payload_json,256)))";
        $where = "{$source} AND ({$filter})";
        $params = [];
        if ($q !== '') {
            $where .= " AND (CAST(c.legacy_installation_object_id AS CHAR) LIKE :q ESCAPE '\\\\' OR l.regnumber LIKE :q ESCAPE '\\\\' OR l.ordadr_address LIKE :q ESCAPE '\\\\' OR l.entrance LIKE :q ESCAPE '\\\\')";
            $params[':q'] = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $q) . '%';
        }
        $from = " FROM `{$p}fm2_installation_cases` c"
            . " JOIN `{$l}fm_maintable` l ON l.id=c.legacy_installation_object_id"
            . " LEFT JOIN `{$p}fm2_migration_classification_provenance` m"
            . " ON m.output_kind='operational_case' AND m.output_id=c.id"
            . ' AND m.legacy_object_id=c.legacy_installation_object_id'
            . " LEFT JOIN `{$p}fm2_pilot_object_details` d ON d.object_id=c.legacy_installation_object_id"
            . " LEFT JOIN `{$p}fm2_assignment_orders` o ON o.installation_case_id=c.id"
            . " AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x"
            . ' WHERE x.installation_case_id=c.id)'
            . " LEFT JOIN `{$p}fm2_assignment_order_applications` a ON a.application_id="
            . "(SELECT x.application_id FROM `{$p}fm2_assignment_order_applications` x"
            . ' WHERE x.installation_case_id=c.id ORDER BY x.application_sequence DESC LIMIT 1)'
            . " LEFT JOIN `{$p}fm2_assignment_order_selections` s ON s.installation_case_id=c.id"
            . " AND s.selection_revision=(SELECT MAX(x.selection_revision) FROM `{$p}fm2_assignment_order_selections` x"
            . ' WHERE x.installation_case_id=c.id)'
            . " LEFT JOIN `{$p}fm2_assignment_order_original_roots` r ON r.installation_case_id=c.id"
            . ' AND r.assignment_order_id=s.assignment_order_id AND r.composition_identity=s.composition_identity'
            . ' AND r.composition_sha256=s.composition_sha256'
            . " LEFT JOIN `{$p}fm2_assignment_order_original_revisions` v ON v.root_original_id=r.root_original_id"
            . " AND v.revision_id=r.current_revision_id WHERE {$where}";
        return[$from,$params];
    }
    private function map(array$r, int$page, int$pages, int$total): array
    {
        $tuple = [$r['actual_start_date'],$r['opened_at'],$r['opened_by_user_id']];
        $present = count(array_filter($tuple, fn ($v) => $v !== null));
        $opened = $present === 3;
        $empty = $present === 0;
        $selected = $r['selection_order_id'] !== null;
        $applied = $r['application_id'] !== null;
        $order = $r['order_status'];
        $ready = $r['original_revision_id'] !== null || (!$selected && ($applied || $order === 'registered'));
        $label = match ($r['process_state']) {
            'needs_assignment_order' => $empty && $ready ? 'Готов к открытию'
                : ($empty && ($selected || ($order === null && !$applied)) ? 'Требуется распоряжение' : null),
            'assignment_order_prepared' => $empty && $ready ? 'Готов к открытию'
                : ($empty && ($selected || ($order === 'prepared' && !$applied)) ? 'Требуется распоряжение' : null),
            'working' => $opened && ($applied || in_array($order, ['prepared','registered'], true)) ? 'В работе' : null,
            'needs_assignment_change' => $opened && ($applied || in_array($order, ['prepared','registered'], true))
                ? 'Требуется изменение' : null,
            default => null,
        };
        $id = (int)$r['legacy_installation_object_id'];
        if (!in_array($present, [0,3], true) || $id < 1 || $label === null
            || trim((string)$r['ordadr_address']) === ''
            || trim((string)$r['entrance']) === ''
            || trim((string)$r['regnumber']) === '') {
            throw new \RuntimeException('Malformed queue row.');
        }
        $start = self::date($r['workdatestart']);
        $finish = self::date($r['workdateendadjusted']) ?? self::date($r['plan_finish_date']);
        return [
            'id'=>$id, 'caseId'=>(int)$r['case_id'], 'registrationNumber'=>$r['regnumber'],
            'address'=>$r['ordadr_address'], 'entrance'=>$r['entrance'],
            'plannedStartDate'=>$start ?? 'Не указано', 'plannedFinishDate'=>$finish ?? 'Не указано',
            'planningDatesUnknownAtCutover'=>$start === null || $finish === null,
            'dataOrigin'=>'migration_native', 'status'=>$label,
            'nextStep'=>'Откройте карточку объекта монтажа',
            '_pagination'=>compact('page', 'pages', 'total'),
        ];
    }
    private static function date(mixed$v): ?string
    {
        $v = trim((string)$v);
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\D|$)/D', $v, $m) === 1 && $m[1] !== '0000' && checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $m[1] . '-' . $m[2] . '-' . $m[3] : null;
    }
}
