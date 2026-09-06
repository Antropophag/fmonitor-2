<?php

declare(strict_types=1);

namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Validates trusted connection inputs before credential contents or DB access. */
final class AssignmentOrderOriginalFreshConnection
{
    public static function host(AssignmentOrderOriginalFreshReaderConfig $c): string
    {
        if ($c->databasePort < 1 || $c->databasePort > 65535
            || preg_match('/^[A-Za-z0-9_]{1,64}$/D', $c->databaseName) !== 1
            || preg_match('/^[A-Za-z0-9_.-]{1,32}$/D', $c->databaseUser) !== 1
            || preg_match('/^[A-Za-z0-9_]{0,25}$/D', $c->tablePrefix) !== 1) AssignmentOrderOriginalSql::fail();
        $host = $c->databaseHost;
        if (strlen($host) <= 255 && preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._-]{0,253}[A-Za-z0-9])?$/D', $host) === 1) return $host;
        if (preg_match('/^\[([0-9a-f:]+)\]$/D', $host, $parts) === 1
            && filter_var($parts[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
            && inet_ntop(inet_pton($parts[1])) === $parts[1]) return $parts[1];
        AssignmentOrderOriginalSql::fail();
    }

    public static function password(string $path): string
    {
        $repository = dirname(__DIR__, 2);
        if ($path === '' || $path[0] !== '/' || preg_match('/[\x00-\x1F\x7F]/', $path) !== 0
            || in_array('..', explode('/', $path), true) || realpath($path) !== $path
            || $path === $repository || str_starts_with($path, $repository.'/')) AssignmentOrderOriginalSql::fail();
        $before = @lstat($path);
        $uid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
        if ($before === false || ($before['mode'] & 0170000) !== 0100000 || ($before['mode'] & 07777) !== 0600
            || !in_array($before['uid'], [0, $uid], true)) AssignmentOrderOriginalSql::fail();
        $handle = @fopen($path, 'rb');
        if ($handle === false) AssignmentOrderOriginalSql::fail();
        try {
            $opened = fstat($handle);
            if ($opened === false || $opened['dev'] !== $before['dev'] || $opened['ino'] !== $before['ino']
                || $opened['mode'] !== $before['mode'] || $opened['uid'] !== $before['uid']) AssignmentOrderOriginalSql::fail();
            $bytes = stream_get_contents($handle, 1026);
            clearstatcache(true, $path);
            $after = @lstat($path);
            if ($bytes === false || $after === false || $after['dev'] !== $opened['dev'] || $after['ino'] !== $opened['ino']
                || $after['mode'] !== $opened['mode'] || $after['uid'] !== $opened['uid']) AssignmentOrderOriginalSql::fail();
            if (str_ends_with($bytes, "\n")) $bytes = substr($bytes, 0, -1);
            if (preg_match('/^[\x20-\x7E]{1,1024}$/D', $bytes) !== 1) AssignmentOrderOriginalSql::fail();
            return $bytes;
        } finally { fclose($handle); }
    }
}
