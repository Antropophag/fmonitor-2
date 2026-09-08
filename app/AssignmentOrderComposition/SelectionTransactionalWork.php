<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionTransactionalWork
{ public function run(SelectionTransactionSession $transaction):SelectionTransactionDecision; }
