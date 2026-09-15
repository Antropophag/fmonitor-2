<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;
use FMonitor2\Jobs\JobsRuntimeConfiguration;
final class BitrixOrderDocumentDelivery
{
 public static function runFromEnvironment():array{try{$c=self::config(static fn(string$n):string=>self::env($n),static fn(string$n):?string=>self::optional($n));$db=self::db(static fn(string$n):string=>self::env($n));$p=self::prefix(self::env('FMONITOR_PROCESS_TABLE_PREFIX'));}catch(\Throwable){return['status'=>'failed','reason'=>'CONFIGURATION_UNAVAILABLE'];}try{return self::run($c,$db,$p);}catch(\Throwable){return['status'=>'failed','reason'=>'SYNC_FAILED'];}finally{$db->close();}}
 public static function runForJob(JobsRuntimeConfiguration$c):array{$get=static fn(string$n):string=>$c->value($n);$delivery=self::config($get,static fn(string$n):?string=>$c->optionalValue($n));$db=self::db($get);try{return self::run($delivery,$db,$c->prefix());}finally{$db->close();}}
 private static function run(BitrixOrderDocumentDeliveryConfig$c,\mysqli$db,string$p):array{$r=(new NativeBitrixOrderDocumentDelivery($c))->fetch();return$r->kind==='complete'?(new OrderDocumentLinksApplication($db,$p))->replace($r->links):['status'=>'failed','reason'=>$r->reason??'DELIVERY_FAILED'];}
 private static function config(callable$get,callable$optional):BitrixOrderDocumentDeliveryConfig{$file=$get('FMONITOR_BITRIX_TOKEN_FILE');$token=is_file($file)?trim((string)file_get_contents($file)):'';return new BitrixOrderDocumentDeliveryConfig($get('FMONITOR_BITRIX_ORIGIN'),self::positive($get('FMONITOR_BITRIX_WEBHOOK_USER_ID')),self::positive($get('FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID')),'/',$token,$optional('FMONITOR_BITRIX_CA_FILE'),50,10000,1048576,30);}
 private static function db(callable$get):\mysqli{mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new \mysqli($get('FMONITOR_DB_HOST'),$get('FMONITOR_DB_USER'),$get('FMONITOR_DB_PASSWORD'),$get('FMONITOR_DB_NAME'),self::positive($get('FMONITOR_DB_PORT')));$db->set_charset('utf8mb4');return$db;}
 private static function env(string$n):string{$v=getenv($n);if(!is_string($v)||$v===''||str_contains($v,"\0"))throw new \InvalidArgumentException();return$v;}
 private static function optional(string$n):?string{$v=getenv($n);return is_string($v)&&$v!==''?$v:null;}
 private static function positive(string$v):int{if(preg_match('/^[1-9][0-9]*$/D',$v)!==1)throw new \InvalidArgumentException();return(int)$v;}
 private static function prefix(string$v):string{if(preg_match('/^[A-Za-z0-9_]{0,25}$/D',$v)!==1)throw new \InvalidArgumentException();return$v;}
}
