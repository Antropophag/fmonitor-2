<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

/** Shared scalar grammar for passive dependency payloads; no I/O or clock. */
final class SelectionScalar
{
    public static function id(int $id): bool { return $id > 0; }
    public static function date(string $value): bool
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$value,$p)===1 && checkdate((int)$p[2],(int)$p[3],(int)$p[1]);
    }
    public static function utc(string $value): bool
    {
        if(preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D',$value)!==1 || !self::date(substr($value,0,10)))return false;
        try{$date=new \DateTimeImmutable($value);return $date->format('Y-m-d\TH:i:s\Z')===$value;}catch(\Throwable){return false;}
    }
    public static function sourceInstant(string $value): bool
    {
        if(strlen($value)>40 || preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D',$value,$m)!==1 || !self::utc($m[1].'T'.$m[2].'Z') || $m[3]==='-00:00')return false;
        if($m[3]==='Z')return true;$h=(int)substr($m[3],1,2);$minute=(int)substr($m[3],4,2);
        return $h<=14 && $minute<=59 && ($h<14 || $minute===0);
    }
    public static function text(string $value,int $max): bool
    {
        return $value!=='' && preg_match('//u',$value)===1 && preg_match('/^[\p{Z}\x{0009}-\x{000D}]|[\p{Z}\x{0009}-\x{000D}]$/u',$value)===0 && preg_match_all('/./us',$value)<=$max;
    }
    public static function hash(string $value): bool { return preg_match('/^[a-f0-9]{64}$/D',$value)===1; }
    public static function uuid(string $value): bool { return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$value)===1; }
    public static function json(mixed $value): string { return json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
    public static function selectionDate(SelectionInstant $at): string { return (new \DateTimeImmutable($at->utcRfc3339Seconds))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d'); }
}
