<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class PilotRouteCsp
{
    public const BASE="default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
    public const SCRIPT="default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
    public const CHECKLIST="default-src 'none'; style-src 'self'; script-src 'self'; worker-src 'self'; connect-src 'self'; img-src 'self' blob:; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
    public const WORKER="default-src 'self'; connect-src 'self'";

    public static function classify(string$method,string$path,int$status,string$contentType):string
    {
        if($method==='GET'&&$path==='/pilot/assets/checklist-sw.js'&&$status>=200&&$status<300&&\str_starts_with(\strtolower($contentType),'text/javascript'))return self::WORKER;
        if($status<200||$status>=300||!\str_starts_with(\strtolower($contentType),'text/html'))return self::BASE;
        if($method==='POST')return $path==='/pilot/login'&&$status===200?self::SCRIPT:self::BASE;
        if(!\in_array($method,['GET','HEAD'],true))return self::BASE;
        if(\preg_match('#^/pilot/objects/[1-9][0-9]*/checklist$#D',$path)===1||\preg_match('#^/pilot/construction-control/objects/[1-9][0-9]*/checklist$#D',$path)===1)return self::CHECKLIST;
        if(\in_array($path,['/pilot/login','/pilot/','/pilot/objects','/pilot/construction-control','/pilot/installers','/pilot/admin/users','/pilot/admin/roles','/pilot/calendar','/pilot/calendar/','/pilot/otiz','/pilot/otiz/','/pilot/otiz/objects','/pilot/otiz/payments','/pilot/otiz/history','/pilot/otiz/reconciliation','/pilot/otiz/reconciliation/quarantine','/pilot/otiz/active-baselines','/pilot/otiz/historical-replay'],true))return self::SCRIPT;
        return \preg_match('#^/pilot/objects/[1-9][0-9]*$#D',$path)===1||\preg_match('#^/pilot/objects/[1-9][0-9]*/assignment-order/prepare$#D',$path)===1||\preg_match('#^/pilot/otiz/snapshots/[1-9][0-9]*$#D',$path)===1?self::SCRIPT:self::BASE;
    }

    public static function forResponse(string$method,string$path,int$status,string$contentType,string$body):string
    {
        $policy=self::classify($method,$path,$status,$contentType);
        if(($policy===self::SCRIPT||$policy===self::CHECKLIST)&&\preg_match('#<script\b[^>]*\bsrc=["\']/pilot/[^"\']+["\'][^>]*>#i',$body)!==1)return self::BASE;
        return $policy;
    }

    public static function installDirectHeaderPolicy():void
    {
        \header_register_callback(static function():void{$path=\parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);$type='text/plain; charset=UTF-8';foreach(\headers_list()as$header)if(\str_starts_with(\strtolower($header),'content-type:'))$type=\trim(\substr($header,13));\header('Content-Security-Policy: '.self::classify(\strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET')),\is_string($path)?$path:'',\http_response_code(),$type),true);});
    }
}
