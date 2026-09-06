<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionCloseableTerminalRequestReader extends SelectionTerminalRequestReader
{ public function close():void; }
