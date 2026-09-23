<?php

declare(strict_types=1);

// YII2-SHLZ-VISUAL-CONTRACT-001 V07/V08: explicit ownership inventory for secondary forms and common overlays.
require dirname(__DIR__) . '/bootstrap.php';

$root = dirname(__DIR__, 2);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$view = static fn(string $name): string => (string) file_get_contents($root . '/app/YiiRuntime/Views/' . $name);

$certificates = $view('deadline-certificates.php');
foreach (['certificateDate', 'newDeadline', 'correctionReason', 'pdf'] as $name) {
    $expect(preg_match('/<label class="shlz-field"[^>]*>[\s\S]*?<[^>]+name="' . preg_quote($name, '/') . '"/u', $certificates) === 1, 'V07 deadline certificate field ' . $name . ' uses one shared Field');
}

$users = $view('users.php');
$expect(preg_match('/<label class="shlz-field"[^>]*>[\s\S]*?<input[^>]+name="legacyUserId"/u', $users) === 1, 'V07 legacy identity link uses shared Field');

$checklist = $view('checklist.php');
foreach (['data-bulk-dialog', 'data-reason-dialog', 'data-installer-dialog'] as $marker) {
    $expect(preg_match('/<dialog[^>]*class="[^"]*shlz-modal[^"]*"[^>]*' . $marker . '/u', $checklist) === 1, 'V08 checklist dialog uses public modal ' . $marker);
}
$expect(!str_contains($checklist, 'class="fm2-toast"'), 'V08 checklist does not own a local common toast primitive');

$objects = $view('objects.php');
$expect(preg_match('/<dialog[^>]*class="[^"]*shlz-modal[^"]*"[^>]*data-inspection-dialog/u', $objects) === 1, 'V08 inspection dialog uses public modal');

if ($failures !== []) throw new TestFailure('INTENDED_RED V07/V08 shared forms and overlays: ' . implode(' | ', $failures));
echo "PASS: YII2-SHLZ-VISUAL-CONTRACT-001 V07/V08 inventory\n";
