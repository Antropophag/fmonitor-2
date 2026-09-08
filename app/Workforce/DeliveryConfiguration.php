<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Pure scalar validation; no filesystem, environment or transport access. */
final class DeliveryConfiguration
{
    public static function origin(BitrixWorkforceDeliveryConfig $c): string
    {
        $parts = parse_url($c->origin);
        if (strlen($c->origin) > 2048 || $parts === false || ($parts['scheme'] ?? null) !== 'https'
            || array_diff(array_keys($parts), ['scheme', 'host', 'port', 'path']) !== []
            || !in_array($parts['path'] ?? '', ['', '/'], true)
            || ($parts['port'] ?? 443) < 1 || ($parts['port'] ?? 443) > 65535
            || !self::host($parts['host'] ?? '') || preg_match('/[\x00-\x20\x7f]/', $c->origin) !== 0
            || $c->webhookUserId < 1 || !self::path($c->tokenFile)
            || ($c->caFile !== null && !self::path($c->caFile))
            || !array_is_list($c->departmentIds) || count($c->departmentIds) < 1 || count($c->departmentIds) > 100
            || $c->connectTimeoutSeconds < 1 || $c->connectTimeoutSeconds > 10
            || $c->requestTimeoutSeconds < $c->connectTimeoutSeconds || $c->requestTimeoutSeconds > 30
            || $c->deadlineSeconds < 1 || $c->deadlineSeconds > 300) self::fail();
        $previous = 0;
        foreach ($c->departmentIds as $id) {
            if (!is_int($id) || $id <= $previous) self::fail();
            $previous = $id;
        }
        return 'https://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    private static function host(string $host): bool
    {
        if ($host === '' || strlen($host) > 253) return false;
        if (preg_match('/^[0-9.]+$/D', $host) === 1) return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        foreach (explode('.', rtrim($host, '.')) as $label) {
            if (preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/D', $label) !== 1) return false;
        }
        return !str_ends_with($host, '..');
    }

    private static function path(string $path): bool
    {
        return $path !== '' && $path[0] === '/' && strlen($path) <= 4096 && !str_contains($path, "\0");
    }

    private static function fail(): never
    {
        throw new BitrixWorkforceDeliveryConfigurationUnavailable();
    }
}
