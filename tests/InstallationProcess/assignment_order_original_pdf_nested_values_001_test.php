<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfCorpus.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfHistoryCorpus.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalPdfStatus as Status;
use FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfHistoryCorpus as History;

// HISTORY-001§2/3 requires valid structure/tokenization; nested dictionaries keep Name keys and one value per key.
$inspector = new FMonitorPassivePdfInspector();
$cases = [
    'nested-integer-key'=>['<< /Metadata << 7 /Value >> >>', Status::INVALID_PDF],
    'nested-string-key'=>['<< /Metadata << (key) /Value >> >>', Status::INVALID_PDF],
    'nested-missing-value'=>['<< /Metadata << /Key >> >>', Status::INVALID_PDF],
    'array-dictionary-missing-value'=>['[ << /Key >> ]', Status::INVALID_PDF],
    'nested-extra-value'=>['<< /Metadata << /Key true false >> >>', Status::INVALID_PDF],
    'nested-bare-reference-marker'=>['<< /Metadata << /Key R >> >>', Status::INVALID_PDF],
    'nested-name-key'=>['<< /Metadata << /Key /Value >> >>', Status::PASSIVE_PDF],
    'nested-reference-value'=>['<< /Metadata << /Key 3 0 R >> >>', Status::PASSIVE_PDF],
    'nested-array-values'=>['[ << /Key [true false null 1 2.5 /Name () <>] >> ]', Status::PASSIVE_PDF],
    'empty-nested-containers'=>['<< /Metadata << /Dict << >> /Array [] >> >>', Status::PASSIVE_PDF],
];
$failures = [];
foreach ($cases as $name=>[$body, $expected]) {
    $actual = $inspector->inspect(History::direct($body))->status;
    if ($actual !== $expected) { $failures[] = $name; fwrite(STDOUT, "FAIL {$name}: expected {$expected->value}, actual {$actual->value}\n"); }
    else fwrite(STDOUT, "PASS {$name}\n");
}
assertSameValue([], $failures, 'Nested container syntax obeys the same PDF dictionary/value grammar.');
fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_PDF_NESTED_VALUES_OK\n");
