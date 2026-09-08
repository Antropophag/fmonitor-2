<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
interface AssignmentOrderTemplateDateReader
{ public function find(int $caseId,int $orderId): array; }
