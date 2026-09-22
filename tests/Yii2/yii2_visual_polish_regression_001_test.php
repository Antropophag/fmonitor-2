<?php

declare(strict_types=1);

// YII2-SHLZ-VISUAL-CONTRACT-001 owner screenshot correction: tables, buttons and open modal composition.
require dirname(__DIR__) . '/bootstrap.php';

$root = dirname(__DIR__, 2);
$css = (string) file_get_contents($root . '/app/YiiRuntime/Assets/pilot.css');
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$check(
    preg_match('/body\.shlz-scope\s+\.shlz-table__head\s+\.shlz-table__cell\s*\{[^}]*background\s*:\s*var\(--shlz-semantic-color-surface-muted\)/s', $css) === 1,
    'all table headers use one shared muted surface'
);
$check(
    preg_match('/\.fm2-otiz-(?:register-table|archive)[^{]*thead[^\{]*\{[^}]*background\s*:/s', $css) !== 1,
    'OTIZ does not recolor the shared table header'
);
$check(
    preg_match('/body\.shlz-scope\s+\.shlz-button\s*\{[^}]*white-space\s*:\s*nowrap[^}]*overflow-wrap\s*:\s*normal/s', $css) === 1,
    'button labels never wrap'
);
$check(
    preg_match('/\.shlz-button[^\{]*\{[^}]*white-space\s*:\s*normal/s', $css) !== 1,
    'responsive rules do not re-enable button wrapping'
);

foreach (['checklist.php', 'objects.php', 'otiz-snapshot.php', 'selection.php'] as $viewName) {
    $view = (string) file_get_contents($root . '/app/YiiRuntime/Views/' . $viewName);
    foreach (['shlz-modal__surface', 'shlz-modal__header', 'shlz-modal__body', 'shlz-modal__footer'] as $part) {
        $check(str_contains($view, $part), $viewName . ' uses public modal part ' . $part);
    }
}

$check(
    preg_match('/\.fm2-(?:confirm|installer|inspection|otiz-payment)-dialog(?:__surface)?\s*\{[^}]*(?:background|box-shadow|border-radius)\s*:/s', $css) !== 1,
    'local modal families do not redraw the public surface'
);

if ($failures !== []) throw new TestFailure('INTENDED_RED owner screenshot visual consistency: ' . implode(' | ', $failures));
echo "PASS: YII2-SHLZ-VISUAL-CONTRACT-001 owner screenshot correction\n";
