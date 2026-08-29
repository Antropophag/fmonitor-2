<?php

declare(strict_types=1);

if ($argc !== 3) throw new RuntimeException('Expected legacy config and output paths');
[$script, $legacyPath, $outputPath] = $argv;
$legacy = is_file($legacyPath) ? file_get_contents($legacyPath) : false;
if (!is_string($legacy)
    || preg_match("/queryUrl\s*=\s*'([^']+)'/", $legacy, $url) !== 1
    || preg_match("/'UF_DEPARTMENT'\s*=>\s*\[([^]]+)\]/", $legacy, $departmentSource) !== 1) {
    throw new RuntimeException('Bitrix workforce configuration is unavailable in the sibling legacy checkout');
}
preg_match_all("/'([0-9]+)'/", $departmentSource[1], $departmentMatches);
$departments = array_values(array_unique($departmentMatches[1]));
if ($departments === [] || filter_var($url[1], FILTER_VALIDATE_URL) === false) throw new RuntimeException('Bitrix workforce configuration is invalid');
$json = json_encode(['baseUrl' => rtrim($url[1], '/') . '/', 'departments' => $departments], JSON_THROW_ON_ERROR);
$temporary = $outputPath . '.new';
if (file_put_contents($temporary, $json, LOCK_EX) === false || !chmod($temporary, 0600) || !rename($temporary, $outputPath)) {
    @unlink($temporary);
    throw new RuntimeException('Cannot write local Bitrix secret');
}
