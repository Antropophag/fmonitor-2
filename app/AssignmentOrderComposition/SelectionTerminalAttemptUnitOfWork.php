<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionTerminalAttemptUnitOfWork
{ public function execute(SelectionTerminalAttemptPersistence $payload):SelectionUnitOfWorkResult; }
