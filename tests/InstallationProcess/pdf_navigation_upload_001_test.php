<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php';
require_once dirname(__DIR__).'/Support/AssignmentOrderOriginalPdfCorpus.php';
use FMonitor2\AssignmentOrderOriginal\{FMonitorPassivePdfInspector,AssignmentOrderOriginalPdfStatus as Status};
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus as Corpus;
// PDF-NAVIGATION-UPLOAD-001: actual public upload inspector, synthetic document.
function navigationPdf(string $open, ?string $uri): string {
    return Corpus::classic([
        '<< /Type /Catalog /Pages 2 0 R '.$open.' >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] /Annots [4 0 R] >>',
        $uri === null ? '<< /Type /Annot /Subtype /Link /Rect [0 0 10 10] >>' : '<< /Type /Annot /Subtype /Link /Rect [0 0 10 10] /A << /S /URI /URI '.$uri.' >> >>',
    ]);
}
$inspector = new FMonitorPassivePdfInspector();
assertSameValue(Status::PASSIVE_PDF, $inspector->inspect(navigationPdf('/OpenAction [3 0 R /FitH null]', null))->status, 'OpenAction alone accepted');
assertSameValue(Status::PASSIVE_PDF, $inspector->inspect(navigationPdf('', '(https://example.org)'))->status, 'URI alone accepted');
foreach (['(https://example.org)', '(http://example.org)', '(mailto:test@example.org)', '(HTTPS://example.org)', '(h\\164tp://example.org)', '<68747470733a2f2f6578616d706c652e6f7267>'] as $uri) {
    assertSameValue(Status::PASSIVE_PDF, $inspector->inspect(navigationPdf('/OpenAction [3 0 R /FitH null]', $uri))->status, 'own template navigation accepted '.$uri);
}
foreach (['/OpenAction << /S /JavaScript /JS (alert) >>', '/OpenAction << /S /Launch /F (test) >>', '/OpenAction 4 0 R'] as $open) {
    assertSameValue(Status::UNSAFE_PDF, $inspector->inspect(navigationPdf($open, '(https://example.org)'))->status, 'active opening rejected');
}
foreach (['(javascript:alert)', '(file:///tmp/test)', '( https://example.org)', '(httpsx://example.org)', '(javascript%3Aalert)', '(java\\163cript:alert)', '<6a6176617363726970743a616c657274>', '3 0 R'] as $uri) {
    assertSameValue(Status::UNSAFE_PDF, $inspector->inspect(navigationPdf('/OpenAction [3 0 R /FitH null]', $uri))->status, 'unsafe link rejected');
}
foreach (['Fit', 'FitB', 'FitH null', 'FitV 10', 'FitBH null', 'FitBV 0', 'XYZ null null 1', 'FitR 0 0 72 72'] as $destination) {
    assertSameValue(Status::PASSIVE_PDF, $inspector->inspect(navigationPdf('/OpenAction [3 0 R /'.$destination.']', '(https://example.org)'))->status, 'page destination '.$destination);
}
assertSameValue(Status::UNSAFE_PDF, $inspector->inspect(navigationPdf('/OpenAction [3 0 R /FitH null] /AA << /S /JavaScript /JS (alert) >>', '(https://example.org)'))->status, 'navigation does not waive scripts');
foreach (['[3 0 R /FitR]', '[3 0 R /FitR null 0 72 72]', '[3 0 R /Fit 0]', '[3 0 R /Unknown]', '[[3 0 R /Fit]]'] as $destination) {
    assertSameValue(Status::UNSAFE_PDF, $inspector->inspect(navigationPdf('/OpenAction '.$destination, null))->status, 'unsupported destination rejected');
}
// Rebuild xref through the corpus rather than changing byte lengths in a PDF.
assertSameValue(Status::UNSAFE_PDF, $inspector->inspect(Corpus::classic([
    '<< /Type /Catalog /Pages 2 0 R /A << /S /URI /Foo /S /URI (javascript:alert) >> >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>',
]))->status, 'unrelated S value cannot waive URI target validation');
echo "PASS PDF-NAVIGATION-UPLOAD-001\n";
