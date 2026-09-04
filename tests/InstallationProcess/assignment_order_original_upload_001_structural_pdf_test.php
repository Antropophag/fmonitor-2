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
    'JavaScript' => '<< /S /JavaScript /JS (app.alert) >>',
    'OpenAction' => '<< /OpenAction 8 0 R >>',
    'AA' => '<< /AA << /O 8 0 R >> >>',
    'Launch' => '<< /S /Launch >>',
    'EmbeddedFiles' => '<< /Names << /EmbeddedFiles 8 0 R >> >>',
    'FileAttachment' => '<< /Subtype /FileAttachment >>',
    'URI' => '<< /S /URI /URI (https://invalid.example) >>',
    'GoToR' => '<< /S /GoToR >>',
];
foreach ($hidden as $family => $dictionary) {
    $assert(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF, AssignmentOrderOriginalPdfOracle::flateObjectStream($dictionary), 'Flate object stream hides forbidden ' . $family . '.');
}

$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::wrongObjectOffset(), 'Classic xref entry with wrong byte offset fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::cyclicPagesTree(), 'Cyclic Pages graph fails closed despite a decoy Page object.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::overDepthGraph(), 'Reference graph beyond depth 100 fails closed.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::latestRootHasZeroPages(), 'Prev chain resolves the latest Root rather than accepting obsolete positive pages.');
$assert(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF, AssignmentOrderOriginalPdfOracle::classic('', 1, '/Encrypt 9 0 R'), 'Encryption is unsafe.');
$assert(AssignmentOrderOriginalPdfStatus::INVALID_PDF, AssignmentOrderOriginalPdfOracle::classic('', 0), 'Zero-page tree is invalid.');

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_STRUCTURAL_PDF_OK\n");
