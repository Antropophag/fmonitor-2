<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

/**
 * Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v6.
 *
 * Data-only oracles are deliberately independent from production parsers,
 * repositories and storage adapters. Production code must not depend on this
 * verifier support file.
 */
final class AssignmentOrderOriginalRemainingMatrix
{
    public const POSITIVE_PDF_BASE64 = 'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK';
    public const POSITIVE_PDF_SHA256 = '4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784';
    public const POSITIVE_PDF_SIZE = 327;
    public const MAX_RECEIVED_BYTES = 20_971_520;
    public const MAX_CHUNK_BYTES = 65_536;

    /** @return array<string, array{status:string,reason:?string,retryable:bool}> */
    public static function resultOracles(): array
    {
        return [
            'passive-positive' => ['status' => 'accepted', 'reason' => null, 'retryable' => false],
            'malformed' => ['status' => 'rejected', 'reason' => 'invalid_pdf', 'retryable' => false],
            'encrypted' => ['status' => 'rejected', 'reason' => 'unsafe_pdf', 'retryable' => false],
            'zero-page' => ['status' => 'rejected', 'reason' => 'invalid_pdf', 'retryable' => false],
            'active-action' => ['status' => 'rejected', 'reason' => 'unsafe_pdf', 'retryable' => false],
            'received-byte-20971521' => ['status' => 'rejected', 'reason' => 'file_too_large', 'retryable' => false],
            'stream-failure' => ['status' => 'failed', 'reason' => 'stream_failure', 'retryable' => true],
            'stage-failure' => ['status' => 'failed', 'reason' => 'storage_failure', 'retryable' => true],
            'finalize-failure' => ['status' => 'failed', 'reason' => 'storage_failure', 'retryable' => true],
            'rollback' => ['status' => 'failed', 'reason' => 'persistence_failure', 'retryable' => true],
            'unknown-absent' => ['status' => 'failed', 'reason' => 'persistence_failure', 'retryable' => true],
            'unknown-unavailable' => ['status' => 'failed', 'reason' => 'persistence_outcome_unknown', 'retryable' => true],
            'stale' => ['status' => 'conflict', 'reason' => 'stale_revision', 'retryable' => false],
            'target-missing' => ['status' => 'conflict', 'reason' => 'target_not_found', 'retryable' => false],
            'target-not-current' => ['status' => 'conflict', 'reason' => 'target_not_current', 'retryable' => false],
            'wrong-root' => ['status' => 'conflict', 'reason' => 'semantic_collision', 'retryable' => false],
            'composition-drift' => ['status' => 'conflict', 'reason' => 'semantic_collision', 'retryable' => false],
            'no-change' => ['status' => 'rejected', 'reason' => 'no_changes', 'retryable' => false],
        ];
    }

    /** @return list<string> */
    public static function passivePdfCorpus(): array
    {
        return [
            'classic-xref', 'xref-stream', 'object-stream', 'malformed',
            'truncated', 'encrypted', 'zero-page', 'JavaScript', 'JS',
            'OpenAction', 'AA', 'Launch', 'EmbeddedFiles', 'Filespec',
            'FileAttachment', 'RichMedia', 'Movie', 'Sound', 'URI', 'GoToR',
            'SubmitForm', 'ImportData', 'unsupported-filter', 'xref-cycle',
            'object-limit', 'reference-depth-limit', 'decompression-limit',
        ];
    }

    /** @return list<string> */
    public static function acceptedStorageEvents(): array
    {
        return ['stage_begin', 'stage_write', 'stage_done', 'finalize_begin', 'finalize_done', 'stage_close'];
    }

    /** @return list<string> */
    public static function rejectedStorageEvents(): array
    {
        return ['stage_begin', 'stage_write', 'stage_done', 'abort_begin', 'abort_done', 'stage_close'];
    }

    /** @return array<string,string> */
    public static function unchangedProcessOracle(): array
    {
        return [
            'schema' => 'aoou-process-v1',
            'orderCompositionSha256' => str_repeat('a', 64),
            'caseSha256' => str_repeat('b', 64),
            'openingSha256' => str_repeat('c', 64),
            'tasksSha256' => str_repeat('d', 64),
            'checklistSha256' => str_repeat('f', 64),
            'decoySha256' => str_repeat('e', 64),
        ];
    }

    /** @return array<string,int|string|null|bool> */
    public static function maintenanceOracle(): array
    {
        return [
            'exactCapability' => 'assignment_order.original.storage.reconcile',
            'batchLimit' => 3,
            'scanned' => 3,
            'deleted' => 1,
            'retained' => 1,
            'failed' => 1,
            'status' => 'partial',
            'reason' => 'storage_failure',
            'retryable' => true,
        ];
    }
}
