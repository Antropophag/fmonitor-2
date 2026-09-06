<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

interface AssignmentOrderCompositionResult
{
    public function status(): AssignmentOrderCompositionStatus;
    public function reasonCode(): ?AssignmentOrderCompositionReason;
    public function retryable(): bool;
    public function requestId(): SelectionRequestId;
    public function success(): ?SelectionSuccessPayload;
}
