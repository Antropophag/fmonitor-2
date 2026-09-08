<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionUnitOfWork
{ public function executeForCase(int $caseId,SelectionTransactionalWork $work):SelectionUnitOfWorkResult; }
