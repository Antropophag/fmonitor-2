<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Bounded received-byte validation; resources stay with the scope. */
final class AssignmentOrderOriginalPdfAcquisition
{
    /** @return array{string,int} Received SHA-256 and byte count. */
    public static function inspect(AssignmentOrderOriginalResourceScope $resources, AssignmentOrderOriginalPdfInspector $inspector, string $mediaType): array
    {
        $resources->begin();
        $bytes = '';
        while (true) {
            $read = $resources->read();
            if ($read->status === AssignmentOrderOriginalStreamReadStatus::EOF) break;
            $bytes .= $read->bytes;
            if (strlen($bytes) > 20971520) throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::FILE_TOO_LARGE);
            $resources->write($read->bytes);
        }
        $resources->done();
        if (strtolower($mediaType) !== 'application/pdf' || strlen($bytes) < 5 || substr($bytes, 0, 5) !== '%PDF-')
            throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::NOT_PDF);
        $completed = $resources->completed();
        try { $inspection = $inspector->inspect($completed); }
        catch (\Throwable) { throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE); }
        if ($inspection->status === AssignmentOrderOriginalPdfStatus::INVALID_PDF)
            throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::INVALID_PDF);
        if ($inspection->status === AssignmentOrderOriginalPdfStatus::UNSAFE_PDF)
            throw AssignmentOrderOriginalSubmissionFailure::rejected(AssignmentOrderOriginalReason::UNSAFE_PDF);
        if ($inspection->status !== AssignmentOrderOriginalPdfStatus::PASSIVE_PDF)
            throw AssignmentOrderOriginalSubmissionFailure::technical(AssignmentOrderOriginalReason::STORAGE_FAILURE);
        return [hash('sha256', $bytes), strlen($bytes)];
    }
}
