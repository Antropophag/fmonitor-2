<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionTerminalRequestReader
{ public function findTerminalRequest(SelectionRequestId $id):SelectionTerminalRequestLookup; }
