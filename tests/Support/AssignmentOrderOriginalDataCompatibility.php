<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

// Test-only compatibility markers never define/alias a production symbol.
if(interface_exists(O\AssignmentOrderOriginalCompleteLineageLookup::class)){
    interface OriginalCompatibilityCompleteLineage extends O\AssignmentOrderOriginalCompleteLineageLookup {}
}else{
    interface OriginalCompatibilityCompleteLineage extends O\AssignmentOrderOriginalLineageLookup,O\AssignmentOrderOriginalCurrentEvidenceLookup {}
}
if(interface_exists(O\AssignmentOrderOriginalFreshTerminalReaderFactory::class)){
    interface OriginalCompatibilityFreshFactoryPort extends O\AssignmentOrderOriginalFreshTerminalReaderFactory {}
    interface OriginalCompatibilityFreshReaderPort extends O\AssignmentOrderOriginalFreshTerminalReader {}
}else{
    interface OriginalCompatibilityFreshFactoryPort {}
    interface OriginalCompatibilityFreshReaderPort {}
}
final class OriginalCompatibilityFreshFactory implements OriginalCompatibilityFreshFactoryPort
{
    public int $opens=0;public int $reads=0;public int $closes=0;
    public function __construct(private \Closure $lookup,private ?\Closure $trace=null){}
    public function event(string $event):void{if($this->trace!==null)($this->trace)($event);}
    public function open():O\AssignmentOrderOriginalFreshTerminalReaderOpenResult
    {++$this->opens;$this->event('fresh.open');return O\AssignmentOrderOriginalFreshTerminalReaderOpenResult::opened(new OriginalCompatibilityFreshReader($this));}
    public function readFixture(string $requestId):O\AssignmentOrderOriginalResultLookup
    {++$this->reads;$this->event('fresh.read');return ($this->lookup)($requestId);}
}
final class OriginalCompatibilityFreshReader implements OriginalCompatibilityFreshReaderPort
{
    private bool $read=false;private bool $closed=false;
    public function __construct(private OriginalCompatibilityFreshFactory $factory){}
    public function findTerminalRequest(string $requestId):O\AssignmentOrderOriginalResultLookup
    {if($this->read||$this->closed)return new O\AssignmentOrderOriginalResultLookupValue(O\AssignmentOrderOriginalLookupStatus::UNAVAILABLE);$this->read=true;return $this->factory->readFixture($requestId);}
    public function close():O\AssignmentOrderOriginalFreshReaderCloseStatus
    {if(!$this->closed){$this->closed=true;++$this->factory->closes;$this->factory->event('fresh.close');}return O\AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED;}
}
