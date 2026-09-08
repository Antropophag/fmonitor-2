<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionFreshTerminalReaderFactory
{ public function open():SelectionCloseableTerminalRequestReader; }
