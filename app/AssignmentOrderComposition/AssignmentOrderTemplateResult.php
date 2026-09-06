<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;
final class AssignmentOrderTemplateResult
{
    public const FILENAME='Распоряжение о закреплении монтажников.pdf';
    public static function failure(string $status,string $reason):array
    { return ['status'=>$status,'reasonCode'=>$reason,'assignmentOrderId'=>null,'templateDate'=>null,'filename'=>null,'mediaType'=>null,'bytes'=>null]; }
    public static function generated(int $order,string $date,string $bytes):array
    { return ['status'=>'generated','reasonCode'=>null,'assignmentOrderId'=>$order,'templateDate'=>$date,'filename'=>self::FILENAME,'mediaType'=>'application/pdf','bytes'=>$bytes]; }
    public static function pdf(mixed $result):?string
    {
        if(!is_array($result)||!array_is_list($result)||count($result)!==1||!is_array($result[0]))return null;
        $r=$result[0];$keys=array_keys($r);sort($keys);
        if($keys!==['bytes','filename','mediaType','type']||$r['type']!=='order'||$r['filename']!==self::FILENAME||$r['mediaType']!=='application/pdf'||!is_string($r['bytes']))return null;
        $bytes=$r['bytes'];return strlen($bytes)>=20&&strlen($bytes)<=20971520&&preg_match('/^%PDF-1\.[4-7](?:\r\n|\n|\r)/D',$bytes)===1&&preg_match('/%%EOF\s*$/D',$bytes)===1?$bytes:null;
    }
}
