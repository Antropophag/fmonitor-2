<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;

use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;

final class OriginalStartFixture
{
    public readonly SelectedOriginalFixture $original;
    public readonly \mysqli $db;
    public readonly C\SelectionClock $clock;
    public const PREFIX = 'start_';

    public function __construct(bool $template = true)
    {
        $this->original = new SelectedOriginalFixture(self::PREFIX);
        $this->db = $this->original->selection->db;
        try {
            \assertSameValue('selected', $this->original->selection->app()->selectAssignmentOrderComposition(SelectionNativeFixture::command())->status()->value, 'native composition prerequisite');
            $this->db->query("INSERT INTO start_fm2_pilot_role_permissions(role_id,permission) VALUES(1,'installation.open')");
            I\ChecklistTemplateSchemaMigration::apply($this->db, self::PREFIX);
            if ($template) $this->addTemplate();
            $this->db->query("UPDATE start_fm_maintable SET workdatestart='2026-08-21' WHERE id=4512");
            \assertSameValue(true, class_exists(I\AssignmentOrderOriginalStartSchemaMigration::class), 'INTENDED_RED: original appendix date migration is absent');
            I\AssignmentOrderOriginalStartSchemaMigration::apply($this->db, self::PREFIX);
            $this->clock = new class implements C\SelectionClock {
                public function now(): C\SelectionInstantLookup { return C\SelectionInstantLookup::found(new C\SelectionInstant('2026-09-05T10:00:00Z')); }
            };
        } catch (\Throwable $e) { $this->original->close(); throw $e; }
    }

    public function addTemplate(): void
    {
        $payload = '{"sections":[{"id":1,"name":"Монтаж","items":[{"id":28,"name":"Работа","weight":2}]}]}';
        $this->original->selection->schema->insert('start_fm2_checklist_template_snapshots', [
            'snapshot_version'=>'start-date-v1','captured_at'=>'2026-09-01 00:00:00','valid_from'=>'2026-09-01 00:00:00',
            'validity_scope'=>'active_baseline_and_future_native_only','source_label'=>'synthetic start date',
            'content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'created_at'=>'2026-09-01 00:00:00',
        ]);
    }

    public function owner(?O\AssignmentOrderOriginalApplication $original = null): object
    {
        \assertSameValue(true, class_exists(C\ProductionOriginalStartFactory::class), 'INTENDED_RED: production upload-and-open owner is absent');
        return C\ProductionOriginalStartFactory::create($this->db, self::PREFIX, $original ?? $this->original->app(), $this->clock);
    }

    public function command(int $request = 1, ?string $pdf = null, ?array $correction = null, int $order = 81, string $documentDate = '2026-09-04'): O\SubmitAssignmentOrderOriginalCommand
    {
        return new O\SubmitAssignmentOrderOriginalCommand(sprintf('12121212-1212-4212-8212-%012d',$request),
            $correction === null ? O\AssignmentOrderOriginalMode::INITIAL : O\AssignmentOrderOriginalMode::CORRECTION,
            4512,$order,18,$documentDate,true,$correction['root']??null,$correction['revision']??null,$correction['revision']??null,
            $correction === null ? null : 'Исправлен подписанный файл',
            new O\AssignmentOrderOriginalUpload(new O\AssignmentOrderOriginalMemoryStream($pdf ?? SelectedOriginalFixture::pdf()),'signed.pdf','application/pdf'));
    }

    public static function otherPdf(): string
    {
        return AssignmentOrderOriginalPdfCorpus::classic(['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 144 144] >>']);
    }

    public function rows(string $table): array { return $this->db->query('SELECT * FROM start_'.$table.' ORDER BY 1')->fetch_all(MYSQLI_ASSOC); }
    public function case(): array { return $this->rows('fm2_installation_cases')[0]; }
    public function openingCount(): int { return (int)$this->db->query("SELECT COUNT(*) FROM start_fm2_process_events WHERE event_type='installation_opened_from_original'")->fetch_column(); }
    public function snapshot(): array
    {
        $out=[];
        foreach (['fm2_assignment_order_original_roots','fm2_assignment_order_original_revisions','fm2_assignment_order_applications','fm2_installation_cases','fm2_process_events','fm2_checklist_template_associations'] as $t) $out[$t]=$this->rows($t);
        $out['files']=$this->original->privateFiles(); return $out;
    }
    public function correction(array $result): array { return ['root'=>$result['original']->rootOriginalId(),'revision'=>$result['original']->currentRevisionId()]; }
    public function close(): void { $this->original->close(); }
}
