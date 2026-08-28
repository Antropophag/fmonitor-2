<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

final class TaskOwnedArtifactRoot
{
    /** @return array{root:string,parent:string} */
    public static function create(string $caller, string $token): array
    {
        if (preg_match('/^[a-z]{3}$/D', $caller) !== 1 || preg_match('/^[a-f0-9]{12}$/D', $token) !== 1) {
            self::fail();
        }
        $repository = realpath(dirname(__DIR__, 2));
        $home = self::home();
        if ($repository === false || !self::inside($repository, $home)) self::fail();
        $parent = $repository . '/.test-artifacts';
        if (@lstat($parent) === false) {
            if (!@mkdir($parent, 0700) && @lstat($parent) === false) {
                self::fail();
            }
        }
        self::trustedDirectory($parent, $home);
        if (realpath($parent) !== $parent) self::fail();
        $basename = $caller . '-' . $token;
        $root = $parent . '/' . $basename;
        if (@lstat($root) !== false || !@mkdir($root, 0700)) self::fail();
        $canonical = realpath($root);
        if ($canonical !== $root || dirname($canonical) !== $parent || basename($canonical) !== $basename) self::fail();
        self::trustedDirectory($root, $home);
        return ['root' => $canonical, 'parent' => $parent];
    }

    public static function cleanup(array $ownership, string $caller, string $token): void
    {
        $basename = $caller . '-' . $token;
        $root = $ownership['root'] ?? null;
        $parent = $ownership['parent'] ?? null;
        if (!is_string($root) || !is_string($parent)
            || basename($root) !== $basename || dirname($root) !== $parent) self::fail();
        $home = self::home();
        self::trustedDirectory($parent, $home);
        if (realpath($parent) !== $parent || realpath($root) !== $root) self::fail();
        self::removeDirectory($root, $parent, $home);
        self::trustedDirectory($parent, $home);
        if (realpath($parent) !== $parent) self::fail();
    }

    private static function removeDirectory(string $path, string $parent, string $home): void
    {
        if (!self::inside($path, $parent) || !self::inside($path, $home)) self::fail();
        self::trustedDirectory($path, $home);
        $names = @scandir($path);
        if ($names === false) self::fail();
        foreach ($names as $name) {
            if ($name === '.' || $name === '..') continue;
            $child = $path . '/' . $name;
            if (!self::inside($child, $parent)) self::fail();
            $state = @lstat($child);
            if ($state === false || $state['uid'] !== posix_geteuid()) self::fail();
            $type = $state['mode'] & 0170000;
            if ($type === 0040000) self::removeDirectory($child, $parent, $home);
            elseif (($type === 0100000 || $type === 0120000) && !@unlink($child)) self::fail();
            elseif ($type !== 0100000 && $type !== 0120000) self::fail();
        }
        self::trustedDirectory($path, $home);
        if (!@rmdir($path)) self::fail();
    }

    private static function trustedDirectory(string $path, string $home): void
    {
        $state = @lstat($path);
        if ($state === false || ($state['mode'] & 0170000) !== 0040000 || is_link($path)
            || $state['uid'] !== posix_geteuid() || ($state['mode'] & 0022) !== 0
            || !self::inside($path, $home)) self::fail();
    }

    private static function home(): string
    {
        $account = posix_getpwuid(posix_geteuid());
        $home = is_array($account) ? realpath((string)($account['dir'] ?? '')) : false;
        if ($home === false || $home === '/') self::fail();
        return $home;
    }

    private static function inside(string $path, string $root): bool
    {
        return $path !== $root && str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }

    private static function fail(): never
    {
        throw new \TestFailure('Task-owned artifact boundary invalid.');
    }
}
