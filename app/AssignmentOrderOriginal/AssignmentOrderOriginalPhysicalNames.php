<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Pure physical addressing; no schema discovery or runtime selection. */
final class AssignmentOrderOriginalPhysicalNames
{
    public const ALIASES = [
        'fm2_assignment_order_original_maintenance_requests'=>'fm2_original_maintenance_requests',
        'fm2_assignment_order_original_maintenance_audits'=>'fm2_original_maintenance_audits',
    ];
    public static function table(string $prefix, string $logical): string
    {
        if (strlen($prefix)>25 || preg_match('/^[A-Za-z0-9_]*$/D',$prefix)!==1)
            throw new \InvalidArgumentException('Invalid table prefix.');
        return $prefix.(strlen($prefix.$logical)>64 ? (self::ALIASES[$logical]??$logical) : $logical);
    }
    public static function qualified(string $name): string
    {
        foreach (self::ALIASES as $logical=>$alias) {
            if (str_ends_with($name,$logical)) {
                $prefix=substr($name,0,-strlen($logical));
                if (strlen($prefix)<=25 && preg_match('/^[A-Za-z0-9_]*$/D',$prefix)===1) return self::table($prefix,$logical);
            }
        }
        return $name;
    }
    public static function logical(string $prefix, string $physical): string
    {
        foreach (self::ALIASES as $logical=>$alias) if (self::table($prefix,$logical)===$physical) return $logical;
        return str_starts_with($physical,$prefix) ? substr($physical,strlen($prefix)) : $physical;
    }
    public static function foreignKey(string $prefix, string $logical, string $column): string
    { return 'fk_ao_'.substr(hash('sha256',$prefix."\0".$logical."\0".$column),0,48); }
}
