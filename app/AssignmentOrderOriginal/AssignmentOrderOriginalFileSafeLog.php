<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;
require_once __DIR__.'/AssignmentOrderOriginalOpenedSafeLog.php';

/** Compatibility constructor only; all file I/O belongs to the opaque owner. */
final class AssignmentOrderOriginalFileSafeLog implements AssignmentOrderOriginalRequestSafeLogObserver
{
    private AssignmentOrderOriginalOpenedSafeLog $owner;
    public function __construct(string $file,string $request='') { $this->owner=AssignmentOrderOriginalOpenedSafeLog::open($file,$request); }
    public static function canonical(string $file): string { return AssignmentOrderOriginalOpenedSafeLog::canonical($file); }
    public function useRequest(string $requestId): void { $this->owner->useRequest($requestId); }
    public function record(string $event,array $fields): void { $this->owner->record($event,$fields); }
    public function __destruct() { if (isset($this->owner)) { try { $this->owner->close(); } catch (\Throwable) {} } }
}
