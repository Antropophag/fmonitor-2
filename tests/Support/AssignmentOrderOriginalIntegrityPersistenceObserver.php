<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

if(interface_exists(O\AssignmentOrderOriginalPersistenceObserver::class)){
    interface OriginalIntegrityPersistenceMarker extends O\AssignmentOrderOriginalPersistenceObserver {}
}else{
    interface OriginalIntegrityPersistenceMarker {}
}
final class OriginalIntegrityPersistenceObserver implements OriginalIntegrityPersistenceMarker
{
    public array $events=[];
    public function __construct(private ?\Closure $callback=null){}
    public function observe(O\AssignmentOrderOriginalPersistenceEvent $event):void
    {$this->events[]=$event->value;if($this->callback!==null)($this->callback)($event);}
}
