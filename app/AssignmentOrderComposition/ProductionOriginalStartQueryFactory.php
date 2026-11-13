<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class ProductionOriginalStartQueryFactory{public static function create(\mysqli$db,string$prefix=''):MariaDbOriginalStartQuery{return new MariaDbOriginalStartQuery(new MariaDbAssignmentOrderApplicationSql($db,$prefix));}}
