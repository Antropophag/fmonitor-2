<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalRuntime.php';
require_once __DIR__.'/AssignmentOrderOriginalSafeLogPath.php';

/** Opaque owner of exactly the handle checked after open; no path is retained. */
final class AssignmentOrderOriginalOpenedSafeLog implements AssignmentOrderOriginalRequestSafeLogObserver
{
    private bool $closed=false;
    private bool $closeFailed=false;
    private function __construct(private mixed $handle,private int $sequence,private string $requestId) {}

    public static function open(string $file,string $requestId=''): self
    {
        $handle=null;
        try {
            [$canonical,$path]=AssignmentOrderOriginalSafeLogPath::observe($file);
            $handle=@\fopen($canonical,'r+b'); // Existing file only: no create/truncate flags.
            if (!is_resource($handle)) { throw new \RuntimeException(); }
            $opened=@\fstat($handle); // Real metadata from the handle retained below.
            if (!AssignmentOrderOriginalSafeLogPath::validStat($opened)
                || !AssignmentOrderOriginalSafeLogAttributePolicy::accepts(
                    mode:$opened['mode'],uid:$opened['uid'],device:$opened['dev'],inode:$opened['ino'],
                    effectiveUid:AssignmentOrderOriginalSafeLogPath::effectiveUid(),
                    expectedDevice:$path['dev'],expectedInode:$path['ino'])) { throw new \RuntimeException(); }
            $sequence=self::lineCount($handle);
            return new self($handle,$sequence,$requestId);
        } catch (\Throwable) {
            if (is_resource($handle)) { try { self::closeHandle($handle); } catch (\Throwable) {} }
            throw new \RuntimeException('safe log unavailable');
        }
    }

    public static function canonical(string $file): string
    {
        return AssignmentOrderOriginalSafeLogPath::observe($file)[0];
    }

    public function useRequest(string $requestId): void
    {
        $this->requireOpen();$this->requestId=$requestId;
    }

    public function record(string $event,array $safeFields): void
    {
        $this->requireOpen();$locked=false;
        try {
            $item=['correlationId'=>substr(hash('sha256',$this->requestId),0,12),
                'event'=>$event,'safeFields'=>$safeFields,'sequence'=>++$this->sequence];
            $line=json_encode($item,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\n";
            if (@\flock($this->handle,LOCK_EX)!==true) { throw new \RuntimeException(); }
            $locked=true;
            if (@\fseek($this->handle,0,SEEK_END)!==0 || @\fwrite($this->handle,$line)!==strlen($line)
                || @\fflush($this->handle)!==true) { throw new \RuntimeException(); }
        } catch (\Throwable) { throw new \RuntimeException('safe log unavailable'); }
        finally {
            if ($locked) {
                try { if (@\flock($this->handle,LOCK_UN)!==true) { throw new \RuntimeException(); } }
                catch (\Throwable) { throw new \RuntimeException('safe log unavailable'); }
            }
        }
    }

    public function close(): void
    {
        if ($this->closed) {
            if ($this->closeFailed) { throw new \RuntimeException('safe log unavailable'); }
            return;
        }
        $this->closed=true;$handle=$this->handle;$this->handle=null;
        try { self::closeHandle($handle); }
        catch (\Throwable) { $this->closeFailed=true;throw new \RuntimeException('safe log unavailable'); }
    }

    public function isClosed(): bool { return $this->closed; }
    public function __serialize(): array { throw new \RuntimeException('safe log unavailable'); }
    public function __unserialize(array $data): void { throw new \RuntimeException('safe log unavailable'); }
    private function __clone(): void {}
    public function __destruct() { try { $this->close(); } catch (\Throwable) {} }

    private function requireOpen(): void
    {
        if ($this->closed || !is_resource($this->handle)) { throw new \RuntimeException('safe log unavailable'); }
    }

    private static function closeHandle(mixed $handle): void
    {
        try { if (!is_resource($handle) || @\fclose($handle)!==true) { throw new \RuntimeException(); } }
        catch (\Throwable) { throw new \RuntimeException('safe log unavailable'); }
    }

    private static function lineCount(mixed $handle): int
    {
        if (@\flock($handle,LOCK_SH)!==true) { throw new \RuntimeException('safe log unavailable'); }
        try {
            if (@\rewind($handle)!==true) { throw new \RuntimeException(); }
            $count=0;
            while (@\fgets($handle)!==false) { $count++; }
            if (!\feof($handle)) { throw new \RuntimeException(); }
            return $count;
        } finally {
            if (@\flock($handle,LOCK_UN)!==true) { throw new \RuntimeException('safe log unavailable'); }
        }
    }
}
