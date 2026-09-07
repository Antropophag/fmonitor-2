<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** Owns the immutable checklist association created with native opening. */
final readonly class MariaDbOpeningTemplate
{
    public function __construct(private MariaDbSelectionSql $sql) {}
    public function bind(int $case, string $date, string $at): array
    {
        $s = $this->sql; $instant = str_replace(['T','Z'], [' ',''], $at);
        $old = $s->rows('SELECT * FROM '.$s->table('fm2_checklist_template_associations')." WHERE subject_kind='operational_case' AND subject_id=? FOR UPDATE", [(string)$case]);
        if ($old !== []) throw new \RuntimeException('Checklist already associated.');
        $rows = $s->rows('SELECT id,snapshot_version,content_sha256,payload_json FROM '.$s->table('fm2_checklist_template_snapshots')." WHERE validity_scope='active_baseline_and_future_native_only' AND valid_from<=? ORDER BY valid_from DESC,id DESC LIMIT 1", [$instant]);
        if (count($rows) !== 1 || !hash_equals($rows[0]['content_sha256'], hash('sha256', $rows[0]['payload_json']))) throw new \RuntimeException('Checklist template unavailable.');
        $template = $rows[0];
        $payload = json_decode($template['payload_json'], true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) throw new \RuntimeException('Checklist template unavailable.');
        $instant = str_replace(['T','Z'], [' ',''], $at);
        $s->insert('fm2_checklist_template_associations', ['association_version'=>'native-original-opening-v1','subject_kind'=>'operational_case',
            'subject_id'=>(string)$case,'effective_at'=>$instant,'template_snapshot_id'=>(int)$template['id'],
            'template_snapshot_version'=>$template['snapshot_version'],'template_content_sha256'=>$template['content_sha256'],'created_at'=>$instant]);
        return ['templateSnapshotId'=>(int)$template['id'],'templateVersion'=>$template['snapshot_version'],'templateSha256'=>$template['content_sha256']];
    }
}
