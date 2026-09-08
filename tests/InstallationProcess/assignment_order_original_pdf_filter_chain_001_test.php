<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfCorpus.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfHistoryCorpus.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus as Status;
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfHistoryCorpus as History;

// HISTORY-001 section4: opaque filter arrays contain allowed Names; codec execution is outside this seam.
// Repeating a codec in a chain is not a duplicate /Filter dictionary entry.
$inspector = new FMonitorPassivePdfInspector();
$cases = [
    'repeated-flate'=>['[/FlateDecode /FlateDecode]', Status::PASSIVE_PDF],
    'repeated-escaped-flate'=>['[/FlateDecode /Flate#44ecode]', Status::PASSIVE_PDF],
    'mixed-repeated-codec'=>['[/ASCII85Decode /FlateDecode /FlateDecode]', Status::PASSIVE_PDF],
    'distinct-codecs'=>['[/ASCII85Decode /FlateDecode]', Status::PASSIVE_PDF],
    'duplicate-dictionary-entry'=>['/FlateDecode /Filter /FlateDecode', Status::INVALID_PDF],
    'repeated-unknown-name'=>['[/Unknown /Unknown]', Status::INVALID_PDF],
];
$failures = [];
foreach ($cases as $name=>[$filter, $expected]) {
    $actual = $inspector->inspect(History::image($filter, 'opaque-data'))->status;
    if ($actual !== $expected) { $failures[] = $name; fwrite(STDOUT, "FAIL {$name}: expected {$expected->value}, actual {$actual->value}\n"); }
    else fwrite(STDOUT, "PASS {$name}\n");
}
assertSameValue([], $failures, 'Opaque codec multiplicity is distinct from forbidden duplicate dictionary keys.');
fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_PDF_FILTER_CHAIN_OK\n");
