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
        if ($old !== []) {
            if (count($old) !== 1) throw new \RuntimeException('Checklist association unavailable.');
            $association = $old[0];
            $snapshots = $s->rows('SELECT id,snapshot_version,valid_from,validity_scope,content_sha256,payload_json FROM '.$s->table('fm2_checklist_template_snapshots').' WHERE id=?', [(int)$association['template_snapshot_id']]);
            if (count($snapshots) !== 1) throw new \RuntimeException('Checklist association unavailable.');
            $template = $snapshots[0];
            $effective=(string)($association['effective_at']??'');
            if ((string)$association['association_version'] !== 'checklist-template-association-v1'
                || preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',$effective)!==1
                || $effective>$instant
                || (string)$template['validity_scope'] !== 'active_baseline_and_future_native_only'
                || !is_string($template['valid_from']) || $template['valid_from'] > $effective
                || (string)$association['template_snapshot_version'] !== (string)$template['snapshot_version']
                || !hash_equals((string)$association['template_content_sha256'], (string)$template['content_sha256'])
                || !hash_equals((string)$template['content_sha256'], hash('sha256', (string)$template['payload_json']))) {
                throw new \RuntimeException('Checklist association unavailable.');
            }
            $payload=json_decode((string)$template['payload_json'], true, 64, JSON_THROW_ON_ERROR);
            if(!is_array($payload))throw new \RuntimeException('Checklist association unavailable.');
            return ['templateSnapshotId'=>(int)$template['id'],'templateVersion'=>$template['snapshot_version'],'templateSha256'=>$template['content_sha256']];
        }
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
