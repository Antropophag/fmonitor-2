<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\Tests\Support\ProtectedE2eAdmissionOracle;

// PILOT-E2E-RBAC-FIXTURES-001 admission amendment revision 3.
// Owner-approved specification SHA256:
// c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e
// Literal inputs below come from that specification, never the renderer.
try {
    assertSameValue(true, class_exists(ProtectedE2eAdmissionOracle::class)
        && is_callable([ProtectedE2eAdmissionOracle::class, 'matches']),
        'RED_ASSERTION: public ProtectedE2eAdmissionOracle::matches is missing');

    $positive = '<!doctype html><html lang="ru"><body><main id="main-content"><ul><li><a href="/pilot/objects/4512">4512</a><span>77-000123</span><span>Москва, ул. Примерная, д. 10</span><span>Подъезд 2</span><span>2026-10-05 — 2026-12-20</span></li></ul></main></body></html>';
    $combined = '<!doctype html><html lang="ru"><body><nav><ul><li>Навигация</li></ul></nav><main id="main-content"><ul><li><div><a href="/pilot/objects/4512">4512</a><strong>77-000123</strong></div><div>Москва, ул. Примерная, д. 10 · Подъезд 2</div><div>2026-10-05 — 2026-12-20</div></li></ul></main></body></html>';
    $anchor = '<a href="/pilot/objects/4512">4512</a>';
    $item = '<li>' . $anchor . '<span>77-000123</span><span>Москва, ул. Примерная, д. 10</span><span>Подъезд 2</span><span>2026-10-05 — 2026-12-20</span></li>';
    $cases = [
        'literal unordered list' => [$positive, true],
        'literal ordered list' => [str_replace(['<ul>', '</ul>'], ['<ol>', '</ol>'], $positive), true],
        'combined text and external navigation' => [$combined, true],
        'missing anchor' => [str_replace($anchor, '', $positive), false],
        'duplicate complete item' => [str_replace($item, $item . $item, $positive), false],
        'wrong href only' => [str_replace('/pilot/objects/4512', '/pilot/objects/4513', $positive), false],
        'wrong anchor text only' => [str_replace('>4512</a>', '>4513</a>', $positive), false],
        'div is not a list item' => [str_replace(['<li>', '</li>'], ['<div>', '</div>'], $positive), false],
        'wrong registration' => [str_replace('77-000123', '77-000124', $positive), false],
        'wrong address' => [str_replace('д. 10', 'д. 11', $positive), false],
        'wrong entrance' => [str_replace('Подъезд 2', 'Подъезд 3', $positive), false],
        'wrong start' => [str_replace('2026-10-05', '2026-10-06', $positive), false],
        'wrong finish' => [str_replace('2026-12-20', '2026-12-21', $positive), false],
        'forbidden native table' => [str_replace('</main>', '<table></table></main>', $positive), false],
        'forbidden wrapper class token' => [str_replace('</main>', '<div class="x shlz-table-wrap y"></div></main>', $positive), false],
        'anchor only in external navigation' => [str_replace('<body>', '<body><nav><ul><li>' . $anchor . '</li></ul></nav>', str_replace($anchor, '', $positive)), false],
        'duplicate main id' => [str_replace('</body>', '<main id="main-content"></main></body>', $positive), false],
        'entrance 22 is not 2' => [str_replace('Подъезд 2', 'Подъезд 22', $combined), false],
        'entrance 20 is not 2' => [str_replace('Подъезд 2', 'Подъезд 20', $combined), false],
        'house 100 is not 10' => [str_replace('д. 10', 'д. 100', $combined), false],
        'second canonical anchor outside item in main' => [str_replace('</ul>', '</ul>' . $anchor, $positive), false],
        'facts only in sibling item' => [str_replace($item, '<li>' . $anchor . '</li><li><span>77-000123</span><span>Москва, ул. Примерная, д. 10</span><span>Подъезд 2</span><span>2026-10-05 — 2026-12-20</span></li>', $positive), false],
        'list item lacks semantic parent' => [str_replace(['<ul>', '</ul>'], ['<div>', '</div>'], $positive), false],
        'invalid UTF-8' => [$positive . "\xFF", false],
        'Unicode whitespace normalization' => [str_replace(['>4512</a>', 'Подъезд 2'], [">\u{00A0}4512\n</a>", "Подъезд\u{00A0}2"], $positive), true],
        'Unicode letter neighbor blocks registration' => [str_replace('77-000123', 'я77-000123', $positive), false],
        'literal punctuation is not wildcard' => [str_replace('77-000123', '77x000123', $positive), false],
        'wrapper substring alone is allowed' => [str_replace('</main>', '<div class="x-shlz-table-wrap"></div></main>', $positive), true],
    ];
    foreach ($cases as $label => [$html, $expected]) {
        ob_start();
        try {
            $actual = ProtectedE2eAdmissionOracle::matches($html);
        } finally {
            $output = ob_get_clean();
        }
        assertSameValue('', $output, 'oracle emits no output: ' . $label);
        assertSameValue($expected, $actual, 'admission representation: ' . $label);
    }
    echo 'PROTECTED_E2E_ADMISSION_ORACLE_OK cases=' . count($cases) . "\n";
} catch (TestFailure $failure) {
    fwrite(STDERR, $failure->getMessage() . "\n");
    exit(1);
}
