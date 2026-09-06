<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class SelectionSystemClock implements SelectionClock
{
    public function now(): SelectionInstantLookup
    { return SelectionInstantLookup::found(new SelectionInstant(gmdate('Y-m-d\TH:i:s\Z'))); }
}
