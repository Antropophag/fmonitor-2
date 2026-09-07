<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Narrow persistence adapter for generation identity; schema remains migration-owned. */
final class MariaDbPilotStartupState
{
    public static function sentinel(\mysqli$db,string$p):?array
    {$r=$db->query("SELECT generation,fingerprint,manifest_nonce FROM `{$p}fm2_pilot_generation_sentinel` WHERE singleton_id=1")->fetch_assoc();return is_array($r)?$r:null;}
    public static function initialize(\mysqli$db,string$p,string$fingerprint,string$nonce):void
    {$s=$db->prepare("INSERT INTO `{$p}fm2_pilot_generation_sentinel` VALUES(1,1,?,?)");$s->bind_param('ss',$fingerprint,$nonce);$s->execute();}
    public static function userCount(\mysqli$db,string$p):int
    {return(int)$db->query("SELECT COUNT(*) n FROM `{$p}fm2_pilot_users`")->fetch_assoc()['n'];}
    public static function serverIdentity(\mysqli$db):string
    {return(string)$db->query('SELECT @@hostname identity')->fetch_assoc()['identity'];}
}
