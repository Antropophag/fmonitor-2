<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
final class StoringAssignmentOrderRenderer
{
    public function __construct(private readonly ProductionPdfAssignmentOrderRenderer|ProductionHtmlAssignmentOrderRenderer $renderer,private readonly ContentAddressedArtifactStore $store){}
    public function renderAssignmentOrder(array $input):array
    {
        $artifacts=$this->renderer->renderAssignmentOrder($input);
        foreach($artifacts as $artifact)$this->store->store($artifact['bytes']);
        return $artifacts;
    }
}
