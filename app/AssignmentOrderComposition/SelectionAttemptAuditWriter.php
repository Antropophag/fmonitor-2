<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface SelectionAttemptAuditWriter
{ public function append(SelectionSafeAttemptAudit $audit):SelectionAuditWriteResult; }
