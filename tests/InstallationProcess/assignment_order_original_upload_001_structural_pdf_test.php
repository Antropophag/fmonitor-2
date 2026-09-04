<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus;
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfOracle;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v4, section 5.
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalPdfOracle.php';

$inspector = new FMonitorPassivePdfInspector();
$assert = static function (AssignmentOrderOriginalPdfStatus $expected, string $bytes, string $scenario) use ($inspector): void {
    assertSameValue($expected, $inspector->inspect($bytes)->status, $scenario);
};

$assert(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF, AssignmentOrderOriginalPdfOracle::nonZeroCatalogGeneration(), 'Classic xref honors exact non-zero object generation.');
$assert(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF, AssignmentOrderOriginalPdfOracle::xrefStream(), 'Binary xref stream is structurally resolved.');

$hidden = [
    'JavaScript' => '<< /S /JavaScript >>',
    'JS' => '<< /JS (app.alert) >>',
    'OpenAction' => '<< /OpenAction null >>',
    'AA' => '<< /AA << >> >>',
    'Launch' => '<< /S /Launch >>',
    'EmbeddedFiles' => '<< /Names << /EmbeddedFiles << >> >> >>',
    'Filespec' => '<< /Type /Filespec >>',
    'FileAttachment' => '<< /Subtype /FileAttachment >>',
    'RichMedia' => '<< /Subtype /RichMedia >>',
    'Movie' => '<< /Subtype /Movie >>',
    'Sound' => '<< /Subtype /Sound >>',
    'URI' => '<< /S /URI /URI (https://invalid.example) >>',
    'GoToR' => '<< /S /GoToR >>',
    'SubmitForm' => '<< /S /SubmitForm >>',
    'ImportData' => '<< /S /ImportData >>',
];
foreach ($hidden as $family => $dictionary) {
    $assert(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF, AssignmentOrderOriginalPdfOracle::flateObjectStream($dictionary), 'Flate object stream hides forbidden ' . $family . '.');
}

$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::wrongObjectOffset(), 'Classic xref entry with wrong byte offset fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::catalogGenerationMismatch(), 'Root reference generation must match its active xref entry.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::malformedXrefStream(), 'Malformed xref-stream field widths fail closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::unsupportedStructuralFilter(), 'Unsupported structural stream filter fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::conflictingXrefIdentity(), 'Conflicting duplicate xref identity fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::objectCountAboveLimit(), 'Declared object namespace above 100000 fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::aggregateStructuralInflationAboveLimit(), 'Aggregate structural decompression above 67108864 bytes fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::cyclicPagesTree(), 'Cyclic Pages graph fails closed despite a decoy Page object.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::overDepthGraph(), 'Reference graph beyond depth 100 fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::latestRootHasZeroPages(), 'Prev chain resolves the latest Root rather than accepting obsolete positive pages.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::prevCycle(), 'Cyclic Prev chain fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF, AssignmentOrderOriginalPdfOracle::classic('', 1, '/Encrypt 9 0 R'), 'Encryption is unsafe.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::classic('', 0), 'Zero-page tree is invalid.');

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_STRUCTURAL_PDF_OK\n");
