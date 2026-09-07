<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
interface AssignmentOrderSelectionPortalQuery
{
    public function authorizeActor(int $actor):array;
    public function readSelectionPortal(int $objectId,int $actorId):array;
    public function searchEligibleInstallers(int $actorId,string $query,int $page):array;
}
