<?php

declare(strict_types=1);

require_once __DIR__ . '/legacy-migration/OtizPaidBeforeBalanceProfiler.php';

try {
    $options = getopt('', ['workbook:', 'mapping:', 'previous:']);
    if (!is_string($options['workbook'] ?? null) || !is_string($options['mapping'] ?? null)) throw new InvalidArgumentException('ARGUMENTS_INVALID');
    $mappingPath = (string) $options['mapping'];
    if (!is_file($mappingPath) || is_link($mappingPath)) throw new InvalidArgumentException('ARGUMENTS_INVALID');
    $mapping = json_decode((string) file_get_contents($mappingPath), true, flags: JSON_THROW_ON_ERROR);
    if (!is_array($mapping)) throw new InvalidArgumentException('ARGUMENTS_INVALID');
    foreach ($mapping as $registration => $ids) if (!is_string($registration) || !is_array($ids) || array_filter($ids, static fn(mixed $id): bool => !is_int($id) || $id < 1) !== []) throw new InvalidArgumentException('ARGUMENTS_INVALID');
    $previous = $options['previous'] ?? null;
    if ($previous !== null && !is_string($previous)) throw new InvalidArgumentException('ARGUMENTS_INVALID');
    $result = (new OtizPaidBeforeBalanceProfiler())->profile((string) $options['workbook'], $mapping, $previous);
    echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
} catch (InvalidArgumentException|JsonException) {
    fwrite(STDOUT, "{\"ok\":false,\"reason\":\"OTIZ_BALANCE_PROFILE_INVALID\"}\n"); exit(64);
} catch (Throwable) {
    fwrite(STDOUT, "{\"ok\":false,\"reason\":\"OTIZ_BALANCE_PROFILE_UNAVAILABLE\"}\n"); exit(69);
}
