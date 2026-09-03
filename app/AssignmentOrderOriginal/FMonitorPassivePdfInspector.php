<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

require_once __DIR__ . '/AssignmentOrderOriginalApplication.php';

final class FMonitorPassivePdfInspector implements AssignmentOrderOriginalPdfInspector
{
    public const ALGORITHM_ID = 'fmonitor-passive-pdf-v1';

    public function algorithmId(): string
    {
        return self::ALGORITHM_ID;
    }

    public function inspect(string $completedBytes): AssignmentOrderOriginalPdfInspection
    {
        if (preg_match('/\A%PDF-1\.[4-7]\R/', $completedBytes) !== 1
            || preg_match('/startxref\R([0-9]+)\R%%EOF\R?\z/', $completedBytes, $match) !== 1) {
            return AssignmentOrderOriginalPdfInspection::invalid();
        }

        $xrefOffset = (int) $match[1];
        if ($xrefOffset <= 0 || $xrefOffset >= strlen($completedBytes)) {
            return AssignmentOrderOriginalPdfInspection::invalid();
        }
        $xref = substr($completedBytes, $xrefOffset);
        if (!str_starts_with($xref, "xref\n")
            && preg_match('/\A[0-9]+ 0 obj\R<<[^>]*\/Type\s*\/XRef\b/s', $xref) !== 1) {
            return AssignmentOrderOriginalPdfInspection::invalid();
        }

        if (preg_match('/\/Encrypt\b/', $completedBytes) === 1) {
            return AssignmentOrderOriginalPdfInspection::unsafe();
        }
        if (preg_match('/\/(?:JavaScript|JS|OpenAction|AA|Launch|EmbeddedFiles|Filespec|FileAttachment|RichMedia|Movie|Sound|URI|GoToR|SubmitForm|ImportData)\b/', $completedBytes) === 1) {
            return AssignmentOrderOriginalPdfInspection::unsafe();
        }
        if (preg_match('/\/Type\s*\/Catalog\b/', $completedBytes) !== 1
            || preg_match('/\/Type\s*\/Pages\b[^>]*\/Count\s+([1-9][0-9]*)\b/s', $completedBytes) !== 1
            || preg_match('/\/Type\s*\/Page\b/', $completedBytes) !== 1) {
            return AssignmentOrderOriginalPdfInspection::invalid();
        }

        return AssignmentOrderOriginalPdfInspection::passive();
    }
}
