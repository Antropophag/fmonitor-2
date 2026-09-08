<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalSafeLogAttributePolicy.php';

/** Non-following pathname observation only; it never opens or writes a file. */
final class AssignmentOrderOriginalSafeLogPath
{
    public static function observe(string $file): array
    {
        try {
            $parts=explode('/',$file);
            if ($file==='' || $file[0]!==DIRECTORY_SEPARATOR || in_array('',array_slice($parts,1),true)
                || in_array('.',$parts,true) || in_array('..',$parts,true)) { throw new \RuntimeException(); }
            \clearstatcache(true,$file);
            $resolved=@\realpath($file);
            $canonical=is_string($resolved) && ($resolved===$file
                || (PHP_OS_FAMILY==='Darwin' && str_starts_with($file,'/var/') && $resolved==='/private'.$file));
            $stat=@\lstat($file);$uid=self::effectiveUid();
            if (!$canonical || !self::validStat($stat) || \is_link($file)
                || !AssignmentOrderOriginalSafeLogAttributePolicy::accepts(
                    $stat['mode'],$stat['uid'],$stat['dev'],$stat['ino'],$uid,$stat['dev'],$stat['ino'])) {
                throw new \RuntimeException();
            }
            return [$resolved,$stat];
        } catch (\Throwable) { throw new \RuntimeException('safe log unavailable'); }
    }

    public static function effectiveUid(): int
    {
        if (!\function_exists('posix_geteuid')) { throw new \RuntimeException('safe log unavailable'); }
        $uid=\posix_geteuid();
        if (!is_int($uid) || $uid<0) { throw new \RuntimeException('safe log unavailable'); }
        return $uid;
    }

    public static function validStat(mixed $stat): bool
    {
        if (!is_array($stat)) { return false; }
        foreach (['mode','uid','dev','ino'] as $field) { if (!isset($stat[$field]) || !is_int($stat[$field])) { return false; } }
        return true;
    }
}
