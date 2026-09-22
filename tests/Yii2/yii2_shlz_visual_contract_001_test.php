<?php

declare(strict_types=1);

// YII2-SHLZ-VISUAL-CONTRACT-001 A-G: source-level contract at the active Yii presentation seam.
require dirname(__DIR__) . '/bootstrap.php';

$root = dirname(__DIR__, 2);
$views = [
    '_otiz-nav.php', '_otiz-snapshot-list.php', 'activate.php', 'calendar.php',
    'checklist.php', 'completion.php', 'construction-control.php', 'dashboard.php',
    'deadline-certificates.php', 'execution.php', 'feedback-admin.php',
    'feedback-confirmation.php', 'feedback.php', 'installers.php', 'login.php',
    'object-card.php', 'objects.php', 'original-history.php', 'original.php',
    'otiz-snapshot.php', 'otiz.php', 'preopening-error.php', 'roles.php',
    'selection.php', 'users.php',
];

assertSameValue(25, count($views), 'complete 25-view inventory');
foreach ($views as $view) {
    assertSameValue(true, is_file($root . '/app/YiiRuntime/Views/' . $view), 'active view exists ' . $view);
}

$support = (string) file_get_contents($root . '/app/YiiRuntime/ViewSupport.php');
assertSameValue(true, str_contains($support, 'function field('), 'INTENDED_RED full Field owns one explicit API');
assertSameValue(true, str_contains($support, 'function choiceControl('), 'INTENDED_RED bare choice Control has an unambiguous API');

$installers = (string) file_get_contents($root . '/app/YiiRuntime/Views/installers.php');
assertSameValue(false, preg_match('/<label class="shlz-field">[\s\S]*?<span class="shlz-field__control"><\?=\s*\$(?:status|availability)Control\s*\?>/u', $installers) === 1, 'INTENDED_RED installer filters do not nest full Fields');

$selection = (string) file_get_contents($root . '/app/YiiRuntime/Views/selection.php');
assertSameValue(true, preg_match('/<\/div>\s*<dialog class="shlz-modal fm2-selection-modal"/u', $selection) === 1, 'fixture proves dialog is a picker sibling');
$css = (string) file_get_contents($root . '/app/YiiRuntime/Assets/pilot.css');
assertSameValue(false, str_contains($css, '[data-selection-picker] .fm2-selection-modal'), 'INTENDED_RED selection modal rules target the actual sibling dialog');
assertSameValue(false, str_contains($css, '.fm2-shell:has(.fm2-otiz)'), 'INTENDED_RED subject page does not own global shell geometry');

$snapshot = (string) file_get_contents($root . '/app/YiiRuntime/Views/otiz-snapshot.php');
assertSameValue(true, str_contains($snapshot, '<dialog class="shlz-modal fm2-otiz-payment-dialog"'), 'INTENDED_RED payment confirmation uses the public modal element');
assertSameValue(false, str_contains($snapshot, 'data-otiz-payment-dialog aria-hidden="true" role="dialog"'), 'INTENDED_RED no ARIA-only payment dialog');
$otizJs = (string) file_get_contents($root . '/app/YiiRuntime/Assets/otiz.js');
foreach (['showModal()', '.close()', "keydown", 'Escape'] as $needle) {
    assertSameValue(true, str_contains($otizJs, $needle), 'INTENDED_RED payment modal focus/close lifecycle ' . $needle);
}

foreach (['objects.php', 'construction-control.php', 'installers.php', 'users.php', 'roles.php', 'otiz.php', 'otiz-snapshot.php', '_otiz-snapshot-list.php', 'deadline-certificates.php'] as $view) {
    $source = (string) file_get_contents($root . '/app/YiiRuntime/Views/' . $view);
    assertSameValue(true, str_contains($source, 'shlz-table-wrap'), 'INTENDED_RED data list has local scroll wrapper ' . $view);
    assertSameValue(true, str_contains($source, 'shlz-table__head'), 'INTENDED_RED data list has public head contract ' . $view);
    assertSameValue(true, str_contains($source, 'shlz-table__row'), 'INTENDED_RED data list has public row contract ' . $view);
    assertSameValue(true, str_contains($source, 'shlz-table__cell'), 'INTENDED_RED data list has public cell contract ' . $view);
}

assertSameValue(false, preg_match('/\.fm2-otiz-archive\s+:is\(th,td\)[^{]*\{[^}]*text-overflow\s*:\s*ellipsis/s', $css) === 1, 'INTENDED_RED financial/history values are not irreversibly ellipsized');
assertSameValue(true, str_contains($css, 'min-inline-size:44px') || str_contains($css, 'min-width:44px'), 'INTENDED_RED touch controls expose a 44px minimum target');

echo "PASS: YII2-SHLZ-VISUAL-CONTRACT-001 source contract\n";
