<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Tests\Support\ProtectedE2eAdmissionOracle;

// Inherited Gate 1: PILOT-E2E-RBAC-FIXTURES-001 admission amendment revision 3,
// c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e.
// HTML class tokens use ASCII TAB, LF, FF, CR and SPACE separators.
$positive = '<!doctype html><html lang="ru"><body><main id="main-content"><ul><li><a href="/pilot/objects/4512">4512</a><span>77-000123</span><span>Москва, ул. Примерная, д. 10</span><span>Подъезд 2</span><span>2026-10-05 — 2026-12-20</span></li></ul></main></body></html>';
try {
    assertSameValue(true, ProtectedE2eAdmissionOracle::matches($positive), 'independent positive fixture control');
    foreach (['FF' => "\f", 'TAB' => "\t", 'LF' => "\n", 'CR' => "\r", 'SPACE' => ' '] as $label => $separator) {
        $html = str_replace('</main>', '<div class="x' . $separator . 'shlz-table-wrap' . $separator . 'y"></div></main>', $positive);
        assertSameValue(false, ProtectedE2eAdmissionOracle::matches($html), 'RED_ASSERTION: forbidden class token separated by HTML ' . $label);
    }
    foreach (['x-shlz-table-wrap', 'shlz-table-wrapper', "x\u{00A0}shlz-table-wrap\u{00A0}y"] as $class) {
        $html = str_replace('</main>', '<div class="' . $class . '"></div></main>', $positive);
        assertSameValue(true, ProtectedE2eAdmissionOracle::matches($html), 'only a complete HTML class token is forbidden');
    }
    echo "PROTECTED_E2E_ADMISSION_CLASS_TOKENS_OK cases=9\n";
} catch (TestFailure $failure) {
    fwrite(STDERR, $failure->getMessage() . "\n");
    exit(1);
}
