<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Per-fetch preflight. No credentials are retained by the client. */
final class DeliveryCredentials
{
    public static function read(BitrixWorkforceDeliveryConfig $c): string
    {
        foreach (['curl_init', 'curl_version', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_errno', 'posix_geteuid', 'hrtime'] as $function) {
            if (!function_exists($function)) self::fail();
        }
        foreach (['CURL_VERSION_ASYNCHDNS', 'CURLOPT_TIMEOUT_MS', 'CURLOPT_CONNECTTIMEOUT_MS', 'CURLINFO_PRETRANSFER_TIME_T'] as $constant) {
            if (!defined($constant)) self::fail();
        }
        if ((curl_version()['features'] & CURL_VERSION_ASYNCHDNS) === 0) self::fail();
        if ($c->caFile !== null) {
            self::regular($c->caFile, false);
            if (!is_readable($c->caFile)) self::fail();
        }
        $before = self::regular($c->tokenFile, true);
        if ($before['size'] > 1024) self::fail();
        $handle = @fopen($c->tokenFile, 'rb');
        if ($handle === false) self::fail();
        try {
            $opened = @fstat($handle);
            self::same($before, $opened);
            $token = @stream_get_contents($handle, 1025);
            self::same($opened, @fstat($handle));
            self::same($opened, self::regular($c->tokenFile, true));
            if (!is_string($token) || strlen($token) > 1024 || !feof($handle)) self::fail();
            if (str_ends_with($token, "\r\n")) $token = substr($token, 0, -2);
            elseif (str_ends_with($token, "\n")) $token = substr($token, 0, -1);
            if (preg_match('/^[A-Za-z0-9_-]{1,256}$/D', $token) !== 1) self::fail();
            return $token;
        } finally {
            fclose($handle);
        }
    }

    private static function regular(string $path, bool $secret): array
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if (@realpath($path) !== $path || $stat === false || ($stat['mode'] & 0170000) !== 0100000) self::fail();
        if ($secret && (($stat['mode'] & 07777) !== 0600 || $stat['nlink'] !== 1 || $stat['uid'] !== posix_geteuid())) self::fail();
        return $stat;
    }

    private static function same(array $before, array|false $after): void
    {
        if ($after === false) self::fail();
        foreach (['dev', 'ino', 'mode', 'uid', 'nlink', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) self::fail();
        }
    }

    private static function fail(): never
    {
        throw new DeliveryFailure(BitrixWorkforceDeliveryReason::ConfigurationUnavailable);
    }
}
