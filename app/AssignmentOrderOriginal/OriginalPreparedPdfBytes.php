<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Reads accepted native content without creating files or retaining a lease. */
final readonly class OriginalPreparedPdfBytes
{
    public function __construct(private string $root) {}

    public function read(array $revision, string $identity): string
    {
        $hash = $revision['sha256']; $size = $revision['byteSize'];
        if (!\is_string($hash) || \preg_match('/^[a-f0-9]{64}$/D', $hash) !== 1
            || !\is_int($size) || $size < 1 || $size > 20971520 || $identity !== 'content-sha256-'.$hash) self::fail();
        \clearstatcache(true, $this->root);
        if (\realpath($this->root) !== $this->root) self::fail();
        AssignmentOrderOriginalFileStorage::validateRoot($this->root);
        $uid = \posix_geteuid();
        \clearstatcache(true, $this->root); $rootStat = @\lstat($this->root);
        if ($rootStat === false || $rootStat['uid'] !== $uid || !\in_array($rootStat['mode'] & 07777, [0700, 0750], true)) self::fail();
        $lockPath = $this->root.'/.lock-'.\hash('sha256', $identity);
        $filePath = $this->root.'/content-'.$hash.'.pdf';
        $lock = null; $file = null; $leased = false;
        try {
            $lock = self::open($lockPath, $uid);
            if (!\flock($lock, LOCK_SH | LOCK_NB)) self::fail();
            $leased = true; self::coherent($lockPath, $lock, $uid);
            $file = self::open($filePath, $uid);
            if (self::coherent($filePath, $file, $uid)['size'] !== $size) self::fail();
            $bytes = ''; $remaining = $size + 1;
            while ($remaining > 0 && !\feof($file)) {
                $chunk = @\fread($file, \min(65536, $remaining));
                if ($chunk === false || ($chunk === '' && !\feof($file))) self::fail();
                $bytes .= $chunk; $remaining -= \strlen($chunk);
            }
            if (\strlen($bytes) !== $size || !\feof($file) || \hash('sha256', $bytes) !== $hash
                || self::coherent($filePath, $file, $uid)['size'] !== $size) self::fail();
            self::coherent($lockPath, $lock, $uid);
            return $bytes;
        } finally {
            self::release($file, $lock, $leased);
        }
    }

    private static function open(string $path, int $uid): mixed
    {
        \clearstatcache(true, $path); $before = @\lstat($path); self::regular($before, $uid);
        $handle = @\fopen($path, 'rb');
        if (!\is_resource($handle)) self::fail();
        try {
            $after = self::coherent($path, $handle, $uid);
            if ($before['dev'] !== $after['dev'] || $before['ino'] !== $after['ino']) self::fail();
            return $handle;
        } catch (\Throwable $error) {
            @\fclose($handle); throw $error;
        }
    }

    private static function coherent(string $path, mixed $handle, int $uid): array
    {
        \clearstatcache(true, $path); $pathStat = @\lstat($path); $descriptor = @\fstat($handle);
        self::regular($pathStat, $uid); self::regular($descriptor, $uid);
        if ($pathStat['dev'] !== $descriptor['dev'] || $pathStat['ino'] !== $descriptor['ino']) self::fail();
        return $descriptor;
    }

    private static function regular(array|false $stat, int $uid): void
    {
        if ($stat === false || ($stat['mode'] & 0170000) !== 0100000 || ($stat['mode'] & 07777) !== 0600
            || $stat['uid'] !== $uid || $stat['nlink'] !== 1) self::fail();
    }

    private static function release(mixed $file, mixed $lock, bool $leased): void
    {
        $failed = false;
        if (\is_resource($file)) {
            try { if (!\fclose($file)) $failed = true; } catch (\Throwable) { $failed = true; }
        }
        if (\is_resource($lock)) {
            if ($leased) { try { if (!\flock($lock, LOCK_UN)) $failed = true; } catch (\Throwable) { $failed = true; } }
            try { if (!\fclose($lock)) $failed = true; } catch (\Throwable) { $failed = true; }
        }
        if ($failed) self::fail();
    }

    private static function fail(): never { throw new \RuntimeException('Original PDF unavailable.'); }
}
