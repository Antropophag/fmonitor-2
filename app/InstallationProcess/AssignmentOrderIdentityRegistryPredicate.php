<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Canonical tokens for this family's fixed conjunction/IN/REGEXP predicates. */
final class AssignmentOrderIdentityRegistryPredicate
{
    public static function key(string $expression): string
    {
        $key='';$quoted=false;
        for ($i=0,$n=strlen($expression);$i<$n;$i++) {
            $char=$expression[$i];
            if ($quoted) {
                $key.=$char;
                if ($char==='\\' && $i+1<$n) { $key.=$expression[++$i]; continue; }
                if ($char==="'") {
                    if ($i+1<$n && $expression[$i+1]==="'") { $key.=$expression[++$i]; continue; }
                    $quoted=false;
                }
            } elseif ($char==="'") { $quoted=true;$key.=$char; }
            elseif (!str_contains(" \t\r\n`()",$char)) { $key.=strtolower($char); }
        }
        if ($quoted) { throw new \DomainException('Invalid constraint metadata.'); }
        return $key;
    }
}
