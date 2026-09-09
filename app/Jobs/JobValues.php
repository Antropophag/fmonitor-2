<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

/** @internal Validates closed commands before opening a persistence transaction. */
final class JobValues
{
    public static function keys(array $value,array $keys): void
    {
        $actual=array_keys($value);sort($actual);sort($keys);
        if($actual!==$keys)throw new \InvalidArgumentException('Invalid Jobs command.');
    }
    public static function text(mixed $value,string $pattern): string
    {
        if(!is_string($value)||preg_match($pattern,$value)!==1)throw new \InvalidArgumentException('Invalid Jobs value.');
        return $value;
    }
    public static function number(mixed $value,int $min=1,int $max=PHP_INT_MAX): int
    {
        if(!is_int($value)||$value<$min||$value>$max)throw new \InvalidArgumentException('Invalid Jobs number.');
        return $value;
    }
    public static function date(mixed $value): string
    {
        if(!is_string($value))throw new \InvalidArgumentException('Invalid Jobs instant.');
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s.u\Z',$value,new \DateTimeZone('UTC'));
        if(!$date||$date->format('Y-m-d\TH:i:s.u\Z')!==$value)throw new \InvalidArgumentException('Invalid Jobs instant.');
        return $value;
    }
    public static function plus(string $instant,int $seconds): string
    {
        return (new \DateTimeImmutable($instant))->modify('+'.$seconds.' seconds')->format('Y-m-d\TH:i:s.u\Z');
    }
    public static function json(mixed $value,int $limit=65535): string
    {
        if(!is_array($value)||($value!==[]&&array_is_list($value)))throw new \InvalidArgumentException('Jobs JSON must be an object.');
        $normalized=self::normalize($value,0);
        $json=json_encode($normalized===[]?(object)[]:$normalized,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        if(strlen($json)>$limit)throw new \InvalidArgumentException('Jobs JSON exceeds limit.');
        return $json;
    }
    private static function normalize(mixed $value,int $depth): mixed
    {
        if($depth>32)throw new \InvalidArgumentException('Jobs JSON exceeds depth.');
        if(is_array($value)){
            $list=array_is_list($value);
            foreach($value as $key=>$child){
                if(is_string($key)&&preg_match('/password|secret|token|credential|authorization/i',$key))throw new \InvalidArgumentException('Forbidden Jobs payload field.');
                $value[$key]=self::normalize($child,$depth+1);
            }
            if(!$list)ksort($value,SORT_STRING);
            return $value;
        }
        if(is_object($value)||is_resource($value)||(is_float($value)&&!is_finite($value)))throw new \InvalidArgumentException('Invalid Jobs JSON value.');
        return $value;
    }
}
