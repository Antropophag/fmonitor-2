<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use Throwable;

final class HttpReadOnlyFilesystemGuard
{
    public static function observe(
        callable $operation,
        array $protectedPaths,
        array $ownedMutableRoots = [],
    ): mixed {
        [$protected, $mutable] = self::validate($protectedPaths, $ownedMutableRoots);
        $before = self::snapshotAll($protected);

        $result = null;
        $operationFailure = null;
        try {
            $result = $operation();
        } catch (Throwable $failure) {
            $operationFailure = $failure;
        }

        try {
            $after = self::snapshotAll($protected);
        } catch (\TestFailure) {
            throw new \TestFailure('Protected HTTP read-only path changed.');
        }
        if ($before !== $after) {
            throw new \TestFailure('Protected HTTP read-only path changed.');
        }
        if ($operationFailure !== null) {
            throw $operationFailure;
        }

        return $result;
    }

    /** @return array{array<string, string>, array<string, string>} */
    private static function validate(array $protectedPaths, array $ownedMutableRoots): array
    {
        $home = self::homeDirectory();
        $protected = [];
        $mutable = [];

        foreach ($protectedPaths as $path) {
            [$canonical, $type] = self::canonicalPath($path, false, $home);
            if (isset($protected[$canonical])) {
                self::invalid();
            }
            $protected[$canonical] = $type;
        }
        foreach ($ownedMutableRoots as $path) {
            [$canonical, $type] = self::canonicalPath($path, true, $home);
            if ($type !== 'directory' || isset($mutable[$canonical])) {
                self::invalid();
            }
            $mutable[$canonical] = $type;
        }

        $protectedEntries = array_keys($protected);
        for ($left = 0; $left < count($protectedEntries); ++$left) {
            for ($right = $left + 1; $right < count($protectedEntries); ++$right) {
                $a = $protectedEntries[$left];
                $b = $protectedEntries[$right];
                if (self::overlaps($a, $b)
                    && ($protected[$a] === 'directory' || $protected[$b] === 'directory')) {
                    self::invalid();
                }
            }
        }

        $mutableEntries = array_keys($mutable);
        for ($left = 0; $left < count($mutableEntries); ++$left) {
            for ($right = $left + 1; $right < count($mutableEntries); ++$right) {
                if (self::overlaps($mutableEntries[$left], $mutableEntries[$right])) {
                    self::invalid();
                }
            }
        }
        foreach ($protected as $protectedPath => $type) {
            foreach ($mutable as $mutablePath => $_) {
                if ($type === 'directory' && self::overlaps($protectedPath, $mutablePath)) {
                    self::invalid();
                }
            }
        }

        ksort($protected, SORT_STRING);
        ksort($mutable, SORT_STRING);
        return [$protected, $mutable];
    }

    /** @return array{string, string} */
    private static function canonicalPath(mixed $path, bool $directoryOnly, string $home): array
    {
        if (!is_string($path) || $path === '' || $path[0] !== '/') {
            self::invalid();
        }
        $state = @lstat($path);
        $canonical = realpath($path);
        if ($state === false || $canonical === false || $canonical !== $path || is_link($path)) {
            self::invalid();
        }
        $repository = realpath(dirname(__DIR__, 2));
        $workspaceDependency = $repository === false ? false : realpath($repository . '/../shlz-ui');
        $allowedRoots = array_values(array_filter([$repository, $workspaceDependency], 'is_string'));
        $insideAllowedRoot = false;
        foreach ($allowedRoots as $allowedRoot) {
            if ($canonical === $allowedRoot || str_starts_with($canonical, $allowedRoot . DIRECTORY_SEPARATOR)) {
                $insideAllowedRoot = true;
                break;
            }
        }
        if (!$insideAllowedRoot
            || ($canonical !== $home && !str_starts_with($canonical, $home . DIRECTORY_SEPARATOR))) {
            self::invalid();
        }
        $kind = $state['mode'] & 0170000;
        $type = match ($kind) {
            0040000 => 'directory',
            0100000 => 'regular file',
            default => null,
        };
        if ($type === null || ($directoryOnly && $type !== 'directory')) {
            self::invalid();
        }
        return [$canonical, $type];
    }

    private static function homeDirectory(): string
    {
        $account = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid()) : false;
        $home = is_array($account) && is_string($account['dir'] ?? null)
            ? realpath($account['dir'])
            : false;
        if ($home === false || $home === '/') {
            self::invalid();
        }
        return $home;
    }

    private static function overlaps(string $left, string $right): bool
    {
        return $left === $right
            || str_starts_with($left, $right . DIRECTORY_SEPARATOR)
            || str_starts_with($right, $left . DIRECTORY_SEPARATOR);
    }

    /** @param array<string, string> $protected */
    private static function snapshotAll(array $protected): string
    {
        $snapshots = [];
        foreach ($protected as $path => $type) {
            $snapshots[] = $type === 'directory'
                ? self::snapshotDirectory($path)
                : self::snapshotFile($path);
        }
        return hash('sha256', serialize($snapshots));
    }

    private static function snapshotFile(string $path): array
    {
        $before = self::freshLstat($path);
        $hash = $before !== false && ($before['mode'] & 0170000) === 0100000
            ? @hash_file('sha256', $path)
            : false;
        $after = self::freshLstat($path);
        if ($before === false || $hash === false || $after === false || !self::sameReadState($before, $after)) {
            self::snapshotFailed();
        }
        return [$path, 'regular file', $before['mode'] & 07777, $before['size'], $hash];
    }

    private static function snapshotDirectory(string $root): array
    {
        $rootBefore = self::freshLstat($root);
        if ($rootBefore === false || ($rootBefore['mode'] & 0170000) !== 0040000) {
            self::snapshotFailed();
        }
        $rows = [];
        self::snapshotDirectoryEntries($root, '', $rows);
        $rootAfter = self::freshLstat($root);
        if ($rootAfter === false || !self::sameReadState($rootBefore, $rootAfter)) {
            self::snapshotFailed();
        }
        usort($rows, static fn(array $a, array $b): int => strcmp($a[0], $b[0]));
        return $rows;
    }

    private static function snapshotDirectoryEntries(string $path, string $relative, array &$rows): void
    {
        $rows[] = self::snapshotEntry($path, $relative);
        $before = self::freshLstat($path);
        if ($before === false || ($before['mode'] & 0170000) !== 0040000) {
            self::snapshotFailed();
        }
        $names = @scandir($path);
        if ($names === false) {
            self::snapshotFailed();
        }
        foreach ($names as $name) {
            if ($name === '.' || $name === '..' || str_contains($name, DIRECTORY_SEPARATOR)) {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $name;
            $childRelative = $relative === '' ? $name : $relative . DIRECTORY_SEPARATOR . $name;
            $state = self::freshLstat($child);
            if ($state === false) {
                self::snapshotFailed();
            }
            if (($state['mode'] & 0170000) === 0040000) {
                self::snapshotDirectoryEntries($child, $childRelative, $rows);
            } else {
                $rows[] = self::snapshotEntry($child, $childRelative);
            }
        }
        $after = self::freshLstat($path);
        if ($after === false || !self::sameReadState($before, $after)) {
            self::snapshotFailed();
        }
    }

    private static function snapshotEntry(string $path, string $relative): array
    {
        $before = self::freshLstat($path);
        if ($before === false) {
            self::snapshotFailed();
        }
        $mode = $before['mode'] & 0170000;
        if ($mode === 0040000) {
            $after = self::freshLstat($path);
            if ($after === false || !self::sameReadState($before, $after)) self::snapshotFailed();
            return [$relative, 'directory', $before['mode'] & 07777];
        }
        if ($mode === 0100000) {
            $hash = @hash_file('sha256', $path);
            $after = self::freshLstat($path);
            if ($hash === false || $after === false || !self::sameReadState($before, $after)) {
                self::snapshotFailed();
            }
            return [$relative, 'regular file', $before['mode'] & 07777, $before['size'], $hash];
        }
        if ($mode === 0120000) {
            $target = @readlink($path);
            $after = self::freshLstat($path);
            if ($target === false || $after === false || !self::sameReadState($before, $after)) {
                self::snapshotFailed();
            }
            return [$relative, 'symlink', $before['mode'] & 07777, $target];
        }
        self::snapshotFailed();
    }

    private static function freshLstat(string $path): array|false
    {
        clearstatcache(true, $path);
        return @lstat($path);
    }

    private static function sameReadState(array $before, array $after): bool
    {
        // Access time may legitimately change because this snapshot reads bytes.
        // Every identity, type, permission, ownership, size and write-time field
        // must remain coherent across that read.
        foreach (['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'mtime', 'ctime', 'blksize', 'blocks'] as $field) {
            if (($before[$field] ?? null) !== ($after[$field] ?? null)) return false;
        }
        return true;
    }

    private static function invalid(): never
    {
        throw new \TestFailure('Invalid HTTP read-only path configuration.');
    }

    private static function snapshotFailed(): never
    {
        throw new \TestFailure('Protected HTTP read-only path could not be read.');
    }
}
