<?php

declare(strict_types=1);

// YII2-SHLZ-VISUAL-CONTRACT-001: complete source ownership inventory; browser tests own interaction behavior.
require dirname(__DIR__) . '/bootstrap.php';

$root = dirname(__DIR__, 2);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$views = [
    '_otiz-nav.php', '_otiz-snapshot-list.php', 'activate.php', 'calendar.php',
    'checklist.php', 'completion.php', 'construction-control.php', 'dashboard.php',
    'deadline-certificates.php', 'execution.php', 'feedback-admin.php',
    'feedback-confirmation.php', 'feedback.php', 'installers.php', 'login.php',
    'object-card.php', 'objects.php', 'original-history.php', 'original.php',
    'otiz-snapshot.php', 'otiz.php', 'preopening-error.php', 'roles.php',
    'selection.php', 'users.php',
];

$check(count($views) === 25, 'complete 25-view inventory');
foreach ($views as $view) $check(is_file($root . '/app/YiiRuntime/Views/' . $view), 'active view exists ' . $view);

$css = (string) file_get_contents($root . '/app/YiiRuntime/Assets/pilot.css');
$selection = (string) file_get_contents($root . '/app/YiiRuntime/Views/selection.php');
$snapshot = (string) file_get_contents($root . '/app/YiiRuntime/Views/otiz-snapshot.php');
$check(preg_match('/<\/div>\s*<dialog class="shlz-modal fm2-selection-modal"/u', $selection) === 1, 'fixture proves dialog is a picker sibling');
$check(!str_contains($css, '[data-selection-picker] .fm2-selection-modal'), 'V03 actual sibling dialog owns responsive rules');
$check(!str_contains($css, '.fm2-shell:has(.fm2-otiz)'), 'V05 subject page does not own global shell geometry');
$check(str_contains($snapshot, '<dialog class="shlz-modal fm2-otiz-payment-dialog"'), 'V04 payment confirmation uses public modal element');
$check(!str_contains($snapshot, 'data-otiz-payment-dialog aria-hidden="true" role="dialog"'), 'V04 no ARIA-only payment dialog');
$check(preg_match('/\.fm2-otiz-archive\s+:is\(th,td\)[^{]*\{[^}]*text-overflow\s*:\s*ellipsis/s', $css) !== 1, 'V06 financial/history values are not irreversibly ellipsized');

$tableViews = ['objects.php', 'construction-control.php', 'installers.php', 'users.php', 'roles.php', 'otiz.php', 'otiz-snapshot.php', '_otiz-snapshot-list.php', 'deadline-certificates.php'];
foreach ($tableViews as $view) {
    $source = (string) file_get_contents($root . '/app/YiiRuntime/Views/' . $view);
    foreach (['shlz-table-wrap', 'shlz-table__head', 'shlz-table__row', 'shlz-table__cell'] as $token) {
        $check(str_contains($source, $token), 'V01 public data-list token ' . $token . ' in ' . $view);
    }
}

if ($failures !== []) throw new TestFailure('INTENDED_RED visual source ownership: ' . implode(' | ', $failures));
echo "PASS: YII2-SHLZ-VISUAL-CONTRACT-001 source ownership inventory\n";
