<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalRemainingMatrix.php';

use FMonitor2\Tests\Support\AssignmentOrderOriginalRemainingMatrix as Matrix;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v6, task 2.2 matrix.
$pdf = base64_decode(Matrix::POSITIVE_PDF_BASE64, true);
assertSameValue(true, is_string($pdf), 'Positive PDF oracle decodes strictly.');
assertSameValue(Matrix::POSITIVE_PDF_SIZE, strlen($pdf), 'Positive PDF size is independently fixed.');
assertSameValue(Matrix::POSITIVE_PDF_SHA256, hash('sha256', $pdf), 'Positive PDF digest is independently fixed.');

assertSameValue(65_536, Matrix::MAX_CHUNK_BYTES, 'Stage writes use the exact maximum chunk size.');
assertSameValue(20_971_520, Matrix::MAX_RECEIVED_BYTES, 'Received-byte inclusive limit is exact.');
// Behavioral coverage deliberately lives in the seven public-seam RED suites;
// this file now proves only the few independent literal inputs they share.
fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK\n");
