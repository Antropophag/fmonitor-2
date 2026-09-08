<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Translates the private pilot webhook document into native client inputs. */
final class WorkerConfiguration
{
    public static function fromFile(string $path): array
    {
        if ($path === '' || $path[0] !== '/' || !is_file($path) || !is_readable($path)) self::fail();
        try { $document = json_decode((string) file_get_contents($path), true, 8, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { self::fail(); }
        if (!is_array($document) || array_diff(array_keys($document), ['baseUrl', 'departments']) !== []) self::fail();
        $baseUrl = $document['baseUrl'] ?? null;
        $departments = $document['departments'] ?? null;
        if (!is_string($baseUrl) || !is_array($departments)) self::fail();
        $parts = parse_url($baseUrl);
        if ($parts === false || ($parts['scheme'] ?? null) !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !is_string($parts['host'] ?? null) || preg_match('#^/rest/([1-9][0-9]*)/([A-Za-z0-9_-]{1,256})/?$#D', (string) ($parts['path'] ?? ''), $match) !== 1) self::fail();
        $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $ids = [];
        foreach ($departments as $id) {
            if (is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id) === 1) $id = (int) $id;
            if (!is_int($id) || $id < 1) self::fail();
            $ids[] = $id;
        }
        sort($ids, SORT_NUMERIC);
        if ($ids === [] || count($ids) !== count(array_unique($ids))) self::fail();
        return ['origin' => $origin, 'webhookUserId' => (int) $match[1], 'token' => $match[2], 'departmentIds' => $ids];
    }

    public static function stageToken(string $token): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fm2-workforce-token-');
        if ($path === false) self::fail();
        try {
            if (!chmod($path, 0600) || file_put_contents($path, $token, LOCK_EX) !== strlen($token)) self::fail();
            clearstatcache(true, $path);$stat = lstat($path);$canonical = realpath($path);
            if ($stat === false || !is_string($canonical) || ($stat['mode'] & 07777) !== 0600 || $stat['nlink'] !== 1 || $stat['uid'] !== posix_geteuid()) self::fail();
            return $canonical;
        } catch (\Throwable $error) {
            @unlink($path);
            throw $error;
        }
    }

    private static function fail(): never { throw new \RuntimeException('CONFIGURATION_INVALID'); }
}
