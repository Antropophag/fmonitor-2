<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

/** Public injected mysqli boundary, with an actual native commit before acknowledgement loss. */
final class OriginalIntegrityAckLossMysqli extends \mysqli
{
    public ?\Closure $afterNativeCommit=null;public int $nativeCommitCalls=0;
    public function commit(int $flags=0,?string $name=null):bool
    {++$this->nativeCommitCalls;$ok=parent::commit($flags,$name);if($ok&&$this->afterNativeCommit!==null)($this->afterNativeCommit)($this);return $ok;}
}
final class OriginalIntegrityCountingRealFactory implements OriginalIntegrityFreshFactoryMarker
{
    public int $opens=0;public int $reads=0;public int $closes=0;public array $newConnectionIds=[];
    public function __construct(private O\AssignmentOrderOriginalFreshTerminalReaderFactory $inner,private \Closure $connectionIds){}
    public function open():O\AssignmentOrderOriginalFreshTerminalReaderOpenResult
    {
        ++$this->opens;$before=($this->connectionIds)();$out=$this->inner->open();
        $this->newConnectionIds=array_values(array_diff(($this->connectionIds)(),$before));
        return $out->reader===null?$out:O\AssignmentOrderOriginalFreshTerminalReaderOpenResult::opened(new OriginalIntegrityCountingRealReader($out->reader,$this));
    }
}
final class OriginalIntegrityCountingRealReader implements OriginalIntegrityFreshReaderMarker
{
    public function __construct(private O\AssignmentOrderOriginalFreshTerminalReader $inner,private OriginalIntegrityCountingRealFactory $owner){}
    public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup{++$this->owner->reads;return $this->inner->findTerminalRequest($requestId);}
    public function close():O\AssignmentOrderOriginalFreshReaderCloseStatus{++$this->owner->closes;return $this->inner->close();}
}
