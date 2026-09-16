<?php
declare(strict_types=1);
namespace FMonitor2\IdentityAccess;
final class ProductionLegacyIdentityLinkFactory
{
 public static function create(\mysqli$db,string$processPrefix,string$legacyPrefix,?LegacyIdentityLinkClock$clock=null):MariaDbLegacyIdentityLink{return new MariaDbLegacyIdentityLink($db,$processPrefix,$legacyPrefix,$clock??new class implements LegacyIdentityLinkClock{public function now():string{return gmdate('Y-m-d\\TH:i:s\\Z');}});}
}
