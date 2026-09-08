<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** Validated owning-module source plus a seal of this reference's immutable rows. */
final readonly class MariaDbOriginalApplicationReferenceSource
{
    public function __construct(private AssignmentOrderOriginalSql $sql) {}

    public function metadata(int $object, int $order, bool $lock = false): ?array
    {
        $context = (new MariaDbOriginalSubmissionSource($this->sql))->read($object, $order, $lock);
        if ($context === null || $context['current'] === null) return null;
        $composition = MariaDbRegisteredCompositionQuery::read($this->sql, $context['caseId'], $order, null, $lock);
        $current = $context['current'];
        return ['objectId'=>$object, 'caseId'=>$context['caseId'], 'orderId'=>$order, 'orderVersion'=>$context['orderVersion'],
            'rootOriginalId'=>$current['rootId'], 'revisionId'=>$current['revisionId'], 'revisionNumber'=>$current['revisionNumber'],
            'documentDate'=>$current['documentDate'], 'sha256'=>$current['sha256'], 'byteSize'=>$current['byteSize'],
            'uploadedAt'=>$current['uploadedAt'], 'compositionIdentity'=>$composition->identity,
            'compositionSha256'=>$composition->sha256, 'composition'=>$context['composition']];
    }

    public function seal(array $metadata, string $referenceRevision, bool $lock = false): string
    {
        $s = $this->sql; $suffix = $lock ? ' FOR UPDATE' : ''; $order = $metadata['orderId'];
        $roots = $s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_original_roots')
            .' WHERE root_original_id='.$s->quote($metadata['rootOriginalId']).$suffix);
        $revisions = $s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_original_revisions')
            .' WHERE revision_id='.$s->quote($referenceRevision).$suffix);
        $registry = $s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_identities').' WHERE assignment_order_id='.$order.$suffix);
        $headers = $s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_selections').' WHERE assignment_order_id='.$order.$suffix);
        $members = $s->rows('SELECT * FROM '.$s->table('fm2_assignment_order_selection_members')
            .' WHERE assignment_order_id='.$order.' ORDER BY installer_tab_id'.$suffix);
        if (\count($roots) !== 1 || \count($revisions) !== 1 || \count($registry) !== 1 || \count($headers) !== 1 || $members === [])
            AssignmentOrderOriginalSql::fail();
        // The leaf pointer is compared separately; the prior revision must stay exact after a correction.
        unset($roots[0]['current_revision_id']);
        return \hash('sha256', \serialize([$roots, $revisions, $registry, $headers, $members]));
    }
}
