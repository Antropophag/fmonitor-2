<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalRemainingMatrix.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalEvidenceReaderFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationWorkerBootstrap;
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalRemainingMatrix as Matrix;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v6, task 2.2 matrix.
$pdf = base64_decode(Matrix::POSITIVE_PDF_BASE64, true);
assertSameValue(true, is_string($pdf), 'Positive PDF oracle decodes strictly.');
assertSameValue(Matrix::POSITIVE_PDF_SIZE, strlen($pdf), 'Positive PDF size is independently fixed.');
assertSameValue(Matrix::POSITIVE_PDF_SHA256, hash('sha256', $pdf), 'Positive PDF digest is independently fixed.');

$oracles = Matrix::resultOracles();
foreach (['malformed', 'encrypted', 'zero-page', 'active-action', 'received-byte-20971521'] as $case) {
    assertSameValue(true, isset($oracles[$case]), "PDF boundary oracle exists for {$case}.");
}
foreach (['stale', 'target-missing', 'target-not-current', 'wrong-root', 'composition-drift', 'no-change'] as $case) {
    assertSameValue(true, isset($oracles[$case]), "Correction oracle exists for {$case}.");
}
foreach (['stream-failure', 'stage-failure', 'finalize-failure', 'rollback', 'unknown-absent', 'unknown-unavailable'] as $case) {
    assertSameValue(true, $oracles[$case]['retryable'], "Fault oracle is retryable for {$case}.");
}

assertSameValue(27, count(Matrix::passivePdfCorpus()), 'Owned parser corpus contains all approved grammar and active-key axes.');
assertSameValue(65_536, Matrix::MAX_CHUNK_BYTES, 'Stage writes use the exact maximum chunk size.');
assertSameValue(20_971_520, Matrix::MAX_RECEIVED_BYTES, 'Received-byte inclusive limit is exact.');
assertSameValue(
    ['stage_begin', 'stage_write', 'stage_done', 'finalize_begin', 'finalize_done', 'stage_close'],
    Matrix::acceptedStorageEvents(),
    'Accepted staging event order is independently fixed.',
);
assertSameValue(
    ['stage_begin', 'stage_write', 'stage_done', 'abort_begin', 'abort_done', 'stage_close'],
    Matrix::rejectedStorageEvents(),
    'Rejected completed input aborts and never finalizes.',
);

$process = Matrix::unchangedProcessOracle();
assertSameValue(
    ['schema', 'orderCompositionSha256', 'caseSha256', 'openingSha256', 'tasksSha256', 'checklistSha256', 'decoySha256'],
    array_keys($process),
    'Independent no-mutation shape includes the separately approved checklist digest.',
);
$maintenance = Matrix::maintenanceOracle();
assertSameValue($maintenance['scanned'], $maintenance['deleted'] + $maintenance['retained'] + $maintenance['failed'], 'Maintenance accounting is closed.');
assertSameValue('assignment_order.original.storage.reconcile', $maintenance['exactCapability'], 'Maintenance uses only its exact capability.');

$requiredProductionSeams = [
    AssignmentOrderOriginalVerificationFactory::class,
    FMonitorPassivePdfInspector::class,
    AssignmentOrderOriginalEvidenceReaderFactory::class,
    AssignmentOrderOriginalMaintenanceVerificationFactory::class,
    AssignmentOrderOriginalVerificationWorkerBootstrap::class,
];
foreach ($requiredProductionSeams as $seam) {
    if (!class_exists($seam)) {
        throw new TestFailure("INTENDED_RED: approved production seam {$seam} is absent.");
    }
}

fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_REMAINING_CONTRACT_ORACLES_OK\n");
