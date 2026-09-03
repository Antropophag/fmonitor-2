<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus;
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfOracle;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, section 5.
if (!class_exists(FMonitorPassivePdfInspector::class)) {
    throw new TestFailure('INTENDED_RED: owned production PDF seam is missing: ' . FMonitorPassivePdfInspector::class);
}
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalPdfOracle.php';

$inspector = new FMonitorPassivePdfInspector();
assertSameValue('fmonitor-passive-pdf-v1', $inspector->algorithmId(), 'Owned parser algorithm ID is immutable.');
$assert = static function (AssignmentOrderOriginalPdfStatus $expected, string $bytes, string $case) use ($inspector): void {
    assertSameValue($expected, $inspector->inspect($bytes)->status, $case);
};

$literal = base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK', true);
$assert(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF, $literal, 'Approved classic-xref literal.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, substr($literal, 0, -12), 'Truncated trailer is invalid.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, "%PDF-1.4\nnot-a-document\n%%EOF\n", 'Malformed structure is invalid.');
$assert(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF, AssignmentOrderOriginalPdfOracle::classic('', 1, '/Encrypt 9 0 R'), 'Encrypted document is unsafe.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::classic('', 0), 'Zero-page document is invalid.');

$forbidden = [
    'JavaScript' => '/Names << /JavaScript 9 0 R >>', 'JS' => '/OpenAction << /S /JavaScript /JS (x) >>',
    'OpenAction' => '/OpenAction 9 0 R', 'AA' => '/AA << /O 9 0 R >>', 'Launch' => '/OpenAction << /S /Launch >>',
    'EmbeddedFiles' => '/Names << /EmbeddedFiles 9 0 R >>', 'Filespec' => '/Oracle << /Type /Filespec >>',
    'FileAttachment' => '/Oracle << /Subtype /FileAttachment >>', 'RichMedia' => '/Oracle << /Subtype /RichMedia >>',
    'Movie' => '/Oracle << /Subtype /Movie >>', 'Sound' => '/Oracle << /Subtype /Sound >>',
    'URI' => '/OpenAction << /S /URI /URI (https://invalid.example) >>', 'GoToR' => '/OpenAction << /S /GoToR >>',
    'SubmitForm' => '/OpenAction << /S /SubmitForm >>', 'ImportData' => '/OpenAction << /S /ImportData >>',
];
foreach ($forbidden as $family => $dictionary) $assert(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF, AssignmentOrderOriginalPdfOracle::classic($dictionary), 'Forbidden action family: ' . $family);
$assert(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF, AssignmentOrderOriginalPdfOracle::xrefStream(), 'Passive xref-stream document.');
$assert(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF, AssignmentOrderOriginalPdfOracle::objectStream(), 'Passive object-stream document.');

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK\n");
