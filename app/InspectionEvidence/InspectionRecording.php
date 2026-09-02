<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

interface InspectionRecording
{
    public function completeItem(CompleteInspectionItem $command): ItemCompletionResult;
}
