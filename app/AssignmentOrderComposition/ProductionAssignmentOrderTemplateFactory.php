<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
use FMonitor2\InstallationProcess\ProductionPdfAssignmentOrderRenderer;
final class ProductionAssignmentOrderTemplateFactory
{
    public static function create(\mysqli $db,string $prefix=''):AssignmentOrderTemplateApplication
    {
        $renderer=new ProductionPdfAssignmentOrderRenderer();
        return new MariaDbTemplateApplication(new MariaDbSelectionSql($db,$prefix),new SelectionSystemClock(),$renderer->renderAssignmentOrder(...));
    }
    public static function dateReader(\mysqli $db,string $prefix=''):AssignmentOrderTemplateDateReader
    { return new MariaDbTemplateDateReader(new MariaDbSelectionSql($db,$prefix)); }
}
