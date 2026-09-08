<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Projects only source already validated by the owning module in this snapshot. */
final readonly class MariaDbOriginalHistorySource
{
    private MariaDbOriginalApplicationReferenceSource $source;
    public function __construct(private AssignmentOrderOriginalSql $sql)
    { $this->source = new MariaDbOriginalApplicationReferenceSource($sql); }

    public function history(int $object, int $order, int $after, int $limit): ?array
    {
        $metadata = $this->source->metadata($object, $order);
        if ($metadata === null) return null;
        $rows = $this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_original_revisions')
            .' WHERE root_original_id='.$this->sql->quote($metadata['rootOriginalId'])
            .' AND revision_number>'.$after.' ORDER BY revision_number LIMIT '.$limit);
        $records = \array_map(self::record(...), $rows);
        $last = $records === [] ? null : $records[\array_key_last($records)]['revisionNumber'];
        return self::context($metadata, true) + ['totalRevisions'=>$metadata['revisionNumber'],
            'afterRevisionNumber'=>$after, 'nextAfterRevisionNumber'=>$last !== null && $last < $metadata['revisionNumber'] ? $last : null,
            'revisions'=>$records];
    }

    public function download(int $object, int $order, string $revision): ?array
    {
        $metadata = $this->source->metadata($object, $order);
        if ($metadata === null) return null;
        $rows = $this->sql->rows('SELECT * FROM '.$this->sql->table('fm2_assignment_order_original_revisions')
            .' WHERE root_original_id='.$this->sql->quote($metadata['rootOriginalId'])
            .' AND BINARY revision_id=BINARY '.$this->sql->quote($revision));
        if ($rows === []) return null;
        if (\count($rows) !== 1 || $rows[0]['revision_id'] !== $revision) AssignmentOrderOriginalSql::fail();
        return ['metadata'=>self::context($metadata, false) + ['revision'=>self::record($rows[0])],
            'identity'=>$rows[0]['private_content_identity']];
    }

    private static function context(array $metadata, bool $history): array
    {
        $value = ['objectId'=>$metadata['objectId'], 'caseId'=>$metadata['caseId'], 'orderId'=>$metadata['orderId'],
            'orderVersion'=>$metadata['orderVersion'], 'rootOriginalId'=>$metadata['rootOriginalId']];
        if ($history) $value['currentRevisionId'] = $metadata['revisionId'];
        return $value + ['compositionIdentity'=>$metadata['compositionIdentity'],
            'compositionSha256'=>$metadata['compositionSha256'], 'composition'=>$metadata['composition']];
    }

    private static function record(array $row): array
    {
        return ['revisionId'=>$row['revision_id'], 'revisionNumber'=>AssignmentOrderOriginalStoredRows::integer($row['revision_number'], 4294967295),
            'previousRevisionId'=>$row['previous_revision_id'], 'documentDate'=>$row['document_date'],
            'uploadedAt'=>AssignmentOrderOriginalStoredRows::time($row['uploaded_at_utc']),
            'actorUserId'=>AssignmentOrderOriginalStoredRows::integer($row['actor_user_id']), 'sha256'=>$row['pdf_sha256'],
            'byteSize'=>AssignmentOrderOriginalStoredRows::integer($row['byte_size'], 20971520), 'correctionReason'=>$row['correction_reason']];
    }
}
