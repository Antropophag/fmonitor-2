<?php

declare(strict_types=1);

$css = (string) file_get_contents(__DIR__ . '/pilot.css');
$failures = [];
$require = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

foreach (['--fm2-focus-ring: #46515e', '--fm2-focus-ring-inverse: #fff', '--fm2-focus-halo: rgb(70 81 94 / 18%)'] as $token) {
    $require(str_contains($css, $token), "missing neutral focus token: {$token}");
}

$contractStart = strpos($css, 'Rapid-pilot focus contract');
$require($contractStart !== false, 'missing scoped rapid-pilot focus contract');
$contract = $contractStart === false ? '' : substr($css, $contractStart);

$families = [
    'native link' => 'a,',
    'native button' => 'button,',
    'native input' => 'input,',
    'native select' => 'select,',
    'native textarea' => 'textarea,',
    'native summary' => 'summary,',
    'custom tabindex' => '[tabindex],',
    'shlz control' => '.shlz-control,',
    'shlz button' => '[class~="shlz-button"],',
    'shlz link' => '.shlz-link,',
    'shlz select trigger' => '.shlz-select__trigger,',
    'shlz select option' => '.shlz-select__option,',
    'shlz checkbox' => '.shlz-checkbox,',
    'shlz radio' => '.shlz-radio,',
    'shlz switch' => '.shlz-switch__input,',
    'shlz dropdown' => '.shlz-dropdown__item,',
    'shlz tabs' => '.shlz-tabs__tab,',
    'shlz pagination' => '.shlz-pagination__item,',
    'shlz modal/drawer close' => '.shlz-modal__close,',
    'shlz document action' => '.shlz-document-row__action',
];
foreach ($families as $family => $selector) {
    $require(str_contains($contract, $selector), "focus contract misses {$family}");
}

$require(str_contains($contract, '.shlz-field__control:focus-within'), 'field wrapper focus-within is not neutralized');
$require(str_contains($contract, '.shlz-select__trigger[aria-expanded="true"]'), 'expanded shlz Select paint is not neutralized');
$require(str_contains($contract, '.shlz-segment__input:focus-visible + .shlz-segment__label'), 'shlz segment focus is not neutralized');
$require(str_contains($contract, 'outline-color: var(--fm2-focus-ring);'), 'interactive focus does not use the neutral ring');
$require(str_contains($contract, 'box-shadow: 0 0 0 3px var(--fm2-focus-halo);'), 'field focus does not use the neutral halo');

preg_match_all('/([^{}]*:(?:focus|focus-visible|focus-within)[^{]*)\{([^}]*)\}/i', $css, $focusRules, PREG_SET_ORDER);
$blueFocusPattern = '/(?:#(?:253d98|6f8cff|8ea2e8)|var\(--(?:fm2-primary|check-blue|shlz-semantic-color-action-primary)\)|rgb\(22 72 120 \/ 14%\))/i';
foreach ($focusRules as $rule) {
    $selector = trim(preg_replace('/\s+/', ' ', $rule[1]) ?? $rule[1]);
    $require(preg_match($blueFocusPattern, $rule[2]) !== 1, "blue focus chrome remains in {$selector}");
}

$require(preg_match('/\.shlz-scope\s+\.shlz-field__control:focus-within[^}]*background:\s*var\(--fm2-surface\)[^}]*border-color:\s*var\(--fm2-focus-ring\)/s', $contract) === 1, 'focused fields must retain white paint and a neutral border');
$require(preg_match('/\.shlz-scope\s+\.fm2-object-filters\s+\.shlz-field__control:focus-within\s*\{[^}]*box-shadow:\s*none/s', $contract) === 1, 'object search shell must not draw a focus halo');
$require(preg_match('/\.shlz-scope\s+\.fm2-object-filters\s+\.shlz-input:focus-visible\s*\{[^}]*outline:\s*0[^}]*box-shadow:\s*none/s', $contract) === 1, 'object search input must not draw a second focus frame');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FOCUS_CONTRACT: {$failure}\n");
    }
    exit(1);
}

fwrite(STDOUT, 'Focus contract OK: native and shlz-ui interactive families use neutral, visible focus chrome.' . PHP_EOL);
