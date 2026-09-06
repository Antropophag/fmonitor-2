<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
interface AssignmentOrderTemplateApplication
{ public function generateAssignmentOrderTemplate(int $caseId,int $orderId,int $actorId): array; }
